<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Order;
use App\Models\Payment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Stripe\Checkout\Session;
use Stripe\Exception\ApiErrorException;
use Stripe\Exception\SignatureVerificationException;
use Stripe\StripeClient;
use Stripe\Webhook;
use Symfony\Component\HttpFoundation\Response;

class StripePaymentController extends Controller
{
    public function checkout(Request $request): Response
    {
        $this->authorizeCustomer($request);
        $this->ensureConfigured();

        $cart = Cart::query()
            ->where('user_id', $request->user()->id)
            ->with(['cartItems.menuItem.days', 'cartItems.menuItem.schools', 'cartItems.student.school'])
            ->first();

        if (! $cart || $cart->cartItems->isEmpty()) {
            throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
        }

        foreach ($cart->cartItems as $item) {
            $this->validateCartItem($request, $item);
        }

        $total = $cart->cartItems->sum(
            fn (CartItem $item) => (float) $item->menuItem->price * $item->quantity
        );

        $order = DB::transaction(function () use ($request, $cart, $total) {
            $order = $request->user()->orders()->create([
                'total_amount' => $total,
                'order_status' => 'pending',
            ]);

            foreach ($cart->cartItems as $item) {
                $order->orderItems()->create([
                    'student_id' => $item->student_id,
                    'menu_item_id' => $item->menu_item_id,
                    'unit_price' => $item->menuItem->price,
                    'scheduled_time' => $item->scheduled_time,
                    'quantity' => $item->quantity,
                ]);
            }

            $order->payment()->create([
                'user_id' => $request->user()->id,
                'amount' => $total,
                'payment_date' => now(),
                'method' => 'stripe',
                'status' => 'pending',
            ]);

            return $order;
        });

        try {
            $session = $this->stripe()->checkout->sessions->create([
                'mode' => 'payment',
                'payment_method_types' => ['card'],
                'customer_email' => $request->user()->email,
                'client_reference_id' => (string) $order->id,
                'metadata' => [
                    'order_id' => (string) $order->id,
                    'user_id' => (string) $request->user()->id,
                ],
                'line_items' => $cart->cartItems->map(fn (CartItem $item) => [
                    'price_data' => [
                        'currency' => config('services.stripe.currency', 'myr'),
                        'unit_amount' => (int) round((float) $item->menuItem->price * 100),
                        'product_data' => [
                            'name' => $item->menuItem->name.' for '.$item->student->name,
                            'description' => 'Meal collection: '.$item->scheduled_time->format('d/m/Y'),
                        ],
                    ],
                    'quantity' => $item->quantity,
                ])->values()->all(),
                'success_url' => route('customer.checkout.success').'?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url' => route('customer.checkout.cancel', ['order' => $order->id]),
            ]);

            $order->payment()->update(['stripe_session_id' => $session->id]);

            return Inertia::location($session->url);
        } catch (ApiErrorException $exception) {
            DB::transaction(function () use ($order) {
                $order->payment()->delete();
                $order->delete();
            });

            Log::error('Stripe Checkout session creation failed.', ['message' => $exception->getMessage()]);
            throw ValidationException::withMessages(['payment' => 'Stripe Checkout could not be started. Please try again.']);
        }
    }

    public function success(Request $request): RedirectResponse
    {
        $this->authorizeCustomer($request);
        $request->validate(['session_id' => ['required', 'string']]);

        try {
            $session = $this->stripe()->checkout->sessions->retrieve($request->string('session_id')->toString());
        } catch (ApiErrorException $exception) {
            throw ValidationException::withMessages(['payment' => 'The Stripe payment session could not be verified.']);
        }

        abort_unless((int) ($session->metadata->user_id ?? 0) === $request->user()->id, 403);
        abort_unless($session->payment_status === 'paid', 422, 'Stripe has not confirmed this payment.');
        $order = $this->completePayment($session);

        return redirect()->route('customer.orders')->with('success', 'Stripe payment completed. Order #'.$order->id.' is confirmed.');
    }

