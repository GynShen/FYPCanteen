<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\School;

class SchoolSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $schools = [
            [
                'school_name' => 'School A',
                'school_address' => '123 Main St',
            ],
            [
                'school_name' => 'School B',
                'school_address' => '456 Elm St',
            ],
            [
                'school_name' => 'School C',
                'school_address' => '789 Oak St',
            ],
        ];

        foreach ($schools as $school) {
            School::create($school);
        }
    }
}
