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
        Schema::create('fee_increment_percentages', function (Blueprint $table) {
            $table->id();
            $table->string('campus')->nullable();
            $table->string('class')->nullable();
            $table->string('section')->nullable();
            $table->decimal('increase', 10, 2);
            $table->string('accountant')->nullable();
            $table->date('date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fee_increment_percentages');
    }
};

