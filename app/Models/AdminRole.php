<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class AdminRole extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'password',
        'admin_of',
        'super_admin',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected $casts = [
        'super_admin' => 'boolean',
    ];

    /**
     * Set the password attribute (hash it)
     */
    public function setPasswordAttribute($value)
    {
        if (!empty($value)) {
            $this->attributes['password'] = Hash::make($value);
        }
    }

    /**
     * Check if admin is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->super_admin === true;
    }
}

