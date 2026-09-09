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

    $this->actingAs($customer)->patch(route('customer.children.update', $otherChild), [
        'name' => 'Forbidden Update',
        'class' => '2 Amanah',
        'school_id' => $school->id,
    ])->assertForbidden();

    $this->actingAs($customer)->post(route('customer.children.store'), [
        'name' => 'Customer Child',
        'class' => '1 Bestari',
        'school_id' => $school->id,
    ])->assertRedirect();

    $child = Children::where('user_id', $customer->id)->firstOrFail();
    $studentId = $child->student_id;
    $this->actingAs($customer)->patch(route('customer.children.update', $child), [
        'name' => 'Customer Child Updated',
        'class' => '2 Bestari',
        'school_id' => $school->id,
    ])->assertRedirect();
    $this->assertDatabaseHas('students', ['id' => $studentId, 'name' => 'Customer Child Updated']);

    $this->actingAs($customer)->delete(route('customer.children.destroy', $child))->assertRedirect();
    $this->assertDatabaseMissing('children', ['id' => $child->id]);
    $this->assertDatabaseHas('students', ['id' => $studentId, 'guardian_phone_number' => null]);
});
