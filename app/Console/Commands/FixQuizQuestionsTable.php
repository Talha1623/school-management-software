<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class FixQuizQuestionsTable extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'fix:quiz-questions-table';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Fix quiz_questions table tablespace issue';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $this->info('Fixing quiz_questions table...');
        
        // Check if table exists and is accessible
        try {
            DB::select("SELECT 1 FROM quiz_questions LIMIT 1");
            $this->info('Table already exists and is accessible!');
            return 0;
        } catch (\Exception $e) {
            $this->info('Table does not exist or is not accessible. Creating...');
        }
        
        try {
            // Disable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            
            // Try to drop the table completely
            try {
                DB::statement('DROP TABLE IF EXISTS quiz_questions');
                $this->info('Dropped existing table reference (if any)');
            } catch (\Exception $e) {
                $this->warn('Could not drop table: ' . $e->getMessage());
                $this->warn('');
                $this->warn('⚠️  TABLESPACE ERROR DETECTED ⚠️');
                $this->warn('');
                $this->warn('You need to manually fix the MySQL tablespace issue:');
                $this->warn('1. Stop MySQL service');
                $this->warn('2. Find MySQL data directory (usually: C:\\ProgramData\\MySQL\\MySQL Server X.X\\Data\\)');
                $this->warn('3. Navigate to: [Data Directory]/school/');
                $this->warn('4. Delete: quiz_questions.ibd (and quiz_questions.frm if exists)');
                $this->warn('5. Start MySQL service');
                $this->warn('6. Run this command again: php artisan fix:quiz-questions-table');
                $this->warn('');
                $this->warn('OR run the SQL script: fix_quiz_questions.sql');
                return 1;
            }
            
            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } catch (\Exception $e) {
            $this->error('Error: ' . $e->getMessage());
            return 1;
        }
        
        // Create table with temporary name to avoid tablespace conflict
        $tempTableName = 'quiz_questions_new_' . time();
        
        try {
            Schema::create($tempTableName, function ($table) {
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
            
            $this->info('Created temporary table: ' . $tempTableName);
            
            // Now try to rename it
            try {
                DB::statement("RENAME TABLE {$tempTableName} TO quiz_questions");
                $this->info('Renamed table to quiz_questions');
            } catch (\Exception $e) {
                // If rename fails, drop temp and try direct creation
                Schema::dropIfExists($tempTableName);
                throw $e;
            }
            
            // Add foreign key separately
            try {
                DB::statement('ALTER TABLE quiz_questions ADD CONSTRAINT quiz_questions_quiz_id_foreign FOREIGN KEY (quiz_id) REFERENCES quizzes(id) ON DELETE CASCADE');
                $this->info('Added foreign key constraint');
            } catch (\Exception $e) {
                $this->warn('Could not add foreign key (might already exist): ' . $e->getMessage());
            }
            
            $this->info('Table created successfully!');
        } catch (\Exception $e) {
            $this->error('Failed to create table: ' . $e->getMessage());
            $this->error('You may need to manually delete the .ibd file from MySQL data directory');
            return 1;
        }
        
        return 0;
    }
}
