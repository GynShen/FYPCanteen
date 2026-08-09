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

        Schema::table('cart_items', function (Blueprint $table): void {
            $table->string('size', 1)->default('M')->after('menu_item_id');
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->string('size', 1)->default('M')->after('menu_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', fn (Blueprint $table) => $table->dropColumn('size'));
        Schema::table('cart_items', fn (Blueprint $table) => $table->dropColumn('size'));
        Schema::dropIfExists('menu_item_sizes');
    }
};
