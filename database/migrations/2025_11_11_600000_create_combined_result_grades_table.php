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
        Schema::create('combined_result_grades', function (Blueprint $table) {
            $table->id();
            $table->string('campus');
            $table->string('name'); // Grade name (e.g., A+, A, B+, etc.)
            $table->decimal('from_percentage', 5, 2); // From percentage
            $table->decimal('to_percentage', 5, 2); // To percentage
            $table->string('session');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('combined_result_grades');
    }
};

