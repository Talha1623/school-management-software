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
        Schema::create('advance_fees', function (Blueprint $table) {
            $table->id();
            $table->string('parent_id')->nullable();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('id_card_number')->nullable();
            $table->decimal('available_credit', 10, 2)->default(0);
            $table->decimal('increase', 10, 2)->default(0);
            $table->decimal('decrease', 10, 2)->default(0);
            $table->integer('childs')->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('advance_fees');
    }
};

