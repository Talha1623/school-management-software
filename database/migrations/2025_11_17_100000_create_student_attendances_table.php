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
        Schema::create('student_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('students')->onDelete('cascade');
            $table->date('attendance_date');
            $table->enum('status', ['Present', 'Absent', 'Holiday', 'Sunday', 'Leave', 'N/A'])->default('N/A');
            $table->string('campus')->nullable();
            $table->string('class')->nullable();
            $table->string('section')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            
            // Index for faster queries
            $table->index(['student_id', 'attendance_date']);
            $table->index(['attendance_date', 'status']);
            $table->index(['campus', 'class', 'section']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_attendances');
    }
};

