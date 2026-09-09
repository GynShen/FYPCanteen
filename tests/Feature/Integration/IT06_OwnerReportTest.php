<?php

use App\Models\MealCollection;
use App\Models\StudentMealEvent;
use Carbon\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Integration\Support\IntegrationFixtures as F;

require_once __DIR__.'/Support/IntegrationFixtures.php';

test('IT06 calculates owner report totals from paid operational records', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-18 10:30:00', 'Asia/Kuala_Lumpur'));
    $owner = F::user('owner');
    $customer = F::user('customer');
    $school = F::school();
    $student = F::student($school);
    $menu = F::menu($school, [], ['tuesday']);
    [, $item] = F::orderItem($customer, $student, $menu, now(), 2);
    $collection = MealCollection::create([
        'order_item_id' => $item->id,
        'collected_quantity' => 1,
        'status' => 'partially_collected',
        'collected_at' => now(),
    ]);
    StudentMealEvent::create([
        'student_id' => $student->id,
        'order_item_id' => $item->id,
        'meal_collection_id' => $collection->id,
        'event_type' => 'still_hungry',
        'reported_at' => now(),
    ]);

    $this->actingAs($owner)->get(route('dashboard').'?period=today')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('report.type', 'owner')
            ->where('report.revenue', 9)
            ->where('report.ordered', 2)
            ->where('report.collected', 1)
            ->has('report.still_hungry', 1));
});
