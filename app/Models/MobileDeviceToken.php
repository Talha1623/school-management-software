<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MobileDeviceToken extends Model
{
    protected $fillable = [
        'user_type',
        'user_id',
        'device_id',
        'fcm_token',
        'last_login_at',
    ];

    protected $casts = [
        'last_login_at' => 'datetime',
    ];
}
