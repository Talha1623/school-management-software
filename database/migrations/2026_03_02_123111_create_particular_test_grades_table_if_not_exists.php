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
            $exists = DB::select("SHOW TABLES LIKE 'particular_test_grades'");
            if (!empty($exists)) {
                // Table exists, check if it's accessible
                try {
                    DB::table('particular_test_grades')->limit(1)->get();
                    // Table is accessible, no need to create
                    return;
                } catch (\Exception $e) {
                    // Table exists but has issues, try to fix it
                }
            }
        } catch (\Exception $e) {
            // Table doesn't exist or can't be checked, continue to create
        }
        
        // Try to discard tablespace and drop table if it exists (fixes tablespace corruption)
        // Use raw SQL to handle tablespace issues
        try {
            DB::statement('SET FOREIGN_KEY_CHECKS=0');
            // Try to discard tablespace first (this must be done before dropping)
            try {
                DB::unprepared('ALTER TABLE particular_test_grades DISCARD TABLESPACE');
            } catch (\Exception $e) {
                // Table might not exist or might not be InnoDB, continue
            }
            // Now drop the table
            DB::statement('DROP TABLE IF EXISTS particular_test_grades');
            DB::statement('SET FOREIGN_KEY_CHECKS=1');
        } catch (\Exception $e) {
            // If all else fails, try to create with a different engine or handle error
            // For now, continue to try creating
        }
        
        // Create the table if it doesn't exist
        if (!Schema::hasTable('particular_test_grades')) {
            Schema::create('particular_test_grades', function (Blueprint $table) {
                $table->id();
                $table->string('campus');
                $table->string('name'); // Grade name (e.g., A+, A, B+, etc.)
                $table->decimal('from_percentage', 5, 2);
                $table->decimal('to_percentage', 5, 2);
                $table->string('for_test'); // Test name
                $table->string('class');
                $table->string('section')->nullable();
                $table->string('subject')->nullable();
                $table->string('session');
                $table->timestamps();
                
                // Indexes
                $table->index('campus');
                $table->index('for_test');
                $table->index('class');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('particular_test_grades');
    }
};
