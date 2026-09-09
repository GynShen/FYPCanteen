<?php

use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Integration\Support\IntegrationFixtures as F;

require_once __DIR__.'/Support/IntegrationFixtures.php';

test('IT18 returns only available meals assigned to a linked child school', function () {
    $customer = F::user('customer');
    $school = F::school();
    $otherSchool = F::school();
    $student = F::student($school);
    F::child($customer, $student);
    $availableMeal = F::menu($school, ['name' => 'Available School Meal']);
    F::menu($school, ['name' => 'Unavailable School Meal', 'is_available' => false]);
    F::menu($otherSchool, ['name' => 'Other School Meal']);

    $this->actingAs($customer)->get(route('customer.menu'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Customer/Menu')
            ->has('children', 1)
            ->has('menuItems', 1)
            ->where('menuItems.0.id', $availableMeal->id)
            ->where('menuItems.0.name', 'Available School Meal'));
});
