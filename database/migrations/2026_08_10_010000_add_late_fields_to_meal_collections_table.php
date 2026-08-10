<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('meal_collections', function (Blueprint $table): void {
            $table->boolean('is_late_collection')->default(false)->after('status');
            $table->text('late_reason')->nullable()->after('is_late_collection');
        });
    }

    public function down(): void
    {
        Schema::table('meal_collections', function (Blueprint $table): void {
            $table->dropColumn(['is_late_collection', 'late_reason']);
        });
    }
};
