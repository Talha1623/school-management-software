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
        Schema::create('messages', function (Blueprint $table) {
            $table->id();

            // Generic polymorphic-style sender/receiver for different roles
            $table->string('from_type', 30); // admin, teacher, parent, student, accountant
            $table->unsignedBigInteger('from_id');
            $table->string('to_type', 30);
            $table->unsignedBigInteger('to_id');

            $table->text('text')->nullable();
            $table->string('attachment_path')->nullable();
            $table->string('attachment_type', 50)->nullable(); // image, pdf, doc, etc.

            $table->timestamp('read_at')->nullable();

            $table->timestamps();

            $table->index(['from_type', 'from_id']);
            $table->index(['to_type', 'to_id']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('messages');
    }
};


