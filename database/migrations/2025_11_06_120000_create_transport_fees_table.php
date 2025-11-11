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
        Schema::create('transport_fees', function (Blueprint $table) {
            $table->id();
            $table->string('campus');
            $table->string('class');
            $table->string('section');
            $table->string('fee_month');
            $table->string('fee_year');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('transport_fees');
    }
};

