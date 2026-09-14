<?php

namespace Database\Seeders;

use App\Models\MenuCategory;
use App\Models\MenuItem;
use App\Models\School;
use App\Services\GoogleDriveService;
use Illuminate\Database\Seeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class MenuSeeder extends Seeder
{
    private const PRICE = 5.00;

    private const PERIODS = [
        ['09:00:00', '11:00:00'],
        ['14:00:00', '16:00:00'],
    ];

    /**
     * Seed two daily collection periods for every demonstration meal.
     */
    public function run(): void
    {
        $schoolIds = School::query()->pluck('id');

        if ($schoolIds->isEmpty()) {
            throw new RuntimeException('Run SchoolSeeder before MenuSeeder.');
        }

        $categoryIds = MenuCategory::query()->pluck('id', 'name');
        $requiredCategories = collect($this->meals())->pluck('categories')->flatten()->unique();
        $missingCategories = $requiredCategories->diff($categoryIds->keys());

        if ($missingCategories->isNotEmpty()) {
            throw new RuntimeException('Run MenuCategorySeeder before MenuSeeder. Missing: '.$missingCategories->join(', '));
        }

        foreach ($this->meals() as $meal) {
            $driveImage = $this->driveImage($meal['name'], $meal['image']);

            foreach (self::PERIODS as [$startTime, $endTime]) {
                DB::transaction(function () use ($meal, $driveImage, $startTime, $endTime, $schoolIds, $categoryIds): void {
                    $periodLabel = $startTime === '09:00:00' ? 'Morning' : 'Afternoon';
                    $seededName = $meal['name'].' ('.$periodLabel.')';
                    $menuItem = MenuItem::query()
                        ->whereIn('name', [$meal['name'], $seededName])
                        ->where('available_start_time', $startTime)
                        ->where('available_end_time', $endTime)
                        ->first() ?? new MenuItem;

                    $menuItem->fill([
                        'name' => $seededName,
                        'description' => $meal['description'],
                        'available_start_time' => $startTime,
                        'available_end_time' => $endTime,
                        'price' => self::PRICE,
                        'image_drive_id' => $driveImage['id'],
                        'image_url' => $driveImage['url'],
                        'is_available' => true,
                        'calories_kcal' => $meal['nutrition']['calories_kcal'],
                        'protein_g' => $meal['nutrition']['protein_g'],
                        'carbohydrates_g' => $meal['nutrition']['carbohydrates_g'],
                        'fat_g' => $meal['nutrition']['fat_g'],
                        'fibre_g' => $meal['nutrition']['fibre_g'],
                        'sodium_mg' => $meal['nutrition']['sodium_mg'],
                    ])->save();

                    $menuItem->schools()->sync($schoolIds->all());
                    $menuItem->menuCategories()->sync(
                        collect($meal['categories'])->map(fn (string $name) => $categoryIds[$name])->all(),
                    );
                    $menuItem->days()->delete();
                    $menuItem->days()->create(['day_of_week' => $meal['day']]);
                    $menuItem->components()->delete();
                    $menuItem->components()->createMany(
                        collect($meal['components'])
                            ->values()
                            ->map(fn (array $component, int $index) => [
                                ...$component,
                                'sort_order' => $index,
                            ])
                            ->all(),
                    );
                });
            }
        }
    }

    /**
     * Reuse an existing seeded Drive image, or upload it once for both periods.
     *
     * @return array{id: string, url: string}
     */
    private function driveImage(string $mealName, string $filename): array
    {
        $existing = MenuItem::query()
            ->whereIn('name', [
                $mealName,
                $mealName.' (Morning)',
                $mealName.' (Afternoon)',
            ])
            ->whereNotNull('image_drive_id')
            ->whereNotNull('image_url')
            ->first(['image_drive_id', 'image_url']);

        if ($existing) {
            return [
                'id' => $existing->image_drive_id,
                'url' => $existing->image_url,
            ];
        }

        $source = database_path('seeders/images/set-meal/'.$filename);

        if (! is_file($source)) {
            throw new RuntimeException("Seeder image not found: {$source}");
        }

        $image = new UploadedFile(
            $source,
            $filename,
            mime_content_type($source) ?: 'image/jpeg',
            null,
            true,
        );

        return app(GoogleDriveService::class)->uploadPublicImage($image);
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function meals(): array
    {
        return [
            [
                'name' => 'Chicken Rice',
                'description' => 'Brown rice served with steamed chicken and cucumber.',
                'day' => 'monday',
                'image' => 'chicken-rice.jpg',
                'categories' => ['Balanced Meal', 'High Protein', 'Low Fat', 'Lower Sodium', 'Whole Grain'],
                'nutrition' => ['calories_kcal' => 310, 'protein_g' => 28, 'carbohydrates_g' => 32, 'fat_g' => 5, 'fibre_g' => 2.5, 'sodium_mg' => 180],
                'components' => [
                    ['component_type' => 'staple', 'name' => 'Brown Rice', 'amount_g' => 120],
                    ['component_type' => 'protein', 'name' => 'Steamed Chicken', 'amount_g' => 80],
                    ['component_type' => 'vegetable', 'name' => 'Cucumber', 'amount_g' => 40],
                ],
            ],
            [
                'name' => 'Nasi Lemak',
                'description' => 'Coconut brown rice served with egg, anchovies and cucumber.',
                'day' => 'monday',
                'image' => 'nasi-lemak.jpg',
                'categories' => ['Balanced Meal', 'High Fibre', 'Whole Grain'],
                'nutrition' => ['calories_kcal' => 360, 'protein_g' => 15, 'carbohydrates_g' => 34, 'fat_g' => 14, 'fibre_g' => 3, 'sodium_mg' => 290],
                'components' => [
                    ['component_type' => 'staple', 'name' => 'Coconut Brown Rice', 'amount_g' => 120],
                    ['component_type' => 'protein', 'name' => 'Egg (1 whole)', 'amount_g' => 50],
                    ['component_type' => 'protein', 'name' => 'Anchovies', 'amount_g' => 10],
                    ['component_type' => 'vegetable', 'name' => 'Cucumber', 'amount_g' => 50],
                ],
            ],
            [
                'name' => 'Chicken Bihun',
                'description' => 'Rice vermicelli served with shredded chicken, carrots and sawi.',
                'day' => 'tuesday',
                'image' => 'chicken-bihun.jpg',
                'categories' => ['Balanced Meal', 'High Protein', 'Low Fat'],
                'nutrition' => ['calories_kcal' => 290, 'protein_g' => 22, 'carbohydrates_g' => 35, 'fat_g' => 3, 'fibre_g' => 2, 'sodium_mg' => 340],
                'components' => [
                    ['component_type' => 'staple', 'name' => 'Rice Vermicelli', 'amount_g' => 100],
                    ['component_type' => 'protein', 'name' => 'Shredded Chicken', 'amount_g' => 70],
                    ['component_type' => 'vegetable', 'name' => 'Carrots and Sawi', 'amount_g' => 60],
                ],
            ],
            [
                'name' => 'Veggie Meatball Pasta',
                'description' => 'Whole-wheat spaghetti served with beef meatballs in tomato and zucchini sauce.',
                'day' => 'tuesday',
                'image' => 'veggie-meatball-pasta .jpg',
                'categories' => ['Balanced Meal', 'High Protein', 'High Fibre', 'Lower Sodium', 'Whole Grain'],
                'nutrition' => ['calories_kcal' => 340, 'protein_g' => 22, 'carbohydrates_g' => 38, 'fat_g' => 9, 'fibre_g' => 5, 'sodium_mg' => 240],
                'components' => [
                    ['component_type' => 'staple', 'name' => 'Whole-wheat Spaghetti', 'amount_g' => 100],
                    ['component_type' => 'protein', 'name' => 'Minced Beef Meatballs', 'amount_g' => 80],
                    ['component_type' => 'vegetable', 'name' => 'Tomato and Zucchini Sauce', 'amount_g' => 60],
                ],
            ],
            [
                'name' => 'Beef Curry Potato',
                'description' => 'Boiled potato cubes served with lean beef, carrots and onions.',
                'day' => 'wednesday',
                'image' => 'beef-curry-potato.jpg',
                'categories' => ['Balanced Meal', 'High Protein', 'High Fibre'],
                'nutrition' => ['calories_kcal' => 345, 'protein_g' => 23, 'carbohydrates_g' => 28, 'fat_g' => 9, 'fibre_g' => 3.2, 'sodium_mg' => 310],
                'components' => [
                    ['component_type' => 'staple', 'name' => 'Boiled Potato Cubes', 'amount_g' => 120],
                    ['component_type' => 'protein', 'name' => 'Lean Beef Cubes', 'amount_g' => 80],
                    ['component_type' => 'vegetable', 'name' => 'Stewed Carrots and Onions', 'amount_g' => 50],
                ],
            ],
            [
                'name' => 'Egg & Chicken Fried Rice',
                'description' => 'Brown fried rice served with scrambled egg, chicken breast, green peas and corn.',
                'day' => 'wednesday',
                'image' => 'egg-chicken-fried-rice .jpg',
                'categories' => ['Balanced Meal', 'High Protein', 'High Fibre', 'Whole Grain'],
                'nutrition' => ['calories_kcal' => 380, 'protein_g' => 24, 'carbohydrates_g' => 39, 'fat_g' => 8, 'fibre_g' => 3.5, 'sodium_mg' => 260],
                'components' => [
                    ['component_type' => 'staple', 'name' => 'Brown Rice', 'amount_g' => 130],
                    ['component_type' => 'protein', 'name' => 'Scrambled Egg and Chicken Breast', 'amount_g' => 80],
                    ['component_type' => 'vegetable', 'name' => 'Green Peas and Corn', 'amount_g' => 40],
                ],
            ],
            [
                'name' => 'Stir-Fried Tomato Chicken Pasta',
                'description' => 'Macaroni pasta served with chicken breast, tomato sauce and bell peppers.',
                'day' => 'thursday',
                'image' => 'stir-fried-tomato-chicken-pasta.jpg',
                'categories' => ['Balanced Meal', 'High Protein', 'High Fibre', 'Low Fat', 'Lower Sodium'],
                'nutrition' => ['calories_kcal' => 365, 'protein_g' => 26, 'carbohydrates_g' => 42, 'fat_g' => 5, 'fibre_g' => 3, 'sodium_mg' => 220],
                'components' => [
                    ['component_type' => 'staple', 'name' => 'Macaroni Pasta', 'amount_g' => 120],
                    ['component_type' => 'protein', 'name' => 'Diced Chicken Breast', 'amount_g' => 80],
                    ['component_type' => 'vegetable', 'name' => 'Tomato Sauce and Bell Peppers', 'amount_g' => 60],
                ],
            ],
            [
                'name' => 'Minced Chicken Udon Soup',
                'description' => 'Fresh udon soup served with chicken meatballs, pak choy and shredded carrots.',
                'day' => 'thursday',
                'image' => 'minced-chicken-udon-soup.jpg',
                'categories' => ['Balanced Meal', 'High Protein', 'Low Fat'],
                'nutrition' => ['calories_kcal' => 340, 'protein_g' => 23, 'carbohydrates_g' => 45, 'fat_g' => 4, 'fibre_g' => 2.2, 'sodium_mg' => 380],
                'components' => [
                    ['component_type' => 'staple', 'name' => 'Fresh Udon Noodles', 'amount_g' => 140],
                    ['component_type' => 'protein', 'name' => 'Chicken Meatballs', 'amount_g' => 80],
                    ['component_type' => 'vegetable', 'name' => 'Pak Choy and Shredded Carrots', 'amount_g' => 60],
                ],
            ],
            [
                'name' => 'Beef & Vegetable Kuey Teow Soup',
                'description' => 'Kuey teow soup served with lean beef, bean sprouts and sawi.',
                'day' => 'friday',
                'image' => 'beef-vegetable-kuey-teow-soup.jpg',
                'categories' => ['Balanced Meal', 'High Protein'],
                'nutrition' => ['calories_kcal' => 335, 'protein_g' => 22, 'carbohydrates_g' => 41, 'fat_g' => 6, 'fibre_g' => 2.1, 'sodium_mg' => 360],
                'components' => [
                    ['component_type' => 'staple', 'name' => 'Flat Rice Noodles / Kuey Teow', 'amount_g' => 140],
                    ['component_type' => 'protein', 'name' => 'Sliced Lean Beef', 'amount_g' => 70],
                    ['component_type' => 'vegetable', 'name' => 'Bean Sprouts and Sawi', 'amount_g' => 60],
                ],
            ],
            [
                'name' => 'Egg & Minced Chicken Mee Hoon Soup',
                'description' => 'Mee hoon soup served with minced chicken, egg, carrots and cabbage.',
                'day' => 'friday',
                'image' => 'egg-minced-chicken-mee-hoon-soup.jpg',
                'categories' => ['Balanced Meal', 'High Protein', 'Low Fat'],
                'nutrition' => ['calories_kcal' => 315, 'protein_g' => 21, 'carbohydrates_g' => 38, 'fat_g' => 5, 'fibre_g' => 2.2, 'sodium_mg' => 320],
                'components' => [
                    ['component_type' => 'staple', 'name' => 'Rice Vermicelli / Mee Hoon', 'amount_g' => 120],
                    ['component_type' => 'protein', 'name' => 'Minced Chicken and Beaten Egg', 'amount_g' => 85],
                    ['component_type' => 'vegetable', 'name' => 'Sliced Carrots and Cabbage', 'amount_g' => 50],
                ],
            ],
        ];
    }
}
