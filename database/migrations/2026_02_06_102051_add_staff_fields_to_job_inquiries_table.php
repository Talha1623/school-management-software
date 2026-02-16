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
        Schema::table('job_inquiries', function (Blueprint $table) {
            $table->decimal('salary', 10, 2)->nullable()->after('salary_demand');
            $table->decimal('absent_fees', 10, 2)->nullable()->after('salary');
            $table->decimal('late_fees', 10, 2)->nullable()->after('absent_fees');
            $table->decimal('early_exit_fees', 10, 2)->nullable()->after('late_fees');
            $table->integer('free_absent')->default(0)->after('early_exit_fees');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('job_inquiries', function (Blueprint $table) {
            $table->dropColumn(['salary', 'absent_fees', 'late_fees', 'early_exit_fees', 'free_absent']);
        });
    }
};
