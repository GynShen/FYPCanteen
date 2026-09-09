<?php

use App\Models\MealCollection;
use App\Models\StudentMealEvent;
use App\Notifications\CustomerEventNotification;
use Carbon\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Integration\Support\IntegrationFixtures as F;

require_once __DIR__.'/Support/IntegrationFixtures.php';

test('IT23 displays customer dashboard totals and event notifications', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-18 10:30:00', 'Asia/Kuala_Lumpur'));
    $customer = F::user('customer');
    $school = F::school();
    $student = F::student($school);
    F::child($customer, $student);
    $menu = F::menu($school, ['name' => 'Dashboard Meal'], ['tuesday']);
    [, $item] = F::orderItem($customer, $student, $menu, now(), 1);
    $collection = MealCollection::create([
        'order_item_id' => $item->id,
        'collected_quantity' => 1,
        'status' => 'collected',
        'collected_at' => now(),
    ]);
    StudentMealEvent::create([
        'student_id' => $student->id,
        'order_item_id' => $item->id,
        'meal_collection_id' => $collection->id,
        'event_type' => 'still_hungry',
        'reported_at' => now(),
    ]);
    foreach (['payment_completed', 'meal_received', 'still_hungry'] as $event) {
        $customer->notify(new CustomerEventNotification([
            'event' => $event,
            'title' => str_replace('_', ' ', ucfirst($event)),
            'message' => 'Integration notification for '.$event,
        ]));
    }

    $this->actingAs($customer)->get(route('dashboard').'?month=2026-08')
        ->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Dashboard')
        ->where('report.type', 'customer')
        ->where('report.ordered', 1)
        ->where('report.collected', 1)
        ->has('report.still_hungry', 1));

    $this->actingAs($customer)->get(route('customer.notifications'))
        ->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Customer/Notifications')
        ->has('notifications.data', 3));

    $notification = $customer->notifications()->firstOrFail();
    $this->actingAs($customer)->patch(route('customer.notifications.read', $notification))->assertRedirect();
    expect($notification->fresh()->read_at)->not->toBeNull();
});
