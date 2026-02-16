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
        Schema::create('particular_test_grades', function (Blueprint $table) {
            $table->id();
            $table->string('campus');
            $table->string('name'); // Grade name (e.g., A+, A, B+, etc.)
            $table->decimal('from_percentage', 5, 2);
            $table->decimal('to_percentage', 5, 2);
            $table->string('for_test'); // Test name
            $table->string('class');
            $table->string('section')->nullable();
            $table->string('subject')->nullable();
            $table->string('session');
            $table->timestamps();
            
            // Indexes
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
        Schema::dropIfExists('particular_test_grades');
    }
};
