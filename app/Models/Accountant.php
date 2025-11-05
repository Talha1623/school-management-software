<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Accountant extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'campus',
        'password',
        'app_login_enabled',
        'web_login_enabled',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'app_login_enabled' => 'boolean',
        'web_login_enabled' => 'boolean',
    ];
}
