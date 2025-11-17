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
        // Modify the enum column to ensure all values are present
        DB::statement("ALTER TABLE tasks MODIFY COLUMN status ENUM('Pending', 'Accepted', 'Returned', 'Completed') DEFAULT 'Pending'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Revert to original if needed
        DB::statement("ALTER TABLE tasks MODIFY COLUMN status ENUM('Pending', 'Accepted', 'Returned', 'Completed') DEFAULT 'Pending'");
    }
};
