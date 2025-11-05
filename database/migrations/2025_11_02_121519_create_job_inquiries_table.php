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
        Schema::create('job_inquiries', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('father_husband_name')->nullable();
            $table->string('campus')->nullable();
            $table->enum('gender', ['Male', 'Female', 'Other'])->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('qualification')->nullable();
            $table->date('birthday')->nullable();
            $table->enum('marital_status', ['Single', 'Married', 'Divorced', 'Widowed'])->nullable();
            $table->string('applied_for_designation')->nullable();
            $table->string('salary_type')->nullable();
            $table->decimal('salary_demand', 10, 2)->nullable();
            $table->string('email')->nullable();
            $table->text('home_address')->nullable();
            $table->string('cv_resume')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('job_inquiries');
    }
};
