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
    $sameDayMenu = F::menu($school, ['name' => 'Same Day Meal'], ['tuesday']);
    $laterMenu = F::menu($school, ['name' => 'Later Meal'], ['wednesday']);
    $order->orderItems()->create([
        'student_id' => $student->id,
        'menu_item_id' => $sameDayMenu->id,
        'unit_price' => $sameDayMenu->price,
        'scheduled_time' => now()->addMinutes(15),
        'quantity' => 1,
    ]);
    $order->orderItems()->create([
        'student_id' => $student->id,
        'menu_item_id' => $laterMenu->id,
        'unit_price' => $laterMenu->price,
        'scheduled_time' => now()->addDay(),
        'quantity' => 1,
    ]);
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

    // Confirms the totals and all meals on the nearest upcoming date.
    $this->actingAs($customer)->get(route('dashboard').'?month=2026-08')
        ->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('report.type', 'customer')
        ->where('report.ordered', 4)
        ->where('report.collected', 1)
        ->where('report.upcoming_count', 3)
        ->has('report.upcoming_meals', 2)
        ->where('report.upcoming_meals.0.meal_name', 'Collected Meal')
        ->where('report.upcoming_meals.1.meal_name', 'Same Day Meal'));
});
