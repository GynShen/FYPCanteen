<?php

use App\Models\MealCollection;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Integration\Support\IntegrationFixtures as F;

require_once __DIR__.'/Support/IntegrationFixtures.php';

test('IT13 identifies late collection and missed remaining quantities', function () {
    Notification::fake();
    Carbon::setTestNow(Carbon::parse('2026-08-18 11:10:00', 'Asia/Kuala_Lumpur'));
    $school = F::school();
    $operator = F::user('operator', $school);
    $customer = F::user('customer');
    $lateStudent = F::student($school);
    $missedStudent = F::student($school);
    $menu = F::menu($school, [], ['tuesday']);
    [, $lateItem] = F::orderItem($customer, $lateStudent, $menu, now()->startOfDay(), 1);
    F::orderItem($customer, $missedStudent, $menu, now()->startOfDay(), 2);

    $this->actingAs($operator)->postJson(route('meal-collection.scan'), [
        'qr_code' => $lateStudent->qr_code,
    ])->assertOk()->assertJsonPath('items.0.status', 'late_collected');

    expect(MealCollection::where('order_item_id', $lateItem->id)->value('is_late_collection'))->toBeTrue();

    Carbon::setTestNow(Carbon::parse('2026-08-18 11:16:00', 'Asia/Kuala_Lumpur'));
    $this->actingAs($operator)->postJson(route('meal-collection.scan'), [
        'qr_code' => $missedStudent->qr_code,
    ])->assertOk()
        ->assertJsonPath('items.0.status', 'missed')
        ->assertJsonPath('items.0.remaining_quantity', 2);
});
