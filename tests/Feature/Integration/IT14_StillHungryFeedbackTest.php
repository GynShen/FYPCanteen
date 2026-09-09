<?php

use App\Models\MealCollection;
use App\Notifications\CustomerEventNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Integration\Support\IntegrationFixtures as F;

require_once __DIR__.'/Support/IntegrationFixtures.php';

test('IT14 accepts still-hungry feedback only once after full collection', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-18 10:30:00', 'Asia/Kuala_Lumpur'));
    Notification::fake();
    $school = F::school();
    $operator = F::user('operator', $school);
    $customer = F::user('customer');
    $student = F::student($school);
    F::child($customer, $student);
    $menu = F::menu($school, [], ['tuesday']);
    [, $item] = F::orderItem($customer, $student, $menu, now(), 1);

    $this->actingAs($operator)->postJson(route('meal-collection.still-hungry', $item))->assertUnprocessable();

    MealCollection::create([
        'order_item_id' => $item->id,
        'collected_quantity' => 1,
        'status' => 'collected',
        'collected_at' => now(),
        'collected_by' => $operator->id,
    ]);

    $this->actingAs($operator)->postJson(route('meal-collection.still-hungry', $item))
        ->assertOk()->assertJsonPath('feedback_sent', true);
    $this->actingAs($operator)->postJson(route('meal-collection.still-hungry', $item))->assertUnprocessable();

    $this->assertDatabaseHas('student_meal_events', ['order_item_id' => $item->id, 'event_type' => 'still_hungry']);
    Notification::assertSentTo($customer, CustomerEventNotification::class);
});
