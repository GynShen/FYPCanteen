<?php

use Tests\Feature\Integration\Support\IntegrationFixtures as F;

require_once __DIR__.'/Support/IntegrationFixtures.php';

test('IT01 authenticates each role and restricts access to owner functions', function () {
    $school = F::school();
    $owner = F::user('owner');
    $operator = F::user('operator', $school);
    $customer = F::user('customer');

    $this->post('/login', ['login' => $owner->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($owner);
    $this->post('/logout');

    $this->post('/login', ['login' => $operator->username, 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($operator);
    $this->get(route('owner.school'))->assertForbidden();
    $this->post('/logout');

    $this->post('/login', ['login' => $customer->email, 'password' => 'password'])
        ->assertRedirect(route('dashboard', absolute: false));
    $this->assertAuthenticatedAs($customer);
    $this->get(route('owner.school'))->assertForbidden();
});
