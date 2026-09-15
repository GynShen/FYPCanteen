<?php

use Tests\Feature\Integration\Support\IntegrationFixtures as F;

require_once __DIR__.'/Support/IntegrationFixtures.php';

test('IT08 removes the previous customer link when an operator changes the guardian phone', function () {
    $school = F::school();
    $operator = F::user('operator', $school);
    $customer = F::user('customer', null, ['phone_number' => '0123456789']);
    $student = F::student($school, ['guardian_phone_number' => '0123456789']);
    $child = F::child($customer, $student);

    // Confirms successful guardian-phone update and redirection.
    $this->actingAs($operator)->patch(route('owner.student.update', $student), [
        'school_id' => $school->id,
        'name' => $student->name,
        'class' => $student->class,
        'guardian_phone_number' => '013-2224444',
    ])->assertRedirect(route('owner.student'));

    // Confirms that the previous parent-child link is removed.
    $this->assertDatabaseMissing('children', ['id' => $child->id]);
    // Confirms that the new normalised guardian phone is stored.
    $this->assertDatabaseHas('students', ['id' => $student->id, 'guardian_phone_number' => '0132224444']);
});
