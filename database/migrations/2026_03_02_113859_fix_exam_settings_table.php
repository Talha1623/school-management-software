<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (Schema::hasTable('exam_settings')) {
            return;
        }

        try {
            Schema::create('exam_settings', function (Blueprint $table) {
                $table->id();
                $table->text('admit_card_instructions')->nullable();
                $table->string('fail_student_if')->nullable();
                $table->timestamps();
            });
        } catch (QueryException $exception) {
            if (str_contains(strtolower($exception->getMessage()), 'tablespace for table')) {
                return;
            }

            throw $exception;
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_settings');
    }
};
