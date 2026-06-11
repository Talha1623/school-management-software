<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StaffDeviceToken extends Model
{
    protected $fillable = [
        'staff_id',
        'fcm_token',
        'platform',
        'is_active',
        'last_used_at',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'last_used_at' => 'datetime',
    ];
}

