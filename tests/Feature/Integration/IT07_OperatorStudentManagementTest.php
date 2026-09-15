<?php

use App\Models\Student;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Feature\Integration\Support\IntegrationFixtures as F;

require_once __DIR__.'/Support/IntegrationFixtures.php';

test('IT07 restricts operator student management to the assigned school', function () {
    $assignedSchool = F::school();
    $otherSchool = F::school();
    $operator = F::user('operator', $assignedSchool);

    // Confirms that the operator can create a student in the assigned school.
    $this->actingAs($operator)->post(route('owner.student.store'), [
        'school_id' => $assignedSchool->id,
        'name' => 'Student Managed',
        'class' => '2 Bestari',
        'guardian_phone_number' => '012-3456789',
    ])->assertRedirect(route('owner.student'));

    $student = Student::where('name', 'Student Managed')->firstOrFail();
    // Confirms that the operator can update an assigned-school student.
    $this->actingAs($operator)->patch(route('owner.student.update', $student), [
        'school_id' => $assignedSchool->id,
        'name' => 'Student Updated',
        'class' => '3 Bestari',
        'guardian_phone_number' => '0123456789',
    ])->assertRedirect(route('owner.student'));

    // Confirms that another school's student cannot be created.
    $this->actingAs($operator)->post(route('owner.student.store'), [
        'school_id' => $otherSchool->id,
        'name' => 'Forbidden Student',
        'class' => '1 Amanah',
        'guardian_phone_number' => null,
    ])->assertForbidden();

    $assignedSchool->update(['is_active' => false]);
    $menu = F::menu($assignedSchool);

    // Confirms that student creation remains available for an inactive school.
    $this->actingAs($operator)->post(route('owner.student.store'), [
        'school_id' => $assignedSchool->id,
        'name' => 'Inactive School Student',
        'class' => '4 Bestari',
        'guardian_phone_number' => '0123456799',
    ])->assertRedirect(route('owner.student'));

    // Confirms that the operator can view the inactive school's weekly menu.
    $this->actingAs($operator)->get(route('weekly-menu.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Owner/WeeklyMenu')
            ->where('schools.0.id', $assignedSchool->id)
            ->where('menuItems.0.id', $menu->id));

    $owner = F::user('owner');
    // Confirms that the owner can view weekly menus for all schools.
    $this->actingAs($owner)->get(route('weekly-menu.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Owner/WeeklyMenu')
            ->has('schools', 2)
            ->where('menuItems.0.id', $menu->id));

    // Confirms that the student update was saved correctly.
    $this->assertDatabaseHas('students', ['id' => $student->id, 'name' => 'Student Updated', 'school_id' => $assignedSchool->id]);
    // Confirms that the inactive-school student was created.
    $this->assertDatabaseHas('students', ['name' => 'Inactive School Student', 'school_id' => $assignedSchool->id]);
    // Confirms that the forbidden student was not created.
    $this->assertDatabaseMissing('students', ['name' => 'Forbidden Student']);
});
