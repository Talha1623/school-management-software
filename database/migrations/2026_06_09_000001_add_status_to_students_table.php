<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('students') || Schema::hasColumn('students', 'status')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            $table->string('status', 20)->default('active')->after('admission_date');
        });

        DB::table('students')->whereNull('status')->update(['status' => 'active']);
    }

    public function down(): void
    {
        if (!Schema::hasTable('students') || !Schema::hasColumn('students', 'status')) {
            return;
        }

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn('status');
        });
    }
};
