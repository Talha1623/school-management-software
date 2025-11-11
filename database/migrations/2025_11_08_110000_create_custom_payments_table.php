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
        Schema::create('custom_payments', function (Blueprint $table) {
            $table->id();
            $table->string('campus')->nullable();
            $table->string('payment_title');
            $table->decimal('payment_amount', 10, 2);
            $table->string('accountant')->nullable();
            $table->string('method');
            $table->string('notify_admin')->default('Yes');
            $table->date('payment_date');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('custom_payments');
    }
};

