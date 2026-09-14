<?php

namespace Database\Seeders;

use App\Models\MenuCategory;
use Illuminate\Database\Seeder;

class MenuCategorySeeder extends Seeder
{
    /**
     * Seed nutrition-focused categories used by the demonstration meals.
     */
    public function run(): void
    {
        $categories = [
            'Balanced Meal',
            'High Protein',
            'High Fibre',
            'Low Fat',
            'Lower Sodium',
            'Whole Grain',
        ];

        foreach ($categories as $category) {
            MenuCategory::firstOrCreate(['name' => $category]);
        }
    }
}
