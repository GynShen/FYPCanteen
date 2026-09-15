<?php

use App\Models\MealCollection;
use Carbon\Carbon;
use Illuminate\Support\Facades\Notification;
use Tests\Feature\Integration\Support\IntegrationFixtures as F;

require_once __DIR__.'/Support/IntegrationFixtures.php';

test('IT12 rejects cross-school scanning and prevents duplicate collection', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-18 10:30:00', 'Asia/Kuala_Lumpur'));
    Notification::fake();
    $school = F::school();
    $otherSchool = F::school();
    $operator = F::user('operator', $school);
    $otherOperator = F::user('operator', $otherSchool);
    $customer = F::user('customer');
    $student = F::student($school);
    $menu = F::menu($school, [], ['tuesday']);
    [, $item] = F::orderItem($customer, $student, $menu, now(), 1);

    // Confirms that an operator cannot scan another school's student.
    $this->actingAs($otherOperator)->postJson(route('meal-collection.scan'), [
        'qr_code' => $student->qr_code,
    ])->assertForbidden();

    // Confirms that the first valid scan collects one meal.
    $this->actingAs($operator)->postJson(route('meal-collection.scan'), [
        'qr_code' => $student->qr_code,
    ])->assertOk()->assertJsonPath('collected_quantity', 1);
    // Confirms that a repeated scan collects no additional quantity.
    $this->actingAs($operator)->postJson(route('meal-collection.scan'), [
        'qr_code' => $student->qr_code,
    ])->assertOk()->assertJsonPath('collected_quantity', 0);

    // Confirms that only one collected quantity is stored.
    expect(MealCollection::where('order_item_id', $item->id)->value('collected_quantity'))->toBe(1);
});
