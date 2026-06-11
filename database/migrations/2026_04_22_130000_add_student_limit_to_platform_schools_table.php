<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_schools', function (Blueprint $table) {
            $table->unsignedInteger('student_limit')->nullable()->after('owner_password');
        });
    }

    public function down(): void
    {
        Schema::table('platform_schools', function (Blueprint $table) {
            $table->dropColumn('student_limit');
        });
    }
};
