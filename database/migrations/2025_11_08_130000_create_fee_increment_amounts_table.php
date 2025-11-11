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
        Schema::create('fee_increment_amounts', function (Blueprint $table) {
            $table->id();
            $table->string('campus')->nullable();
            $table->string('class')->nullable();
            $table->string('section')->nullable();
            $table->decimal('amount', 10, 2);
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
        Schema::dropIfExists('fee_increment_amounts');
    }
};

