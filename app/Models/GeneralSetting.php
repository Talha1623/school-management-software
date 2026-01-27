<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class GeneralSetting extends Model
{
    use HasFactory;

    protected $table = 'general_settings';

    protected $fillable = [
        'school_name',
        'sms_signature',
        'address',
        'school_phone',
        'school_email',
        'currency',
        'timezone',
    ];

    public static function getSettings(): self
    {
        return self::first() ?? self::create([
            'currency' => 'PKR',
            'timezone' => 'Asia/Karachi',
        ]);
    }
}
