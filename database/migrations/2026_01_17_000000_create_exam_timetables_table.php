<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_timetables', function (Blueprint $table) {
            $table->id();
            $table->string('campus');
            $table->string('class');
            $table->string('section');
            $table->string('subject');
            $table->string('exam_name');
            $table->date('exam_date');
            $table->time('starting_time');
            $table->time('ending_time');
            $table->string('room_block')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_timetables');
    }
};
