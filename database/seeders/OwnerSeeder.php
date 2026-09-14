<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OwnerSeeder extends Seeder
{
    /**
     * Seed the demonstration canteen-owner account.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'owner@example.com'],
            [
                'name' => 'Canteen Owner',
                'username' => null,
                'password' => Hash::make('password'),
                'phone_number' => '0123456789',
                'role' => 'owner',
                'school_id' => null,
                'is_active' => true,
            ],
        );
    }
}
