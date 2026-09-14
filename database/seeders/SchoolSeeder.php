<?php

namespace Database\Seeders;

use App\Models\School;
use Illuminate\Database\Seeder;

class SchoolSeeder extends Seeder
{
    /**
     * Seed the schools used for demonstration and student-data imports.
     */
    public function run(): void
    {
        $schools = [
            [
                'name' => 'SJK(C) Bandar Sungai Long',
                'address' => 'Bandar Sungai Long, 43200 Kajang, Selangor',
                'ordering_deadline_day' => 'saturday',
                'is_active' => true,
            ],
            [
                'name' => 'SJK (C) Batu 11 Cheras',
                'address' => 'No. 1, Cheras, 43200, Kajang, Selangor, 43200',
                'ordering_deadline_day' => 'saturday',
                'is_active' => true,
            ],
        ];

        foreach ($schools as $school) {
            School::updateOrCreate(
                ['name' => $school['name']],
                $school,
            );
        }
    }
}
