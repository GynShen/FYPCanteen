<?php

namespace Database\Seeders;

use App\Models\School;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class OperatorSeeder extends Seeder
{
    /**
     * Seed one demonstration operator for each school.
     */
    public function run(): void
    {
        $operators = [
            [
                'school_name' => 'SJK(C) Bandar Sungai Long',
                'name' => 'Operator(Sungai Long)',
                'username' => 'operator_sungai_long',
                'phone_number' => '0123456790',
            ],
            [
                'school_name' => 'SJK (C) Batu 11 Cheras',
                'name' => 'Operator(Batu 11 Cheras)',
                'username' => 'operator_batu_11',
                'phone_number' => '0123456791',
            ],
        ];

        foreach ($operators as $operator) {
            $school = School::where('name', $operator['school_name'])->firstOrFail();

            User::updateOrCreate(
                ['username' => $operator['username']],
                [
                    'name' => $operator['name'],
                    'email' => null,
                    'password' => Hash::make('password'),
                    'phone_number' => $operator['phone_number'],
                    'role' => 'operator',
                    'school_id' => $school->id,
                    'is_active' => true,
                ],
            );
        }
    }
}
