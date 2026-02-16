<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BiometricDevice extends Model
{
    use HasFactory;

    protected $fillable = [
        'device_name',
        'device_model',
        'device_serial_number',
        'device_ip_address',
        'device_port',
        'device_password',
        'campus',
        'location',
        'status',
        'connection_type',
        'last_sync_date',
        'total_users',
        'total_fingerprints',
        'firmware_version',
        'notes',
    ];

    protected $casts = [
        'last_sync_date' => 'datetime',
        'device_port' => 'integer',
        'total_users' => 'integer',
        'total_fingerprints' => 'integer',
    ];
}
