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
        Schema::table('staff', function (Blueprint $table) {
            $table->decimal('absent_fees', 10, 2)->nullable()->after('salary');
            $table->decimal('late_fees', 10, 2)->nullable()->after('absent_fees');
            $table->integer('free_absent')->default(0)->after('late_fees');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('staff', function (Blueprint $table) {
            $table->dropColumn(['absent_fees', 'late_fees', 'free_absent']);
        });
    }
};
