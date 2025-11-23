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
        Schema::table('students', function (Blueprint $table) {
            // Check if columns don't exist before adding them
            if (!Schema::hasColumn('students', 'email')) {
                $table->string('email')->nullable()->after('b_form_number');
            }
            if (!Schema::hasColumn('students', 'password')) {
                $table->string('password')->nullable()->after('email');
            }
            if (!Schema::hasColumn('students', 'remember_token')) {
                $table->rememberToken()->after('password');
            }
            
            // Note: student_code index already exists from original migration
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['email', 'password', 'remember_token']);
        });
    }
};

