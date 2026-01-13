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
        Schema::create('classes_to_show', function (Blueprint $table) {
            $table->id();
            $table->string('campus')->nullable();
            $table->string('class')->nullable();
            $table->string('class_timing_from')->nullable();
            $table->string('class_timing_to')->nullable();
            $table->string('student_age_limit_from')->nullable();
            $table->string('student_age_limit_to')->nullable();
            $table->string('class_tuition_fee')->nullable();
            $table->enum('show_on_website_main_page', ['Yes', 'No'])->default('No');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('classes_to_show');
    }
};
