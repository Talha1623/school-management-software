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
        Schema::create('student_payments', function (Blueprint $table) {
            $table->id();
            $table->string('campus')->nullable();
            $table->string('student_code');
            $table->string('payment_title');
            $table->decimal('payment_amount', 10, 2);
            $table->decimal('discount', 10, 2)->default(0);
            $table->string('method');
            $table->date('payment_date');
            $table->string('sms_notification')->default('Yes');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_payments');
    }
};

