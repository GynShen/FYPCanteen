<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('school_menu_items', function (Blueprint $table): void {
            $table->unique(['school_id', 'menu_item_id']);
        });
    }

    public function down(): void
    {
        Schema::table('school_menu_items', function (Blueprint $table): void {
            $table->dropUnique(['school_id', 'menu_item_id']);
        });
    }
};
