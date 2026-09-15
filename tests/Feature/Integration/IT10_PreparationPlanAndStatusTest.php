<?php

use Carbon\Carbon;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Integration\Support\IntegrationFixtures as F;

require_once __DIR__.'/Support/IntegrationFixtures.php';

test('IT10 groups paid quantities and allows an operator to update preparation status', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-18 09:00:00', 'Asia/Kuala_Lumpur'));
    $school = F::school();
    $operator = F::user('operator', $school);
    $customer = F::user('customer');
    $firstStudent = F::student($school);
    $secondStudent = F::student($school);
    $menu = F::menu($school, [], ['thursday']);
    F::orderItem($customer, $firstStudent, $menu, '2026-08-20 00:00:00', 2);
    F::orderItem($customer, $secondStudent, $menu, '2026-08-20 00:00:00', 3);

    // Confirms that paid orders are grouped into one preparation batch.
    $this->actingAs($operator)->get(route('operator.preparation-plan', [
        'week' => 'custom',
        'start_date' => '2026-08-20',
        'end_date' => '2026-08-20',
    ]))->assertOk()->assertInertia(fn (Assert $page) => $page
        ->component('Operator/PreparationPlan')
        ->has('meals', 1)
        ->where('meals.0.total', 5)
        ->where('meals.0.students', 2));

    // Confirms successful preparation-status update and redirection.
    $this->actingAs($operator)->patch(route('dashboard.meal-preparation'), [
        'menu_item_id' => $menu->id,
        'preparation_date' => '2026-08-20',
    ])->assertRedirect();

    // Confirms that the completed preparation is stored with its operator.
    $this->assertDatabaseHas('meal_preparations', [
        'school_id' => $school->id,
        'menu_item_id' => $menu->id,
        'preparation_date' => '2026-08-20 00:00:00',
        'completed_by' => $operator->id,
    ]);
});
