<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('salary_settings', function (Blueprint $table) {
            $table->id();
            $table->time('late_arrival_time')->default('08:00:00');
            $table->integer('free_absents')->default(2);
            $table->enum('leave_deduction', ['Yes', 'No'])->default('No');
            $table->timestamps();
        });

        // Insert default settings
        DB::table('salary_settings')->insert([
            'late_arrival_time' => '08:00:00',
            'free_absents' => 2,
            'leave_deduction' => 'No',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salary_settings');
    }
};

