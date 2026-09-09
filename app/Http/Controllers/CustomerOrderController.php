<?php

namespace App\Http\Controllers;

use App\Models\Cart;
use App\Models\CartItem;
use App\Models\Children;
use App\Models\MenuItem;
use App\Models\OrderItem;
use App\Support\SchoolOrderingSchedule;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class CustomerOrderController extends Controller
{
    public function menu(Request $request)
    {
        $this->authorizeCustomer($request);

        $children = $request->user()
            ->children()
            ->with('student.school')
            ->get();
        $children->each(function (Children $child): void {
            $child->setAttribute(
                'ordering_deadline_day',
                SchoolOrderingSchedule::deadlineDay($child->student?->school),
            );
        });
        $schoolIds = $children
            ->filter(fn ($child) => $child->student?->school?->is_active)
            ->pluck('student.school_id')
            ->unique()
            ->values();
        $cart = Cart::query()
            ->where('user_id', $request->user()->id)
            ->with([
                'cartItems.menuItem.days',
                'cartItems.menuItem.menuCategories',
                'cartItems.menuItem.schools',
                'cartItems.student.school',
            ])
            ->first();
        $cartItems = $cart?->cartItems ?? collect();
        $cartItems->each(function (CartItem $item): void {
            $item->setAttribute(
                'collection_date',
                $item->scheduled_time->copy()->timezone('Asia/Kuala_Lumpur')->toDateString(),
            );
        });

        return inertia('Customer/Menu', [
            'children' => $children,
            'menuItems' => MenuItem::query()
                ->where('is_available', true)
                ->whereHas('schools', fn ($query) => $query
                    ->where('schools.is_active', true)
                    ->whereIn('schools.id', $schoolIds))
                ->with(['menuCategories', 'days', 'schools', 'components'])
                ->orderBy('name')
                ->get(),
            'cartItems' => $cartItems,
        ]);
    }

    public function addToCart(Request $request): RedirectResponse
    {
        $this->authorizeCustomer($request);
        $validated = $this->validateSelection($request);
        $scheduledTime = Carbon::parse($validated['collection_date'])->startOfDay();
        $this->validateMealForChild(
            $request,
            (int) $validated['student_id'],
            (int) $validated['menu_item_id'],
            $scheduledTime,
        );

        $cart = Cart::firstOrCreate(['user_id' => $request->user()->id]);
        $cart->cartItems()->updateOrCreate([
            'student_id' => $validated['student_id'],
            'menu_item_id' => $validated['menu_item_id'],
            'scheduled_time' => $scheduledTime,
        ], [
            'quantity' => (int) $validated['quantity'],
        ]);

        return back()->with('success', 'Cart updated.');
    }

    public function updateCart(Request $request, CartItem $cartItem): RedirectResponse
    {
        $this->authorizeCustomer($request);
        $this->authorizeCartItem($request, $cartItem);

        $validated = $request->validate([
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);

        $this->validateMealForChild(
            $request,
            $cartItem->student_id,
            $cartItem->menu_item_id,
            $cartItem->scheduled_time,
        );

        $cartItem->update([
            'quantity' => $validated['quantity'],
        ]);

        return back()->with('success', 'Cart updated.');
    }

    public function setCartQuantity(Request $request): RedirectResponse
    {
        $this->authorizeCustomer($request);
        $validated = $request->validate([
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'menu_item_id' => ['required', 'integer', Rule::exists('menu_items', 'id')],
            'collection_date' => ['required', 'date', 'after_or_equal:today'],
            'quantity' => ['required', 'integer', 'min:0', 'max:20'],
        ]);
        $scheduledTime = Carbon::parse($validated['collection_date'])->startOfDay();
        $this->validateMealForChild($request, (int) $validated['student_id'], (int) $validated['menu_item_id'], $scheduledTime);

        $cart = Cart::query()->where('user_id', $request->user()->id)->first();
        if ((int) $validated['quantity'] === 0) {
            $cart?->cartItems()->where([
                'student_id' => $validated['student_id'],
                'menu_item_id' => $validated['menu_item_id'],
                'scheduled_time' => $scheduledTime,
            ])->delete();

            return back();
        }

        $cart ??= Cart::create(['user_id' => $request->user()->id]);
        $cart->cartItems()->updateOrCreate([
            'student_id' => $validated['student_id'],
            'menu_item_id' => $validated['menu_item_id'],
            'scheduled_time' => $scheduledTime,
        ], ['quantity' => $validated['quantity']]);

        return back();
    }

    public function removeFromCart(Request $request, CartItem $cartItem): RedirectResponse
    {
        $this->authorizeCustomer($request);
        $this->authorizeCartItem($request, $cartItem);
        $cartItem->delete();

        return back()->with('success', 'Meal removed from cart.');
    }

    public function checkout(Request $request): RedirectResponse
    {
        $this->authorizeCustomer($request);

        $order = DB::transaction(function () use ($request) {
            $cart = Cart::query()
                ->where('user_id', $request->user()->id)
                ->lockForUpdate()
                ->first();

            if (! $cart) {
                throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
            }

            $items = $cart->cartItems()
                ->with(['menuItem.days', 'menuItem.schools'])
                ->lockForUpdate()
                ->get();

            if ($items->isEmpty()) {
                throw ValidationException::withMessages(['cart' => 'Your cart is empty.']);
            }

            foreach ($items as $item) {
                $this->validateMealForChild(
                    $request,
                    $item->student_id,
                    $item->menu_item_id,
                    $item->scheduled_time,
                );
            }

            $total = $items->sum(
                fn (CartItem $item) => (float) $item->menuItem->price * $item->quantity
            );
            $order = $request->user()->orders()->create([
                'total_amount' => $total,
                'order_status' => 'paid',
            ]);

            foreach ($items as $item) {
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
                'method' => 'temporary',
                'status' => 'paid',
            ]);
            $cart->cartItems()->delete();

            return $order;
        });

        return redirect()->route('customer.orders')->with('success', 'Payment completed. Order #'.$order->id.' was created.');
    }

    public function orders(Request $request)
    {
        $this->authorizeCustomer($request);
        $orders = $request->user()
            ->orders()
            ->with(['payment', 'orderItems.menuItem.components', 'orderItems.student.school'])
            ->latest()
            ->get();

        $today = now()->startOfDay();
        $orders->each(function ($order) use ($today) {
            $order->orderItems->each(function ($item) use ($order, $today) {
                $school = $item->student?->school;
                $deadline = $school
                    ? SchoolOrderingSchedule::deadlineForCollection($item->scheduled_time, $school)
                    : $item->scheduled_time->copy()->startOfDay();
                $isPaidFutureMeal = $order->order_status === 'paid'
                    && $item->scheduled_time->gte($today);

                $item->setAttribute('deadline_locked', $isPaidFutureMeal && now()->gt($deadline));
                $item->setAttribute('ordering_deadline_day', SchoolOrderingSchedule::deadlineDay($school));
                $item->setAttribute(
                    'can_edit',
                    $isPaidFutureMeal
                        && now()->lte($deadline)
                        && $item->student?->school?->is_active
                );
            });
        });

        return inertia('Customer/Orders', [
            'orders' => $orders,
            'menuItems' => MenuItem::query()
                ->where('is_available', true)
                ->whereHas('schools.students.child', fn ($query) => $query->where('user_id', $request->user()->id))
                ->with(['days', 'schools', 'components'])
                ->orderBy('name')
                ->get(),
        ]);
    }

    public function updateOrderedMeal(Request $request, OrderItem $orderItem): RedirectResponse
    {
        $this->authorizeCustomer($request);
        abort_unless($orderItem->order()->where('user_id', $request->user()->id)->exists(), 403);
        abort_unless($orderItem->order()->where('order_status', 'paid')->exists(), 422, 'Only paid orders can be changed.');

        if (! $orderItem->student()->whereHas('school', fn ($query) => $query->where('is_active', true))->exists()) {
            throw ValidationException::withMessages([
                'order_item' => 'Meal changes are unavailable because this school is currently inactive.',
            ]);
        }

        $orderItem->loadMissing('student.school');
        $school = $orderItem->student?->school;
        abort_unless($school, 422, 'The student school could not be found.');
        $deadline = SchoolOrderingSchedule::deadlineForCollection($orderItem->scheduled_time, $school);
        if ($orderItem->scheduled_time->lt(now()->startOfDay()) || now()->gt($deadline)) {
            throw ValidationException::withMessages([
                'order_item' => 'This order can no longer be changed. '.ucfirst(SchoolOrderingSchedule::deadlineDay($school)).' is this school\'s deadline for the following week.',
            ]);
        }

        $validated = $request->validate([
            'menu_item_id' => ['required', 'integer', Rule::exists('menu_items', 'id')],
        ]);
        $replacement = MenuItem::query()
            ->whereKey($validated['menu_item_id'])
            ->where('is_available', true)
            ->whereHas('schools.students', fn ($query) => $query->whereKey($orderItem->student_id))
            ->whereHas('days', fn ($query) => $query->where('day_of_week', strtolower($orderItem->scheduled_time->format('l'))))
            ->where('available_start_time', $orderItem->menuItem?->available_start_time)
            ->where('available_end_time', $orderItem->menuItem?->available_end_time)
            ->get()
            ->first(fn (MenuItem $menuItem) => (float) $menuItem->price === (float) $orderItem->unit_price);

        if (! $replacement) {
            throw ValidationException::withMessages([
                'menu_item_id' => 'Choose a same-price meal with the same collection time.',
            ]);
        }

        $orderItem->update(['menu_item_id' => $replacement->id]);

        return back()->with('success', 'Ordered meal changed successfully. No additional payment was required.');
    }

    /** @return array<string, mixed> */
    private function validateSelection(Request $request): array
    {
        return $request->validate([
            'student_id' => ['required', 'integer', Rule::exists('students', 'id')],
            'menu_item_id' => ['required', 'integer', Rule::exists('menu_items', 'id')],
            'collection_date' => ['required', 'date', 'after_or_equal:today'],
            'quantity' => ['required', 'integer', 'min:1', 'max:20'],
        ]);
    }

    private function validateMealForChild(Request $request, int $studentId, int $menuItemId, Carbon $date): void
    {
        $child = Children::query()
            ->where('user_id', $request->user()->id)
            ->where('student_id', $studentId)
            ->whereHas('student.school', fn ($query) => $query->where('is_active', true))
            ->with('student.school')
            ->first();

        if (! $child) {
            throw ValidationException::withMessages([
                'student_id' => 'Meal ordering is unavailable because this student’s school is currently inactive.',
            ]);
        }

        [$weekStart, $weekEnd] = SchoolOrderingSchedule::orderingWindow($child->student->school);
        if ($date->lt($weekStart) || $date->gt($weekEnd)) {
            $deadlineDay = ucfirst(SchoolOrderingSchedule::deadlineDay($child->student->school));
            throw ValidationException::withMessages([
                'collection_date' => "Meals can only be ordered for the next eligible week (Monday to Sunday). {$deadlineDay} is this school's deadline.",
            ]);
        }

        $mealExists = MenuItem::query()
            ->whereKey($menuItemId)
            ->where('is_available', true)
            ->whereHas('schools.students', fn ($query) => $query->whereKey($studentId))
            ->whereHas('days', fn ($query) => $query->where('day_of_week', strtolower($date->format('l'))))
            ->exists();

        if (! $mealExists) {
            throw ValidationException::withMessages([
                'menu_item_id' => 'This meal is not available for the selected child on that date.',
            ]);
        }
    }

    private function authorizeCustomer(Request $request): void
    {
        abort_unless($request->user()?->hasRole('customer'), 403);
    }

    private function authorizeCartItem(Request $request, CartItem $cartItem): void
    {
        abort_unless($cartItem->cart()->where('user_id', $request->user()->id)->exists(), 403);
    }
}
