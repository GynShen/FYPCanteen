<?php

use Tests\Feature\Integration\Support\IntegrationFixtures as F;

require_once __DIR__.'/Support/IntegrationFixtures.php';

test('IT01 authenticates each role and restricts access to owner functions', function () {
    $school = F::school();
    $owner = F::user('owner');
    $operator = F::user('operator', $school);
    $customer = F::user('customer');

    // Confirms successful owner login and dashboard redirection.
    $this->post('/login', ['login' => $owner->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));
    // Confirms that the owner becomes authenticated.
    $this->assertAuthenticatedAs($owner);
    $this->post('/logout');

    // Confirms successful operator login and dashboard redirection.
    $this->post('/login', ['login' => $operator->username, 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));
    // Confirms that the operator becomes authenticated.
    $this->assertAuthenticatedAs($operator);
    // Confirms that the operator cannot access owner-only functions.
    $this->get(route('owner.school'))->assertForbidden();
    $this->post('/logout');

    // Confirms successful customer login and dashboard redirection.
    $this->post('/login', ['login' => $customer->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));
    // Confirms that the customer becomes authenticated.
    $this->assertAuthenticatedAs($customer);
    // Confirms that the customer cannot access owner-only functions.
    $this->get(route('owner.school'))->assertForbidden();
});
