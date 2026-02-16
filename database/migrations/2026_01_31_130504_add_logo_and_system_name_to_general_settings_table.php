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
        Schema::table('general_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('general_settings', 'logo')) {
                $table->string('logo')->nullable()->after('timezone');
            }
            if (!Schema::hasColumn('general_settings', 'system_name')) {
                $table->string('system_name')->nullable()->default('ICMS')->after('logo');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $table->dropColumn(['logo', 'system_name']);
        });
    }
};
