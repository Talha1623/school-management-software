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
        // Check if table already exists and is accessible
        try {
            $exists = DB::select("SHOW TABLES LIKE 'exam_settings'");
            if (!empty($exists)) {
                // Table exists, check if it's accessible
                try {
                    DB::table('exam_settings')->limit(1)->get();
                    // Table is accessible, no need to create
                    return;
                } catch (\Exception $e) {
                    // Table exists but has issues, try to fix it
                    try {
                        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
                        DB::unprepared('ALTER TABLE exam_settings DISCARD TABLESPACE');
                        DB::statement('DROP TABLE exam_settings');
                        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
                    } catch (\Exception $e2) {
                        // If we can't fix it, try to continue
                    }
                }
            }
        } catch (\Exception $e) {
            // Table doesn't exist or can't be checked, continue to create
        }
        
        // Create the table
        if (!Schema::hasTable('exam_settings')) {
            Schema::create('exam_settings', function (Blueprint $table) {
                $table->id();
                $table->text('admit_card_instructions')->nullable();
                $table->string('fail_student_if')->nullable();
                $table->timestamps();
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_settings');
    }
};
