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
        Schema::create('online_rejected_payments', function (Blueprint $table) {
            $table->id();
            $table->string('payment_id')->nullable();
            $table->string('student_code')->nullable();
            $table->string('parent_name')->nullable();
            $table->decimal('paid_amount', 10, 2)->default(0);
            $table->decimal('expected_amount', 10, 2)->default(0);
            $table->date('payment_date')->nullable();
            $table->string('status')->default('Rejected');
            $table->text('remarks')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('online_rejected_payments');
    }
};
