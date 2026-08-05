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
        Schema::create('drive_files', function (Blueprint $table) {
            $table->id();
            $table->string('drive_file_id');
            $table->string('name')->nullable();
            $table->string('filename')->nullable();
            $table->string('extension')->nullable();
            $table->string('path')->nullable();
            $table->string('mime_type')->nullable();
            $table->string('file_size')->nullable();
            $table->string('visibility')->nullable();
            $table->string('last_modified')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('drive_files');
    }
};