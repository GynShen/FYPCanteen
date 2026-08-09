<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('menu_items', function (Blueprint $table): void {
            $table->time('available_start_time')->nullable()->after('price');
            $table->time('available_end_time')->nullable()->after('available_start_time');
        });
    }

    public function down(): void
    {
        Schema::table('menu_items', function (Blueprint $table): void {
            $table->dropColumn(['available_start_time', 'available_end_time']);
        });
    }
};
