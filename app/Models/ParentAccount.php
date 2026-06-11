<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\HasApiTokens;

class ParentAccount extends Authenticatable
{
    use HasFactory, Notifiable, HasApiTokens;

    protected $table = 'parent_accounts';

    protected $fillable = [
        'name',
        'email',
        'password',
        'plain_password',
        'phone',
        'whatsapp',
        'id_card_number',
        'address',
        'profession',
        'api_token',
    ];

    protected $hidden = [
        'password',
        'remember_token',
        'api_token',
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
     * Check if parent has login access (has email and password)
     */
    public function hasLoginAccess(): bool
    {
        return !empty($this->email) && !empty($this->password);
    }

    /**
     * Get the students for this parent account.
     */
    public function students()
    {
        return $this->hasMany(Student::class, 'parent_account_id');
    }

    public function displayPassword(): string
    {
        $plain = $this->plain_password ?? null;

        return $plain !== null && $plain !== '' ? (string) $plain : 'parent';
    }

    public function hasPlaceholderEmail(): bool
    {
        $email = strtolower(trim((string) ($this->email ?? '')));

        if ($email === '') {
            return true;
        }

        return str_contains($email, '@placeholder.')
            || str_ends_with($email, '@parent.local')
            || str_ends_with($email, '@noemail.local');
    }
}

