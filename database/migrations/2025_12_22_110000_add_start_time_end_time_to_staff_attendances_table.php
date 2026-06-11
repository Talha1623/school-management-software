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
        if (!Schema::hasTable('staff_attendances')) {
            return;
        }

        Schema::table('staff_attendances', function (Blueprint $table) {
            if (!Schema::hasColumn('staff_attendances', 'start_time')) {
                $table->time('start_time')->nullable()->after('status');
            }

            if (!Schema::hasColumn('staff_attendances', 'end_time')) {
                $table->time('end_time')->nullable()->after('start_time');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        if (!Schema::hasTable('staff_attendances')) {
            return;
        }

        Schema::table('staff_attendances', function (Blueprint $table) {
            if (Schema::hasColumn('staff_attendances', 'end_time')) {
                $table->dropColumn('end_time');
            }

            if (Schema::hasColumn('staff_attendances', 'start_time')) {
                $table->dropColumn('start_time');
            }
        });
    }
};

