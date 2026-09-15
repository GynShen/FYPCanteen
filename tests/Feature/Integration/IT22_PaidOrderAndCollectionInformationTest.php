<?php

use App\Models\MealCollection;
use Carbon\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Integration\Support\IntegrationFixtures as F;

require_once __DIR__.'/Support/IntegrationFixtures.php';

test('IT22 displays paid-order and collection information for the customer', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-18 10:30:00', 'Asia/Kuala_Lumpur'));
    $customer = F::user('customer');
    $school = F::school();
    $student = F::student($school);
    F::child($customer, $student);
    $menu = F::menu($school, ['name' => 'Collected Meal'], ['tuesday']);
    [$order, $item] = F::orderItem($customer, $student, $menu, now(), 2);
    MealCollection::create([
        'order_item_id' => $item->id,
        'collected_quantity' => 1,
        'status' => 'partially_collected',
        'collected_at' => now(),
    ]);

    // Confirms that the paid order is displayed on the orders page.
    $this->actingAs($customer)->get(route('customer.orders'))
        ->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Customer/Orders')
        ->has('orders', 1)
        ->where('orders.0.id', $order->id)
        ->where('orders.0.order_status', 'paid'));

    // Confirms the customer's ordered, collected and upcoming totals.
    $this->actingAs($customer)->get(route('dashboard').'?month=2026-08')
        ->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('report.type', 'customer')
        ->where('report.ordered', 2)
        ->where('report.collected', 1)
        ->where('report.upcoming_count', 1));
});
