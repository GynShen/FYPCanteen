<?php

use Tests\Feature\Integration\Support\IntegrationFixtures as F;

require_once __DIR__.'/Support/IntegrationFixtures.php';

test('IT16 links unlinked students when the customer opens the children page', function () {
    $school = F::school();
    $customer = F::user('customer', null, ['phone_number' => '0123456789']);
    $localStudent = F::student($school, ['guardian_phone_number' => '012-3456789']);
    $internationalStudent = F::student($school, ['guardian_phone_number' => '+60123456789']);
    F::student($school, ['guardian_phone_number' => '0132224444']);

    $this->actingAs($customer)->get(route('customer.children'))->assertOk();
    $this->actingAs($customer)->get(route('customer.children'))->assertOk();

    $this->assertDatabaseHas('children', ['user_id' => $customer->id, 'student_id' => $localStudent->id]);
    $this->assertDatabaseHas('children', ['user_id' => $customer->id, 'student_id' => $internationalStudent->id]);
    expect($customer->children()->count())->toBe(2);
});
