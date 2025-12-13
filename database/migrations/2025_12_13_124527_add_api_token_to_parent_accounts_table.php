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
        Schema::table('parent_accounts', function (Blueprint $table) {
            if (!Schema::hasColumn('parent_accounts', 'api_token')) {
                $table->text('api_token')->nullable()->after('password');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('parent_accounts', function (Blueprint $table) {
            if (Schema::hasColumn('parent_accounts', 'api_token')) {
                $table->dropColumn('api_token');
            }
        });
    }
};
