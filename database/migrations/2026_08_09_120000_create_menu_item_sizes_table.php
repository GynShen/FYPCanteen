<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('menu_item_sizes', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('menu_item_id')->constrained('menu_items')->cascadeOnDelete();
            $table->string('size', 1);
            $table->decimal('price_adjustment', 10, 2)->default(0);
            $table->timestamps();
            $table->unique(['menu_item_id', 'size']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('menu_item_sizes');
    }
};
