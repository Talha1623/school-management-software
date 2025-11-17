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
            $table->integer('leaves')->default(0)->after('late');
            $table->integer('holidays')->default(0)->after('leaves');
            $table->integer('sundays')->default(0)->after('holidays');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('salaries', function (Blueprint $table) {
            $table->dropColumn(['leaves', 'holidays', 'sundays']);
        });
    }
};
