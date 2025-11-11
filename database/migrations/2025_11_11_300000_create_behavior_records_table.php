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
        Schema::create('behavior_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->string('student_name');
            $table->string('type'); // Positive, Negative, Warning, etc.
            $table->string('class');
            $table->string('section');
            $table->string('campus');
            $table->date('date');
            $table->text('description')->nullable();
            $table->string('recorded_by')->nullable(); // Staff/Teacher name
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('behavior_records');
    }
};

