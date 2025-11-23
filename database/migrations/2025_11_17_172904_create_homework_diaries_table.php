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
        Schema::create('homework_diaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->string('campus');
            $table->string('class');
            $table->string('section');
            $table->date('date');
            $table->text('homework_content');
            $table->timestamps();
            
            // Unique constraint to prevent duplicate entries for same subject, date, class, section
            $table->unique(['subject_id', 'date', 'class', 'section'], 'unique_homework_diary');
            
            // Indexes for faster queries
            $table->index(['campus', 'class', 'section', 'date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('homework_diaries');
    }
};
