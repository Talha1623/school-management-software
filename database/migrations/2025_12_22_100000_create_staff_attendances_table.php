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
        Schema::create('staff_attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade');
            $table->date('attendance_date');
            $table->enum('status', ['Present', 'Absent', 'Holiday', 'Sunday', 'Leave', 'N/A'])->default('N/A');
            $table->string('campus')->nullable();
            $table->string('designation')->nullable();
            $table->text('remarks')->nullable();
            $table->timestamps();
            
            // Unique constraint: one attendance per staff per day
            $table->unique(['staff_id', 'attendance_date']);
            
            // Indexes for faster queries
            $table->index(['staff_id', 'attendance_date']);
            $table->index(['attendance_date', 'status']);
            $table->index(['campus', 'designation']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('staff_attendances');
    }
};

