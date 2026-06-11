<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Filename → class name expected by Migrator::resolvePath on some Laravel / hosting setups.
 */
class AddUniqueQuizIdStudentIdToQuizSubmissions extends Migration
{
    /**
     * Prevent two submission rows for the same student + quiz (parallel requests or bad clients).
     */
    public function up(): void
    {
        if (! Schema::hasTable('quiz_submissions')) {
            return;
        }

        try {
            Schema::table('quiz_submissions', function (Blueprint $table) {
                $table->unique(['quiz_id', 'student_id'], 'quiz_submissions_quiz_id_student_id_unique');
            });
        } catch (\Throwable $e) {
            // Index may already exist on some deployments.
        }
    }

    public function down(): void
    {
        if (! Schema::hasTable('quiz_submissions')) {
            return;
        }

        try {
            Schema::table('quiz_submissions', function (Blueprint $table) {
                $table->dropUnique('quiz_submissions_quiz_id_student_id_unique');
            });
        } catch (\Throwable $e) {
            //
        }
    }
}
