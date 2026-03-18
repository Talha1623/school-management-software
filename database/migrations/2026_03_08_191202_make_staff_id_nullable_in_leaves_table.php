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
        // Get the actual foreign key constraint name
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'leaves' 
            AND COLUMN_NAME = 'staff_id' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        // Drop foreign key constraint if it exists
        if (!empty($foreignKeys)) {
            $constraintName = $foreignKeys[0]->CONSTRAINT_NAME;
            DB::statement("ALTER TABLE leaves DROP FOREIGN KEY {$constraintName}");
        }
        
        // Make staff_id nullable
        Schema::table('leaves', function (Blueprint $table) {
            $table->unsignedBigInteger('staff_id')->nullable()->change();
        });
        
        // Re-add foreign key constraint
        Schema::table('leaves', function (Blueprint $table) {
            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Get the actual foreign key constraint name
        $foreignKeys = DB::select("
            SELECT CONSTRAINT_NAME 
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = DATABASE() 
            AND TABLE_NAME = 'leaves' 
            AND COLUMN_NAME = 'staff_id' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        // Drop foreign key constraint if it exists
        if (!empty($foreignKeys)) {
            $constraintName = $foreignKeys[0]->CONSTRAINT_NAME;
            DB::statement("ALTER TABLE leaves DROP FOREIGN KEY {$constraintName}");
        }
        
        // Make staff_id not nullable again
        Schema::table('leaves', function (Blueprint $table) {
            $table->unsignedBigInteger('staff_id')->nullable(false)->change();
        });
        
        // Re-add foreign key constraint
        Schema::table('leaves', function (Blueprint $table) {
            $table->foreign('staff_id')->references('id')->on('staff')->onDelete('cascade');
        });
    }
};
