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
        Schema::create('parent_complaints', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_account_id')->nullable();
            $table->unsignedBigInteger('student_id')->nullable();

            $table->string('parent_name')->nullable();
            $table->string('email')->nullable();
            $table->string('phone')->nullable();

            $table->string('subject')->nullable();
            $table->text('complain');

            // pending, in-progress, resolved, closed etc.
            $table->string('status')->default('pending');

            $table->timestamps();

            $table->index('parent_account_id');
            $table->index('student_id');
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('parent_complaints');
    }
};


