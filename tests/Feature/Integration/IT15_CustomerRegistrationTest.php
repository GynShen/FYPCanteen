<?php

test('IT15 registers and authenticates a customer with valid information', function () {
    // Confirms successful registration and dashboard redirection.
    $this->post('/register', [
        'name' => 'Customer Integration',
        'email' => 'customer.integration@example.test',
        'phone_number' => '012-3456789',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    // Confirms that the registered customer is authenticated.
    $this->assertAuthenticated();
    // Confirms that the normalised customer account is stored.
    $this->assertDatabaseHas('users', [
        'email' => 'customer.integration@example.test',
        'phone_number' => '0123456789',
        'role' => 'customer',
    ]);
});
