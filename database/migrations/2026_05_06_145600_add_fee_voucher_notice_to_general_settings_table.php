<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('general_settings', 'fee_voucher_notice')) {
                $table->text('fee_voucher_notice')->nullable()->after('system_name');
            }
        });
    }

    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            if (Schema::hasColumn('general_settings', 'fee_voucher_notice')) {
                $table->dropColumn('fee_voucher_notice');
            }
        });
    }
};
