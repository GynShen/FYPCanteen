<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const DAYS = [
        'monday',
        'tuesday',
        'wednesday',
        'thursday',
        'friday',
        'saturday',
        'sunday',
    ];

    public function up(): void
    {
        Schema::create('menu_item_days', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('menu_item_id')
                ->constrained('menu_items')
                ->cascadeOnDelete();
            $table->enum('day_of_week', self::DAYS);
            $table->timestamps();

            $table->unique(['menu_item_id', 'day_of_week']);
        });

        if (! Schema::hasColumn('menu_items', 'day_of_week')) {
            return;
        }

        DB::table('menu_items')
            ->whereNotNull('day_of_week')
            ->orderBy('id')
            ->chunkById(100, function ($menuItems): void {
                $now = now();

                DB::table('menu_item_days')->insertOrIgnore(
                    $menuItems->map(fn ($menuItem) => [
                        'menu_item_id' => $menuItem->id,
                        'day_of_week' => $menuItem->day_of_week,
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])->all()
                );
            });

        Schema::table('menu_items', function (Blueprint $table): void {
            $table->dropColumn('day_of_week');
        });
    }

    public function down(): void
    {
        if (! Schema::hasColumn('menu_items', 'day_of_week')) {
            Schema::table('menu_items', function (Blueprint $table): void {
                $table->enum('day_of_week', self::DAYS)->nullable()->after('description');
            });

            DB::table('menu_item_days')
                ->orderBy('id')
                ->get()
                ->unique('menu_item_id')
                ->each(function ($menuItemDay): void {
                    DB::table('menu_items')
                        ->where('id', $menuItemDay->menu_item_id)
                        ->update(['day_of_week' => $menuItemDay->day_of_week]);
                });
        }

        Schema::dropIfExists('menu_item_days');
    }
};
