<?php

use App\Models\User;

test('UT01 returns true only when the user role matches the required role', function () {
    $user = new User(['role' => 'operator']);

    expect($user->hasRole('operator'))->toBeTrue()
        ->and($user->hasRole('owner'))->toBeFalse()
        ->and($user->hasRole('customer'))->toBeFalse();
});
