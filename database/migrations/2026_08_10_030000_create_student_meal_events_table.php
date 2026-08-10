<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_meal_events', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('order_item_id')->constrained('order_items')->cascadeOnDelete();
            $table->foreignId('meal_collection_id')->nullable()->constrained('meal_collections')->nullOnDelete();
            $table->enum('event_type', ['still_hungry', 'missed_collection']);
            $table->foreignId('reported_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('reported_at');
            $table->timestamps();

            $table->unique(['order_item_id', 'event_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_meal_events');
    }
};
