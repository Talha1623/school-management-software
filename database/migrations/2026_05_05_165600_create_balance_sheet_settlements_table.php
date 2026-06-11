<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('balance_sheet_settlements', function (Blueprint $table) {
            $table->id();
            $table->date('settlement_date');
            $table->string('campus')->default('all');
            $table->string('user_type')->default('all');
            $table->string('user_name')->default('all');
            $table->decimal('total_payment', 14, 2)->default(0);
            $table->string('method', 100);
            $table->string('transaction_id')->nullable();
            $table->text('remarks')->nullable();
            $table->string('receipt_path')->nullable();
            $table->timestamps();

            $table->index(['settlement_date', 'campus', 'user_type', 'user_name'], 'bs_settlement_filter_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('balance_sheet_settlements');
    }
};

