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
        Schema::create('deleted_fees', function (Blueprint $table) {
            $table->id();
            $table->string('campus')->nullable();
            $table->string('student_code'); // Roll
            $table->string('student_name'); // Student
            $table->string('parent_name')->nullable(); // Parent (father_name)
            $table->string('payment_title'); // Fee Title
            $table->decimal('payment_amount', 10, 2); // Amount
            $table->decimal('discount', 10, 2)->default(0);
            $table->string('method')->nullable();
            $table->date('payment_date')->nullable();
            $table->string('deleted_by')->nullable(); // Deleted By (accountant name)
            $table->text('reason')->nullable(); // Reason for deletion
            $table->timestamp('deleted_at')->nullable(); // Deleted On
            $table->unsignedBigInteger('original_payment_id')->nullable(); // Original payment ID for restore
            // Store all original payment data as JSON for restore
            $table->json('original_data')->nullable();
            $table->timestamps();
            
            $table->index('student_code');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('deleted_fees');
    }
};
