<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('parent_device_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('parent_id');
            $table->text('fcm_token');
            $table->string('platform', 20)->default('android');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['parent_id', 'fcm_token'], 'parent_device_unique');
            $table->index(['parent_id', 'is_active'], 'parent_device_active_idx');
        });

        Schema::create('staff_device_tokens', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('staff_id');
            $table->text('fcm_token');
            $table->string('platform', 20)->default('android');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->timestamps();

            $table->unique(['staff_id', 'fcm_token'], 'staff_device_unique');
            $table->index(['staff_id', 'is_active'], 'staff_device_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('staff_device_tokens');
        Schema::dropIfExists('parent_device_tokens');
    }
};

