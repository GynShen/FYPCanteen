<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Tests\Feature\Integration\Support\IntegrationFixtures as F;

require_once __DIR__.'/Support/IntegrationFixtures.php';

test('IT03 allows an owner to manage an operator assigned to an active school', function () {
    $owner = F::user('owner');
    $school = F::school();

    $this->actingAs($owner)->post(route('owner.operators.store'), [
        'name' => 'Operator Integration',
        'username' => 'operator_integration',
        'phone_number' => '012-3456789',
        'password' => 'password',
        'password_confirmation' => 'password',
        'school_id' => $school->id,
    ])->assertRedirect();

    $operator = User::where('username', 'operator_integration')->firstOrFail();
    $this->actingAs($owner)->patch(route('owner.operators.update', $operator), [
        'name' => 'Operator Updated',
        'username' => 'operator_updated',
        'phone_number' => '013-2224444',
        'school_id' => $school->id,
    ])->assertRedirect();
    $this->actingAs($owner)->patch(route('owner.operators.password', $operator), [
        'password' => 'new-password',
        'password_confirmation' => 'new-password',
    ])->assertRedirect();
    $this->actingAs($owner)->patch(route('owner.operators.status', $operator))->assertRedirect();

    $operator->refresh();
    expect($operator->name)->toBe('Operator Updated')
        ->and($operator->phone_number)->toBe('0132224444')
        ->and($operator->is_active)->toBeFalse()
        ->and(Hash::check('new-password', $operator->password))->toBeTrue();
});
