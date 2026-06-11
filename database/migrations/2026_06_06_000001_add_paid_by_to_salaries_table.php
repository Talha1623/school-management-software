<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('salaries', function (Blueprint $table) {
            if (! Schema::hasColumn('salaries', 'paid_by_type')) {
                $table->string('paid_by_type')->nullable();
            }
            if (! Schema::hasColumn('salaries', 'paid_by_name')) {
                $table->string('paid_by_name')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('salaries', function (Blueprint $table) {
            if (Schema::hasColumn('salaries', 'paid_by_name')) {
                $table->dropColumn('paid_by_name');
            }
            if (Schema::hasColumn('salaries', 'paid_by_type')) {
                $table->dropColumn('paid_by_type');
            }
        });
    }
};
