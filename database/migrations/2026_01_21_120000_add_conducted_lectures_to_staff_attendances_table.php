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
            if (!Schema::hasColumn('staff_attendances', 'conducted_lectures')) {
                $table->unsignedInteger('conducted_lectures')->nullable()->after('end_time');
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
            if (Schema::hasColumn('staff_attendances', 'conducted_lectures')) {
                $table->dropColumn('conducted_lectures');
            }
        });
    }
};
