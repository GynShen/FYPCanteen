<?php

use App\Models\MenuCategory;
use App\Services\GoogleDriveService;
use Illuminate\Http\UploadedFile;
use Tests\Feature\Integration\Support\IntegrationFixtures as F;

require_once __DIR__.'/Support/IntegrationFixtures.php';

test('IT04 creates a set meal and all of its relationships', function () {
    $owner = F::user('owner');
    $school = F::school(['is_active' => false]);
    $category = MenuCategory::create(['name' => 'Protein']);
    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->shouldReceive('uploadPublicImage')->once()->andReturn(['id' => 'drive-test', 'url' => 'https://example.test/drive-test.png']);
    app()->instance(GoogleDriveService::class, $drive);

    $this->actingAs($owner)->post(route('owner.menu.store'), [
        'menu_category_ids' => [$category->id],
        'school_ids' => [$school->id],
        'name' => 'Integration Chicken Rice',
        'description' => 'Test meal',
        'days' => ['monday', 'tuesday'],
        'price' => '4.50',
        'available_start_time' => '10:00',
        'available_end_time' => '11:00',
        'image' => UploadedFile::fake()->image('meal.png', 80, 80),
        'is_available' => '1',
        'calories_kcal' => '500',
        'protein_g' => '20',
        'carbohydrates_g' => '70',
        'fat_g' => '12',
        'fibre_g' => '5',
        'sodium_mg' => '600',
        'components' => [['component_type' => 'staple', 'name' => 'Rice', 'amount_g' => '150']],
    ])->assertRedirect(route('owner.menu'));

    $this->assertDatabaseHas('menu_items', ['name' => 'Integration Chicken Rice', 'image_drive_id' => 'drive-test']);
    $this->assertDatabaseCount('school_menu_items', 1);
    $this->assertDatabaseCount('menu_category_menu_items', 1);
    $this->assertDatabaseCount('menu_item_days', 2);
    $this->assertDatabaseCount('menu_item_components', 1);
});
