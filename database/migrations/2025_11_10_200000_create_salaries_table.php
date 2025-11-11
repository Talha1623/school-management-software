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
        Schema::create('salaries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('staff_id')->constrained('staff')->onDelete('cascade');
            $table->string('salary_month');
            $table->string('year');
            $table->integer('present')->default(0);
            $table->integer('absent')->default(0);
            $table->integer('late')->default(0);
            $table->decimal('basic', 10, 2)->default(0);
            $table->decimal('salary_generated', 10, 2)->default(0);
            $table->decimal('amount_paid', 10, 2)->default(0);
            $table->decimal('loan_repayment', 10, 2)->default(0);
            $table->enum('status', ['Pending', 'Paid', 'Partial'])->default('Pending');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('salaries');
    }
};

