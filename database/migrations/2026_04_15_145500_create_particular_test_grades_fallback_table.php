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
        if (Schema::hasTable('particular_test_grades_fallback')) {
            return;
        }

        Schema::create('particular_test_grades_fallback', function (Blueprint $table) {
            $table->id();
            $table->string('campus');
            $table->string('name');
            $table->decimal('from_percentage', 5, 2);
            $table->decimal('to_percentage', 5, 2);
            $table->string('for_test');
            $table->string('class');
            $table->string('section')->nullable();
            $table->string('subject')->nullable();
            $table->string('session');
            $table->timestamps();

            $table->index('campus');
            $table->index('for_test');
            $table->index('class');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('particular_test_grades_fallback');
    }
};
