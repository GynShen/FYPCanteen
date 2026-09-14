<?php

namespace Database\Seeders;

use App\Models\Children;
use App\Models\School;
use App\Models\Student;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use RuntimeException;

class UnlinkedStudentsSeeder extends Seeder
{
    /**
     * Seed two unlinked siblings in each school for phone-matching demos.
     */
    public function run(): void
    {
        DB::transaction(function (): void {
            foreach ($this->groups() as $group) {
                $school = School::query()->where('name', $group['school_name'])->first();

                if (! $school) {
                    throw new RuntimeException('Run SchoolSeeder before UnlinkedStudentsSeeder.');
                }

                foreach ($group['students'] as [$name, $class]) {
                    $student = Student::query()->updateOrCreate(
                        ['qr_code' => 'student-qr-'.Str::slug($name)],
                        [
                            'school_id' => $school->id,
                            'name' => $name,
                            'class' => $class,
                            'guardian_phone_number' => $group['guardian_phone_number'],
                        ],
                    );

                    // Keep these exact demonstration students unlinked until the
                    // matching parent visits the Children Management page.
                    Children::query()->where('student_id', $student->id)->delete();
                }
            }
        });
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function groups(): array
    {
        return [
            [
                'school_name' => 'SJK(C) Bandar Sungai Long',
                'guardian_phone_number' => '0123456820',
                'students' => [
                    ['Hannah Lee', '1 Cemerlang'],
                    ['Nathan Lee', '4 Amanah'],
                ],
            ],
            [
                'school_name' => 'SJK (C) Batu 11 Cheras',
                'guardian_phone_number' => '0123456821',
                'students' => [
                    ['Amelia Goh', '1 Amanah'],
                    ['Benjamin Goh', '3 Bestari'],
                ],
            ],
        ];
    }
}
