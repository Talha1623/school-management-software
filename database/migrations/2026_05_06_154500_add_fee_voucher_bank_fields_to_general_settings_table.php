<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            if (!Schema::hasColumn('general_settings', 'fee_voucher_bank_name')) {
                $table->string('fee_voucher_bank_name')->nullable()->after('fee_voucher_notice');
            }
            if (!Schema::hasColumn('general_settings', 'fee_voucher_account_title')) {
                $table->string('fee_voucher_account_title')->nullable()->after('fee_voucher_bank_name');
            }
            if (!Schema::hasColumn('general_settings', 'fee_voucher_account_number')) {
                $table->string('fee_voucher_account_number')->nullable()->after('fee_voucher_account_title');
            }
            if (!Schema::hasColumn('general_settings', 'fee_voucher_iban')) {
                $table->string('fee_voucher_iban')->nullable()->after('fee_voucher_account_number');
            }
        });
    }

    public function down(): void
    {
        Schema::table('general_settings', function (Blueprint $table) {
            $dropColumns = [];
            foreach (['fee_voucher_bank_name', 'fee_voucher_account_title', 'fee_voucher_account_number', 'fee_voucher_iban'] as $column) {
                if (Schema::hasColumn('general_settings', $column)) {
                    $dropColumns[] = $column;
                }
            }
            if (!empty($dropColumns)) {
                $table->dropColumn($dropColumns);
            }
        });
    }
};
