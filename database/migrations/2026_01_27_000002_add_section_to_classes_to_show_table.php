<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('classes_to_show', function (Blueprint $table) {
            $table->string('section')->nullable()->after('class');
        });
    }

    public function down(): void
    {
        Schema::table('classes_to_show', function (Blueprint $table) {
            $table->dropColumn('section');
        });
    }
};
