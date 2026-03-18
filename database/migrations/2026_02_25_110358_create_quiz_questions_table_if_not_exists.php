<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Check if table actually exists and is accessible
        try {
            $exists = DB::select("SELECT 1 FROM quiz_questions LIMIT 1");
            // If we can query it, table exists and is fine
            return;
        } catch (\Exception $e) {
            // Table doesn't exist or has issues, continue with creation
        }
        
        // Try to clean up orphaned tablespace
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            // Try to create a temporary table to force cleanup
            DB::statement('CREATE TABLE IF NOT EXISTS quiz_questions_temp LIKE quizzes');
            DB::statement('DROP TABLE IF EXISTS quiz_questions_temp');
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } catch (\Exception $e) {
            // Ignore
        }
        
        // Create table using Schema builder which handles tablespace better
        if (!Schema::hasTable('quiz_questions')) {
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
        });
            
            // Add foreign key separately
            try {
                DB::statement('ALTER TABLE quiz_questions ADD CONSTRAINT quiz_questions_quiz_id_foreign FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE');
            } catch (\Exception $e) {
                // Foreign key might already exist, ignore
            }
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('quiz_questions');
    }
};
