<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('admin_roles')) {
            return;
        }

        if (! Schema::hasColumn('admin_roles', 'photo')) {
            Schema::table('admin_roles', function (Blueprint $table) {
                $table->string('photo')->nullable()->after('email');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('admin_roles') && Schema::hasColumn('admin_roles', 'photo')) {
            Schema::table('admin_roles', function (Blueprint $table) {
                $table->dropColumn('photo');
            });
        }
    }
};
