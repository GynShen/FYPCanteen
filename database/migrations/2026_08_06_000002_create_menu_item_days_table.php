<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
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

    }

    public function down(): void
    {
        Schema::dropIfExists('menu_item_days');
    }
};
