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
        if (!Schema::hasTable('staff_attendances') || !Schema::hasColumn('staff_attendances', 'status')) {
            return;
        }

        // Modify the enum column to include 'Half Day'
        // MySQL requires raw SQL to modify ENUM columns
        DB::statement("ALTER TABLE `staff_attendances` MODIFY COLUMN `status` ENUM('Present', 'Absent', 'Holiday', 'Sunday', 'Leave', 'Half Day', 'N/A') DEFAULT 'N/A'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('staff_attendances') || !Schema::hasColumn('staff_attendances', 'status')) {
            return;
        }

        // Remove 'Half Day' from enum (revert to original)
        DB::statement("ALTER TABLE `staff_attendances` MODIFY COLUMN `status` ENUM('Present', 'Absent', 'Holiday', 'Sunday', 'Leave', 'N/A') DEFAULT 'N/A'");
    }
};
