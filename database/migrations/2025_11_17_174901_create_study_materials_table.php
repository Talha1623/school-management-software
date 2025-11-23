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
        Schema::create('study_materials', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('campus');
            $table->string('class');
            $table->string('section')->nullable();
            $table->string('subject')->nullable();
            $table->enum('file_type', ['picture', 'video', 'documents']);
            $table->string('file_path')->nullable(); // For uploaded files
            $table->text('youtube_url')->nullable(); // For YouTube videos
            $table->timestamps();
            
            // Indexes for faster queries
            $table->index(['campus', 'class', 'section', 'file_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('study_materials');
    }
};
