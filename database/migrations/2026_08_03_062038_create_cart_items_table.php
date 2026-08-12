<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')
                ->constrained('carts')->cascadeOnDelete();
            $table->foreignId('student_id')
                ->constrained('students')->cascadeOnDelete();
            $table->foreignId('menu_item_id')
                ->constrained('menu_items')->cascadeOnDelete();
            $table->string('size', 1)->default('M');
            $table->integer('quantity');
            $table->dateTime('scheduled_time');
            $table->timestamps();
            $table->unique([
                'cart_id',
                'student_id',
                'menu_item_id',
                'scheduled_time',
            ], 'cart_item_selection_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cart_items');
    }
};
