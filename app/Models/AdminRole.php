<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;

class AdminRole extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'name',
        'phone',
        'email',
        'photo',
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
     * Hash plain-text passwords only (avoid double-hash when controller already used Hash::make).
     */
    public function setPasswordAttribute($value): void
    {
        if ($value === null || $value === '') {
            return;
        }

        $value = (string) $value;

        if (preg_match('/^\$2[ayb]\$.{56}$/', $value)) {
            $this->attributes['password'] = $value;

            return;
        }

        $this->attributes['password'] = Hash::make($value);
    }

    /**
     * Check if admin is super admin
     */
    public function isSuperAdmin(): bool
    {
        return $this->super_admin === true;
    }

    public static function ensurePhotoColumn(): void
    {
        $table = (new static)->getTable();

        if (! Schema::hasTable($table) || Schema::hasColumn($table, 'photo')) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) {
            $blueprint->string('photo')->nullable();
        });
    }

    public function profilePhotoUrl(?string $default = null): string
    {
        $default ??= asset('assets/images/admin.png');

        if (! Schema::hasColumn($this->getTable(), 'photo')) {
            return $default;
        }

        $photo = trim((string) ($this->photo ?? ''));

        if ($photo === '') {
            return $default;
        }

        if (filter_var($photo, FILTER_VALIDATE_URL)) {
            return $photo;
        }

        if (str_starts_with($photo, 'storage/')) {
            return asset($photo);
        }

        return Storage::disk('public')->exists($photo)
            ? Storage::url($photo)
            : $default;
    }
}

