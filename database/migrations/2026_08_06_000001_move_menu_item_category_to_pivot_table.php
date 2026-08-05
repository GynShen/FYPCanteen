<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasColumn('menu_items', 'menu_category_id')) {
            return;
        }

        DB::table('menu_items')
            ->whereNotNull('menu_category_id')
            ->orderBy('id')
            ->chunkById(100, function ($menuItems): void {
                $now = now();

                DB::table('menu_category_menu_items')->insertOrIgnore(
                    $menuItems->map(fn ($menuItem) => [
                        'menu_item_id' => $menuItem->id,
                        'menu_category_id' => $menuItem->menu_category_id,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );
            });

        Schema::table('menu_items', function (Blueprint $table): void {
            $table->dropForeign(['menu_category_id']);
            $table->dropColumn('menu_category_id');
        });
    }

    public function down(): void
    {
        if (Schema::hasColumn('menu_items', 'menu_category_id')) {
            return;
        }

        Schema::table('menu_items', function (Blueprint $table): void {
            $table->foreignId('menu_category_id')
                ->nullable()
                ->after('id')
                ->constrained('menu_categories');
        });

        DB::table('menu_category_menu_items')
            ->orderBy('id')
            ->get()
            ->unique('menu_item_id')
            ->each(function ($category): void {
                DB::table('menu_items')
                    ->where('id', $category->menu_item_id)
                    ->update(['menu_category_id' => $category->menu_category_id]);
            });
    }
};
