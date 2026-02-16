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
        if (!Schema::hasColumn('staff_attendances', 'class')) {
            Schema::table('staff_attendances', function (Blueprint $table) {
                $table->string('class')->nullable()->after('designation');
            });
        }
        if (!Schema::hasColumn('staff_attendances', 'section')) {
            Schema::table('staff_attendances', function (Blueprint $table) {
                $table->string('section')->nullable()->after('class');
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff_attendances', function (Blueprint $table) {
            if (Schema::hasColumn('staff_attendances', 'section')) {
                $table->dropColumn('section');
            }
            if (Schema::hasColumn('staff_attendances', 'class')) {
                $table->dropColumn('class');
            }
        });
    }
};
