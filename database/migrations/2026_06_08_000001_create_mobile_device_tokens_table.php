<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('mobile_device_tokens')) {
            return;
        }

        Schema::create('mobile_device_tokens', function (Blueprint $table) {
            $table->id();
            $table->string('user_type', 32);
            $table->unsignedBigInteger('user_id');
            $table->string('device_id', 255);
            $table->text('fcm_token')->nullable();
            $table->timestamp('last_login_at')->nullable();
            $table->timestamps();

            $table->unique(['user_type', 'user_id', 'device_id'], 'mobile_device_tokens_user_device_unique');
            $table->index(['user_type', 'user_id'], 'mobile_device_tokens_user_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mobile_device_tokens');
    }
};
