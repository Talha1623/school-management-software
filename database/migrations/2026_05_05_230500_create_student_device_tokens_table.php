<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_device_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('student_id');
            $table->text('fcm_token');
            $table->string('platform', 20)->default('android');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['student_id', 'fcm_token'], 'student_device_unique');
            $table->index(['student_id', 'is_active'], 'student_device_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_device_tokens');
    }
};

