<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;

class Accountant extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'accountants';

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
        'remember_token',
    ];

    protected $casts = [
        'app_login_enabled' => 'boolean',
        'web_login_enabled' => 'boolean',
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
     * Check if accountant has web login access
     */
    public function hasWebLoginAccess(): bool
    {
        return $this->web_login_enabled === true;
    }
}
