<?php

use Carbon\Carbon;
use Tests\Feature\Integration\Support\IntegrationFixtures as F;

require_once __DIR__.'/Support/IntegrationFixtures.php';

test('IT21 replaces an eligible paid meal while preserving the paid order details', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-11 12:00:00', 'Asia/Kuala_Lumpur'));
    $customer = F::user('customer');
    $school = F::school(['ordering_deadline_day' => 'wednesday']);
    $student = F::student($school);
    F::child($customer, $student);
    $original = F::menu($school, ['name' => 'Original Meal', 'price' => 4.50], ['monday']);
    $replacement = F::menu($school, [
        'name' => 'Replacement Meal',
        'price' => 4.50,
        'available_start_time' => $original->available_start_time,
        'available_end_time' => $original->available_end_time,
    ], ['monday']);
    [, $item] = F::orderItem($customer, $student, $original, '2026-08-17 00:00:00', 2);
    $originalDetails = $item->only(['student_id', 'scheduled_time', 'quantity', 'unit_price']);

    // Confirms that an eligible paid meal can be replaced.
    $this->actingAs($customer)->patch(route('customer.orders.items.update', $item), [
        'menu_item_id' => $replacement->id,
    ])->assertRedirect()->assertSessionHasNoErrors();

    $item->refresh();
    // Confirms the replacement while preserving all paid-order details.
    expect($item->menu_item_id)->toBe($replacement->id)
        ->and($item->student_id)->toBe($originalDetails['student_id'])
        ->and($item->scheduled_time->toDateTimeString())->toBe($originalDetails['scheduled_time']->toDateTimeString())
        ->and($item->quantity)->toBe($originalDetails['quantity'])
        ->and($item->unit_price)->toBe($originalDetails['unit_price']);

    Carbon::setTestNow(Carbon::parse('2026-08-13 00:00:00', 'Asia/Kuala_Lumpur'));
    // Confirms that replacement is rejected after the deadline.
    $this->actingAs($customer)->from(route('customer.orders'))->patch(route('customer.orders.items.update', $item), [
        'menu_item_id' => $original->id,
    ])->assertRedirect(route('customer.orders'))->assertSessionHasErrors('order_item');
});
