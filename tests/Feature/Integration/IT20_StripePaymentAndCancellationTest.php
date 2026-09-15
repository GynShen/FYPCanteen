<?php

use App\Models\CartItem;
use Carbon\Carbon;
use Tests\Feature\Integration\Support\IntegrationFixtures as F;

require_once __DIR__.'/Support/IntegrationFixtures.php';

test('IT20 confirms a verified Stripe webhook and preserves the cart after cancellation', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-18 09:00:00', 'Asia/Kuala_Lumpur'));
    config([
        'services.stripe.webhook_secret' => 'whsec_integration_test',
        'services.stripe.currency' => 'myr',
    ]);
    $customer = F::user('customer');
    $school = F::school();
    $student = F::student($school);
    F::child($customer, $student);
    $menu = F::menu($school, [], ['monday']);
    [$order, $item] = F::orderItem($customer, $student, $menu, '2026-08-24 00:00:00', 1, 'pending');
    $cartItem = F::cartItem($customer, $student, $menu, $item->scheduled_time, 1);

    $payload = json_encode([
        'id' => 'evt_it20',
        'object' => 'event',
        'type' => 'checkout.session.completed',
        'data' => ['object' => [
            'id' => 'cs_test_it20',
            'object' => 'checkout.session',
            'payment_status' => 'paid',
            'payment_intent' => 'pi_test_it20',
            'amount_total' => 450,
            'currency' => 'myr',
            'metadata' => ['order_id' => (string) $order->id, 'user_id' => (string) $customer->id],
        ]],
    ], JSON_THROW_ON_ERROR);
    $timestamp = time();
    $signature = hash_hmac('sha256', $timestamp.'.'.$payload, 'whsec_integration_test');

    // Confirms that the signed Stripe webhook is accepted.
    $this->call('POST', route('stripe.webhook'), [], [], [], [
        'CONTENT_TYPE' => 'application/json',
        'HTTP_STRIPE_SIGNATURE' => 't='.$timestamp.',v1='.$signature,
    ], $payload)->assertOk();

    // Confirms that the order is marked as paid.
    $this->assertDatabaseHas('orders', ['id' => $order->id, 'order_status' => 'paid']);
    // Confirms that the verified Stripe payment is stored.
    $this->assertDatabaseHas('payments', ['order_id' => $order->id, 'status' => 'paid', 'stripe_session_id' => 'cs_test_it20']);
    // Confirms that paid cart selections are removed.
    $this->assertDatabaseMissing('cart_items', ['id' => $cartItem->id]);
    // Confirms that payment completion creates a notification.
    expect($customer->notifications()->where('data->event', 'payment_completed')->exists())->toBeTrue();

    [$cancelledOrder] = F::orderItem($customer, $student, $menu, '2026-08-25 00:00:00', 1, 'pending');
    $cancelledCartItem = F::cartItem($customer, $student, $menu, '2026-08-25 00:00:00', 1);
    // Confirms that cancelling checkout returns to the menu.
    $this->actingAs($customer)->get(route('customer.checkout.cancel', $cancelledOrder))
        ->assertRedirect(route('customer.menu'));

    // Confirms that the cancelled order status is stored.
    $this->assertDatabaseHas('orders', ['id' => $cancelledOrder->id, 'order_status' => 'cancelled']);
    // Confirms that the cancelled payment status is stored.
    $this->assertDatabaseHas('payments', ['order_id' => $cancelledOrder->id, 'status' => 'cancelled']);
    // Confirms that cancellation preserves the cart selection.
    expect(CartItem::find($cancelledCartItem->id))->not->toBeNull();
});
