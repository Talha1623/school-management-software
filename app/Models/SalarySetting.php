<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalarySetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'late_arrival_time',
        'free_absents',
        'leave_deduction',
    ];

    protected $casts = [
        'late_arrival_time' => 'string',
        'free_absents' => 'integer',
    ];

    /**
     * Get the first (and only) salary setting record.
     */
    public static function getSettings()
    {
        return self::first() ?? self::create([
            'late_arrival_time' => '08:00:00',
            'free_absents' => 2,
            'leave_deduction' => 'No',
        ]);
    }
}

