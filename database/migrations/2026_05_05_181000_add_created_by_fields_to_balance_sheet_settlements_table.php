<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('balance_sheet_settlements')) {
            return;
        }

        Schema::table('balance_sheet_settlements', function (Blueprint $table) {
            if (!Schema::hasColumn('balance_sheet_settlements', 'created_by_type')) {
                $table->string('created_by_type')->nullable()->after('user_name');
            }

            if (!Schema::hasColumn('balance_sheet_settlements', 'created_by_name')) {
                $table->string('created_by_name')->nullable()->after('created_by_type');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('balance_sheet_settlements')) {
            return;
        }

        Schema::table('balance_sheet_settlements', function (Blueprint $table) {
            if (Schema::hasColumn('balance_sheet_settlements', 'created_by_name')) {
                $table->dropColumn('created_by_name');
            }
            if (Schema::hasColumn('balance_sheet_settlements', 'created_by_type')) {
                $table->dropColumn('created_by_type');
            }
        });
    }
};

