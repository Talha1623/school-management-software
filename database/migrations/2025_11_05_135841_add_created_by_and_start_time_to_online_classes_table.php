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
        Schema::table('online_classes', function (Blueprint $table) {
            $table->string('created_by')->nullable()->after('password');
            $table->time('start_time')->nullable()->after('start_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('online_classes', function (Blueprint $table) {
            $table->dropColumn(['created_by', 'start_time']);
        });
    }
};
