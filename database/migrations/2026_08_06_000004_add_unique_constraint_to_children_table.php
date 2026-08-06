<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('children', function (Blueprint $table): void {
            $table->unique('student_id');
        });
    }

    public function down(): void
    {
        Schema::table('children', function (Blueprint $table): void {
            $table->dropUnique(['student_id']);
        });
    }
};
