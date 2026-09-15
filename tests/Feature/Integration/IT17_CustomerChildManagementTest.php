<?php

use App\Models\Children;
use Tests\Feature\Integration\Support\IntegrationFixtures as F;

require_once __DIR__.'/Support/IntegrationFixtures.php';

test('IT17 allows customers to manage only their own child links', function () {
    $school = F::school();
    $customer = F::user('customer');
    $otherCustomer = F::user('customer');
    $otherStudent = F::student($school);
    $otherChild = F::child($otherCustomer, $otherStudent);

    // Confirms that another customer's child cannot be updated.
    $this->actingAs($customer)->patch(route('customer.children.update', $otherChild), [
        'name' => 'Forbidden Update',
        'class' => '2 Amanah',
        'school_id' => $school->id,
    ])->assertForbidden();

    $school->update(['is_active' => false]);

    // Confirms that a child can be added for an inactive school.
    $this->actingAs($customer)->post(route('customer.children.store'), [
        'name' => 'Customer Child',
        'class' => '1 Bestari',
        'school_id' => $school->id,
    ])->assertRedirect();

    $child = Children::where('user_id', $customer->id)->firstOrFail();
    $studentId = $child->student_id;
    // Confirms that the customer's own child can be updated.
    $this->actingAs($customer)->patch(route('customer.children.update', $child), [
        'name' => 'Customer Child Updated',
        'class' => '2 Bestari',
        'school_id' => $school->id,
    ])->assertRedirect();
    // Confirms that the child's updated name is stored.
    $this->assertDatabaseHas('students', ['id' => $studentId, 'name' => 'Customer Child Updated']);

    // Confirms successful removal of the child link.
    $this->actingAs($customer)->delete(route('customer.children.destroy', $child))->assertRedirect();
    // Confirms that the child link is deleted.
    $this->assertDatabaseMissing('children', ['id' => $child->id]);
    // Confirms that the student remains without a guardian phone link.
    $this->assertDatabaseHas('students', ['id' => $studentId, 'guardian_phone_number' => null]);
});
