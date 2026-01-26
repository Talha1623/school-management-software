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
        Schema::create('particular_exam_grades', function (Blueprint $table) {
            $table->id();
            $table->string('campus');
            $table->string('name');
            $table->decimal('from_percentage', 5, 2);
            $table->decimal('to_percentage', 5, 2);
            $table->decimal('grade_points', 5, 2)->default(0);
            $table->string('for_exam');
            $table->string('session');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('particular_exam_grades');
    }
};
