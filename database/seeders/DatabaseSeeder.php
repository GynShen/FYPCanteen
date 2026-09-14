<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            OwnerSeeder::class,
            SchoolSeeder::class,
            OperatorSeeder::class,
            LinkedStudentsParentsSeeder::class,
            UnlinkedStudentsSeeder::class,
            MenuCategorySeeder::class,
            MenuSeeder::class,
        ]);
    }
}
