<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeType extends Model
{
    use HasFactory;

    protected $fillable = [
        'fee_name',
        'campus',
    ];

    /**
     * Ensure a fee head exists for this campus (matches CustomFeeController / fee-type listing: LOWER(TRIM(...))).
     */
    public static function ensureExistsForCampus(string $feeName, string $campus): void
    {
        $feeName = trim($feeName);
        $campus = trim($campus);
        if ($feeName === '' || $campus === '') {
            return;
        }

        $exists = static::query()
            ->whereRaw('LOWER(TRIM(fee_name)) = ?', [strtolower($feeName)])
            ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)])
            ->exists();

        if (! $exists) {
            static::create([
                'fee_name' => $feeName,
                'campus' => $campus,
            ]);
        }
    }
}

