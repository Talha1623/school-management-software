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
        Schema::create('biometric_devices', function (Blueprint $table) {
            $table->id();
            $table->string('device_name');
            $table->string('device_model')->nullable();
            $table->string('device_serial_number')->nullable();
            $table->string('device_ip_address')->nullable();
            $table->integer('device_port')->nullable();
            $table->string('device_password')->nullable();
            $table->string('campus')->nullable();
            $table->string('location')->nullable();
            $table->enum('status', ['Active', 'Inactive', 'Maintenance', 'Disconnected'])->default('Inactive');
            $table->enum('connection_type', ['Ethernet', 'WiFi', 'USB'])->default('Ethernet');
            $table->dateTime('last_sync_date')->nullable();
            $table->integer('total_users')->default(0);
            $table->integer('total_fingerprints')->default(0);
            $table->string('firmware_version')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('biometric_devices');
    }
};
