<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('platform_schools', function (Blueprint $table) {
            $table->string('db_host')->default('127.0.0.1')->after('domain');
            $table->string('db_port')->default('3306')->after('db_host');
            $table->string('db_database')->nullable()->after('db_port');
            $table->string('db_username')->nullable()->after('db_database');
            $table->string('db_password')->nullable()->after('db_username');
        });
    }

    public function down(): void
    {
        Schema::table('platform_schools', function (Blueprint $table) {
            $table->dropColumn(['db_host', 'db_port', 'db_database', 'db_username', 'db_password']);
        });
    }
};
