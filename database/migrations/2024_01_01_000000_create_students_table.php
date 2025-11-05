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
        Schema::create('students', function (Blueprint $table) {
            $table->id();
            
            // Student Information
            $table->string('student_name');
            $table->string('surname_caste')->nullable();
            $table->enum('gender', ['male', 'female', 'other']);
            $table->date('date_of_birth');
            $table->string('place_of_birth')->nullable();
            $table->string('photo')->nullable();
            
            // Parent Information
            $table->string('father_id_card')->nullable();
            $table->string('father_name');
            $table->string('father_email')->nullable();
            $table->string('father_phone', 20)->nullable();
            $table->string('mother_phone', 20)->nullable();
            $table->string('whatsapp_number', 20)->nullable();
            $table->string('religion')->nullable();
            $table->text('home_address')->nullable();
            
            // Other Information
            $table->string('b_form_number')->nullable();
            $table->decimal('monthly_fee', 10, 2)->nullable();
            $table->boolean('discounted_student')->default(false);
            $table->string('transport_route')->nullable();
            $table->string('admission_notification')->nullable();
            $table->boolean('create_parent_account')->default(false);
            $table->string('generate_other_fee')->nullable();
            
            // Academic Information
            $table->string('student_code')->nullable();
            $table->string('gr_number')->nullable();
            $table->string('campus')->nullable();
            $table->string('class');
            $table->string('section')->nullable();
            $table->string('previous_school')->nullable();
            $table->date('admission_date');
            $table->text('reference_remarks')->nullable();
            
            $table->timestamps();
            
            // Indexes for better performance
            $table->index('student_code');
            $table->index('gr_number');
            $table->index('class');
            $table->index('admission_date');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('students');
    }
};

