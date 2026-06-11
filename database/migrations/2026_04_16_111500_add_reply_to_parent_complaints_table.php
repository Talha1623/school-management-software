<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('parent_complaints')) {
            return;
        }

        Schema::table('parent_complaints', function (Blueprint $table) {
            if (!Schema::hasColumn('parent_complaints', 'reply')) {
                $table->text('reply')->nullable()->after('status');
            }
        });
    }

    public function down(): void
    {
        if (!Schema::hasTable('parent_complaints')) {
            return;
        }

        Schema::table('parent_complaints', function (Blueprint $table) {
            if (Schema::hasColumn('parent_complaints', 'reply')) {
                $table->dropColumn('reply');
            }
        });
    }
};
