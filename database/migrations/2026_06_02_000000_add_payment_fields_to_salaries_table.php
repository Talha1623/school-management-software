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
        Schema::table('salaries', function (Blueprint $table) {
            if (!Schema::hasColumn('salaries', 'payment_method')) {
                $table->string('payment_method')->nullable()->after('deduction_amount');
            }
            if (!Schema::hasColumn('salaries', 'payment_date')) {
                $table->date('payment_date')->nullable()->after('payment_method');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salaries', function (Blueprint $table) {
            if (Schema::hasColumn('salaries', 'payment_date')) {
                $table->dropColumn('payment_date');
            }
            if (Schema::hasColumn('salaries', 'payment_method')) {
                $table->dropColumn('payment_method');
            }
        });
    }
};

