<?php

use App\Models\School;
use Tests\Feature\Integration\Support\IntegrationFixtures as F;

require_once __DIR__.'/Support/IntegrationFixtures.php';

test('IT02 allows an owner to create update and deactivate a school', function () {
    $owner = F::user('owner');

    // Confirms that school creation returns to the management page.
    $this->actingAs($owner)->post(route('owner.school.store'), [
        'name' => 'School Integration',
        'address' => '123 Test Road',
        'ordering_deadline_day' => 'monday',
    ])->assertRedirect(route('owner.school'));

    $school = School::where('name', 'School Integration')->firstOrFail();
    // Confirms that the school can be updated successfully.
    $this->actingAs($owner)->patch(route('owner.school.update', $school), [
        'name' => 'School Integration Updated',
        'address' => '456 Test Road',
        'ordering_deadline_day' => 'thursday',
    ])->assertRedirect(route('owner.school'));
    // Confirms that the school status can be changed.
    $this->actingAs($owner)->patch(route('owner.school.status', $school))->assertRedirect();

    // Confirms that the updated inactive school is stored correctly.
    $this->assertDatabaseHas('schools', [
        'id' => $school->id,
        'name' => 'School Integration Updated',
        'ordering_deadline_day' => 'thursday',
        'is_active' => false,
    ]);
});
