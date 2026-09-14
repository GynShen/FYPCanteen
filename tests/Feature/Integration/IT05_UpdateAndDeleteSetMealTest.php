<?php

use App\Models\MenuCategory;
use App\Services\GoogleDriveService;
use Tests\Feature\Integration\Support\IntegrationFixtures as F;

require_once __DIR__.'/Support/IntegrationFixtures.php';

test('IT05 updates and deletes a set meal with its related records', function () {
    $owner = F::user('owner');
    $school = F::school(['is_active' => false]);
    $menu = F::menu($school);
    $category = MenuCategory::create(['name' => 'Updated Category']);
    $drive = Mockery::mock(GoogleDriveService::class);
    $drive->shouldReceive('delete')->once()->with($menu->image_drive_id);
    app()->instance(GoogleDriveService::class, $drive);

    $this->actingAs($owner)->patch(route('owner.menu.update', $menu), [
        'menu_category_ids' => [$category->id],
        'school_ids' => [$school->id],
        'name' => 'Updated Meal',
        'description' => 'Updated description',
        'days' => ['friday'],
        'price' => '5.00',
        'available_start_time' => '10:30',
        'available_end_time' => '11:30',
        'is_available' => '1',
        'calories_kcal' => '520',
        'protein_g' => '21',
        'carbohydrates_g' => '72',
        'fat_g' => '13',
        'fibre_g' => '6',
        'sodium_mg' => '610',
        'components' => [['component_type' => 'protein', 'name' => 'Chicken', 'amount_g' => '100']],
    ])->assertRedirect(route('owner.menu'));

    $this->assertDatabaseHas('menu_items', ['id' => $menu->id, 'name' => 'Updated Meal', 'price' => 5.00]);
    $this->assertDatabaseHas('menu_item_days', ['menu_item_id' => $menu->id, 'day_of_week' => 'friday']);

    $this->actingAs($owner)->delete(route('owner.menu.destroy', $menu))->assertRedirect(route('owner.menu'));
    $this->assertDatabaseMissing('menu_items', ['id' => $menu->id]);
});
