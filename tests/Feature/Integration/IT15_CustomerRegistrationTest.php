<?php

test('IT15 registers and authenticates a customer with valid information', function () {
    $this->post('/register', [
        'name' => 'Customer Integration',
        'email' => 'customer.integration@example.test',
        'phone_number' => '012-3456789',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    $this->assertAuthenticated();
    $this->assertDatabaseHas('users', [
        'email' => 'customer.integration@example.test',
        'phone_number' => '0123456789',
        'role' => 'customer',
    ]);
});
