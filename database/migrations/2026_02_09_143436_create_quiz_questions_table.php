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
        Schema::create('quiz_questions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('quiz_id');
            $table->integer('question_number');
            $table->text('question')->nullable();
            $table->string('answer1')->nullable();
            $table->integer('marks1')->default(0);
            $table->string('answer2')->nullable();
            $table->integer('marks2')->default(0);
            $table->string('answer3')->nullable();
            $table->integer('marks3')->default(0);
            $table->timestamps();
            
            // Foreign key constraint - commented out if table already exists
            // $table->foreign('quiz_id')->references('id')->on('quizzes')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_questions');
    }
};
