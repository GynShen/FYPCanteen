<?php

namespace Database\Seeders;

use App\Models\Children;
use App\Models\School;
use App\Models\Student;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use RuntimeException;

class LinkedStudentsParentsSeeder extends Seeder
{
    /**
     * Seed ten parents and fifteen linked students for each school.
     */
    public function run(): void
    {
        $schoolFamilies = $this->schoolFamilies();
        $schools = School::query()
            ->whereIn('name', array_keys($schoolFamilies))
            ->pluck('id', 'name');

        if ($schools->count() !== count($schoolFamilies)) {
            throw new RuntimeException('Run SchoolSeeder before LinkedStudentsParentsSeeder.');
        }

        DB::transaction(function () use ($schoolFamilies, $schools): void {
            foreach ($schoolFamilies as $schoolName => $families) {
                foreach ($families as [$parentName, $email, $phoneNumber, $students]) {
                    $parent = User::query()->updateOrCreate(
                        ['email' => $email],
                        [
                            'name' => $parentName,
                            'username' => null,
                            'password' => Hash::make('password'),
                            'phone_number' => $phoneNumber,
                            'role' => 'customer',
                            'school_id' => null,
                            'is_active' => true,
                        ],
                    );

                    foreach ($students as [$studentName, $class]) {
                        $student = Student::query()->updateOrCreate(
                            ['qr_code' => 'student-qr-'.Str::slug($studentName)],
                            [
                                'school_id' => $schools[$schoolName],
                                'name' => $studentName,
                                'class' => $class,
                                'guardian_phone_number' => $phoneNumber,
                            ],
                        );

                        Children::query()->updateOrCreate(
                            ['student_id' => $student->id],
                            ['user_id' => $parent->id],
                        );
                    }
                }
            }
        });
    }

    /**
     * Each school contains ten parents. The first five parents have two
     * children each, while the remaining five have one child each.
     *
     * @return array<string, array<int, array{string, string, string, array<int, array{string, string}>}>>
     */
    private function schoolFamilies(): array
    {
        return [
            'SJK(C) Bandar Sungai Long' => [
                ['John Tan', 'john.tan@example.com', '0123456800', [
                    ['Ethan Tan', '1 Amanah'],
                    ['Emily Tan', '3 Bestari'],
                ]],
                ['Siti Aisyah Rahman', 'siti.aisyah@example.com', '0123456801', [
                    ['Aiman Rahman', '2 Cemerlang'],
                    ['Alya Rahman', '5 Amanah'],
                ]],
                ['Raj Kumar', 'raj.kumar@example.com', '0123456802', [
                    ['Arjun Kumar', '4 Bestari'],
                    ['Priya Kumar', '6 Cemerlang'],
                ]],
                ['Mei Ling Wong', 'mei.ling.wong@example.com', '0123456803', [
                    ['Chloe Wong', '1 Bestari'],
                    ['Lucas Wong', '3 Cemerlang'],
                ]],
                ['Ahmad Faizal Ismail', 'ahmad.faizal@example.com', '0123456804', [
                    ['Adam Faizal', '2 Amanah'],
                    ['Sara Faizal', '5 Bestari'],
                ]],
                ['Nurul Huda Hassan', 'nurul.huda@example.com', '0123456805', [
                    ['Iman Hassan', '4 Amanah'],
                ]],
                ['Daniel Lee', 'daniel.lee@example.com', '0123456806', [
                    ['Joshua Lee', '6 Bestari'],
                ]],
                ['Kavitha Nair', 'kavitha.nair@example.com', '0123456807', [
                    ['Anjali Nair', '3 Amanah'],
                ]],
                ['Lim Wei Jian', 'lim.weijian@example.com', '0123456808', [
                    ['Jayden Lim', '2 Bestari'],
                ]],
                ['Farah Nadia Osman', 'farah.nadia@example.com', '0123456809', [
                    ['Hana Osman', '5 Cemerlang'],
                ]],
            ],
            'SJK (C) Batu 11 Cheras' => [
                ['Michael Chan', 'michael.chan@example.com', '0123456810', [
                    ['Ryan Chan', '1 Cemerlang'],
                    ['Sophia Chan', '4 Amanah'],
                ]],
                ['Aminah Yusuf', 'aminah.yusuf@example.com', '0123456811', [
                    ['Hakim Yusuf', '2 Bestari'],
                    ['Nadia Yusuf', '5 Cemerlang'],
                ]],
                ['Suresh Menon', 'suresh.menon@example.com', '0123456812', [
                    ['Vikram Menon', '3 Amanah'],
                    ['Anika Menon', '6 Bestari'],
                ]],
                ['Grace Ng', 'grace.ng@example.com', '0123456813', [
                    ['Oliver Ng', '1 Bestari'],
                    ['Isabelle Ng', '4 Cemerlang'],
                ]],
                ['Hafiz Roslan', 'hafiz.roslan@example.com', '0123456814', [
                    ['Danish Roslan', '2 Amanah'],
                    ['Sofea Roslan', '5 Bestari'],
                ]],
                ['Amanda Goh', 'amanda.goh@example.com', '0123456815', [
                    ['Aiden Goh', '3 Cemerlang'],
                ]],
                ['Ravi Shankar', 'ravi.shankar@example.com', '0123456816', [
                    ['Kiran Shankar', '6 Amanah'],
                ]],
                ['Nora Abdullah', 'nora.abdullah@example.com', '0123456817', [
                    ['Irfan Abdullah', '4 Bestari'],
                ]],
                ['Kelvin Teo', 'kelvin.teo@example.com', '0123456818', [
                    ['Marcus Teo', '2 Cemerlang'],
                ]],
                ['Liyana Karim', 'liyana.karim@example.com', '0123456819', [
                    ['Maya Karim', '5 Amanah'],
                ]],
            ],
        ];
    }
}
