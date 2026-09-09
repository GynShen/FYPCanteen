<?php

use App\Models\Student;
use Tests\Feature\Integration\Support\IntegrationFixtures as F;

require_once __DIR__.'/Support/IntegrationFixtures.php';

test('IT07 restricts operator student management to the assigned school', function () {
    $assignedSchool = F::school();
    $otherSchool = F::school();
    $operator = F::user('operator', $assignedSchool);

    $this->actingAs($operator)->post(route('owner.student.store'), [
        'school_id' => $assignedSchool->id,
        'name' => 'Student Managed',
        'class' => '2 Bestari',
        'guardian_phone_number' => '012-3456789',
    ])->assertRedirect(route('owner.student'));

    $student = Student::where('name', 'Student Managed')->firstOrFail();
    $this->actingAs($operator)->patch(route('owner.student.update', $student), [
        'school_id' => $assignedSchool->id,
        'name' => 'Student Updated',
        'class' => '3 Bestari',
        'guardian_phone_number' => '0123456789',
    ])->assertRedirect(route('owner.student'));

    $this->actingAs($operator)->post(route('owner.student.store'), [
        'school_id' => $otherSchool->id,
        'name' => 'Forbidden Student',
        'class' => '1 Amanah',
        'guardian_phone_number' => null,
    ])->assertForbidden();

    $this->assertDatabaseHas('students', ['id' => $student->id, 'name' => 'Student Updated', 'school_id' => $assignedSchool->id]);
    $this->assertDatabaseMissing('students', ['name' => 'Forbidden Student']);
});
