<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Integration\Support\IntegrationFixtures as F;

require_once __DIR__.'/Support/IntegrationFixtures.php';

test('IT03 allows an owner to manage an operator assigned to an inactive school', function () {
    $owner = F::user('owner');
    $school = F::school(['is_active' => false]);

    // Confirms that an operator can be created for an inactive school.
    $this->actingAs($owner)->post(route('owner.operators.store'), [
        'name' => 'Operator Integration',
        'username' => 'operator_integration',
        'phone_number' => '012-3456789',
        'password' => 'password',
        'password_confirmation' => 'password',
        'school_id' => $school->id,
    ])->assertRedirect();

    $operator = User::where('username', 'operator_integration')->firstOrFail();
    // Confirms that the operator information can be updated.
    $this->actingAs($owner)->patch(route('owner.operators.update', $operator), [
        'name' => 'Operator Updated',
        'username' => 'operator_updated',
        'phone_number' => '013-2224444',
        'school_id' => $school->id,
    ])->assertRedirect();
    // Confirms that the operator password can be reset.
    $this->actingAs($owner)->patch(route('owner.operators.password', $operator), [
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertRedirect();
    // Confirms that the operator account can be deactivated.
    $this->actingAs($owner)->patch(route('owner.operators.status', $operator))->assertRedirect();

    $operator->refresh();
    // Confirms the updated, normalised, inactive operator record and password.
    expect($operator->name)->toBe('Operator Updated')
        ->and($operator->phone_number)->toBe('0132224444')
        ->and($operator->is_active)->toBeFalse()
        ->and(Hash::check('new-password', $operator->password))->toBeTrue();
});
