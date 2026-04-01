<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Recreate cleanly if a previous attempt partially created it
        Schema::dropIfExists('quiz_submissions');

        Schema::create('quiz_submissions', function (Blueprint $table) {
            $table->id();
            // No FK constraints (some deployments have engine mismatch)
            $table->unsignedBigInteger('quiz_id');
            $table->unsignedBigInteger('student_id');
            $table->json('answers'); // [{question_id, selected_option, selected_answer, marks_awarded}]
            $table->integer('obtained_marks')->default(0);
            $table->integer('total_marks')->default(0);
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();

            $table->unique(['quiz_id', 'student_id']);
            $table->index(['student_id', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('quiz_submissions');
    }
};

