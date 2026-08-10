<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('meal_collections', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('order_item_id')->unique()->constrained('order_items')->cascadeOnDelete();
            $table->unsignedInteger('collected_quantity')->default(0);
            $table->enum('status', ['pending', 'partially_collected', 'collected'])->default('pending');
            $table->boolean('is_late_collection')->default(false);
            $table->text('late_reason')->nullable();
            $table->timestamp('collected_at')->nullable();
            $table->foreignId('collected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('meal_collections');
    }
};
