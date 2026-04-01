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
        // If a previous attempt partially created the table, recreate cleanly
        Schema::dropIfExists('student_notifications');

        Schema::create('student_notifications', function (Blueprint $table) {
            $table->id();
            // NOTE: We intentionally do NOT add a foreign key constraint here.
            // Some deployments use MyISAM or mismatched engines which can break FK creation.
            $table->unsignedBigInteger('student_id');
            $table->string('title');
            $table->text('message')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->string('created_by_type')->nullable(); // admin|staff|system
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();

            $table->index(['student_id', 'read_at']);
            $table->index(['created_by_type', 'created_by_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_notifications');
    }
};

