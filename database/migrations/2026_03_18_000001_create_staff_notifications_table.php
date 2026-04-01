<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // If a previous attempt partially created the table, recreate cleanly
        Schema::dropIfExists('staff_notifications');

        Schema::create('staff_notifications', function (Blueprint $table) {
            $table->id();
            // No FK constraint (some deployments have engine mismatch)
            $table->unsignedBigInteger('staff_id');
            $table->string('title');
            $table->text('message')->nullable();
            $table->json('data')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->string('created_by_type')->nullable(); // admin|staff|system
            $table->unsignedBigInteger('created_by_id')->nullable();
            $table->timestamps();

            $table->index(['staff_id', 'read_at']);
            $table->index(['created_by_type', 'created_by_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_notifications');
    }
};

