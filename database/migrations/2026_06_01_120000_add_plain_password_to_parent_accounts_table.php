<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('parent_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('parent_accounts', 'plain_password')) {
                $table->string('plain_password', 255)->nullable()->after('password');
            }
        });
    }

    public function down(): void
    {
        Schema::table('parent_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('parent_accounts', 'plain_password')) {
                $table->dropColumn('plain_password');
            }
        });
    }
};
