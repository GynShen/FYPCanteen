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
                'name' => 'School A',
                'address' => '123 Main St',
            ],
            [
                'name' => 'School B',
                'address' => '456 Elm St',
            ],
            [
                'name' => 'School C',
                'address' => '789 Oak St',
            ],
        ];

        foreach ($schools as $school) {
            School::create($school);
        }
    }
}
