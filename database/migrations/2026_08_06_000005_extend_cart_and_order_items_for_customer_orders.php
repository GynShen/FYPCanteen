<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('carts', function (Blueprint $table): void {
            $table->unique('user_id');
        });

        Schema::table('cart_items', function (Blueprint $table): void {
            $table->foreignId('student_id')
                ->after('cart_id')
                ->constrained('students')
                ->cascadeOnDelete();
            $table->dateTime('scheduled_time')->after('quantity');
            $table->unique([
                'cart_id',
                'student_id',
                'menu_item_id',
                'scheduled_time',
            ], 'cart_item_selection_unique');
        });

        Schema::table('order_items', function (Blueprint $table): void {
            $table->decimal('unit_price', 10, 2)->after('menu_item_id');
        });
    }

    public function down(): void
    {
        Schema::table('order_items', function (Blueprint $table): void {
            $table->dropColumn('unit_price');
        });

        Schema::table('cart_items', function (Blueprint $table): void {
            $table->dropUnique('cart_item_selection_unique');
            $table->dropForeign(['student_id']);
            $table->dropColumn(['student_id', 'scheduled_time']);
        });

        Schema::table('carts', function (Blueprint $table): void {
            $table->dropUnique(['user_id']);
        });
    }
};
