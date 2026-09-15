<?php

use App\Models\MealCollection;
use App\Notifications\CustomerEventNotification;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Integration\Support\IntegrationFixtures as F;

require_once __DIR__.'/Support/IntegrationFixtures.php';

test('IT11 scans a valid QR code and records an eligible paid meal collection', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-18 10:30:00', 'Asia/Kuala_Lumpur'));
    Notification::fake();
    $school = F::school();
    $operator = F::user('operator', $school);
    $customer = F::user('customer');
    $student = F::student($school);
    F::child($customer, $student);
    $menu = F::menu($school, [], ['tuesday']);
    [, $item] = F::orderItem($customer, $student, $menu, now(), 2);

    // Confirms that scanning collects the eligible quantity for the student.
    $this->actingAs($operator)->postJson(route('meal-collection.scan'), [
        'qr_code' => $student->qr_code,
    ])->assertOk()
        ->assertJsonPath('student.id', $student->id)
        ->assertJsonPath('collected_quantity', 2)
        ->assertJsonPath('items.0.status', 'collected');

    $collection = MealCollection::where('order_item_id', $item->id)->firstOrFail();
    // Confirms the stored quantity, operator and completed status.
    expect($collection->collected_quantity)->toBe(2)
        ->and($collection->collected_by)->toBe($operator->id)
        ->and($collection->status)->toBe('collected');
    // Confirms that the parent receives a collection notification.
    Notification::assertSentTo($customer, CustomerEventNotification::class);
});