    public function cancel(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeCustomer($request);
        abort_unless($order->user_id === $request->user()->id, 403);

        if ($order->order_status === 'pending') {
            $order->update(['order_status' => 'cancelled']);
            $order->payment()->update(['status' => 'cancelled']);
        }

        return redirect()->route('customer.menu')->with('error', 'Stripe payment was cancelled. Your cart is unchanged.');
    }

    public function webhook(Request $request): Response
    {
        $secret = config('services.stripe.webhook_secret');
        abort_if(blank($secret), 503, 'Stripe webhook secret is not configured.');

        try {
            $event = Webhook::constructEvent(
                $request->getContent(),
                (string) $request->header('Stripe-Signature'),
                $secret,
            );
        } catch (\UnexpectedValueException|SignatureVerificationException $exception) {
            return response('Invalid Stripe webhook.', 400);
        }

        if ($event->type === 'checkout.session.completed' && $event->data->object->payment_status === 'paid') {
            $this->completePayment($event->data->object);
        }

        return response('Webhook handled.');
    }

    private function completePayment(Session $session): Order
    {
        return DB::transaction(function () use ($session) {
            $order = Order::query()->with('orderItems')->lockForUpdate()->findOrFail((int) $session->metadata->order_id);
            $payment = Payment::query()->where('order_id', $order->id)->lockForUpdate()->firstOrFail();

            abort_unless(
                (int) $session->amount_total === (int) round((float) $order->total_amount * 100)
                && strtolower((string) $session->currency) === strtolower(config('services.stripe.currency', 'myr')),
                422,
                'Stripe payment amount does not match this order.',
            );

            if ($payment->status === 'paid') {
                return $order;
            }

            $order->update(['order_status' => 'paid']);
            $payment->update([
                'status' => 'paid',
                'payment_date' => now(),
                'stripe_session_id' => $session->id,
                'stripe_payment_intent_id' => is_string($session->payment_intent) ? $session->payment_intent : $session->payment_intent?->id,
            ]);

            $cart = Cart::query()->where('user_id', $order->user_id)->first();
            foreach ($order->orderItems as $item) {
                $cart?->cartItems()->where([
                    'student_id' => $item->student_id,
                    'menu_item_id' => $item->menu_item_id,
                    'scheduled_time' => $item->scheduled_time,
                ])->delete();
            }

            return $order;
        });
    }

    private function validateCartItem(Request $request, CartItem $item): void
    {
        $today = now();
        $weeksAhead = $today->dayOfWeekIso === 7 ? 2 : 1;
        $weekStart = $today->copy()->startOfWeek()->addWeeks($weeksAhead)->startOfDay();
        $weekEnd = $weekStart->copy()->endOfWeek()->endOfDay();
        $valid = $request->user()->children()->where('student_id', $item->student_id)->exists()
            && $item->student->approval_status === 'approved'
            && $item->student->school?->is_active
            && $item->menuItem->is_available
            && $item->menuItem->schools->contains('id', $item->student->school_id)
            && $item->menuItem->days->contains('day_of_week', strtolower($item->scheduled_time->format('l')))
            && $item->scheduled_time->betweenIncluded($weekStart, $weekEnd);

        if (! $valid) {
            throw ValidationException::withMessages(['cart' => 'A cart meal is no longer available for the selected child or date.']);
        }
    }

    private function stripe(): StripeClient
    {
        return new StripeClient(config('services.stripe.secret'));
    }

    private function ensureConfigured(): void
    {
        if (blank(config('services.stripe.secret'))) {
            throw ValidationException::withMessages(['payment' => 'Stripe is not configured. Add STRIPE_SECRET to your environment file.']);
        }
    }

    private function authorizeCustomer(Request $request): void
    {
        abort_unless($request->user()?->hasRole('customer'), 403);
    }
}
