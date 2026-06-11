<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;

class ParticularTestGrade extends Model
{
    use HasFactory;

    /**
     * Primary table is corrupted on some environments (orphan tablespace).
     * Fallback table keeps feature available without data loss.
     */
    public const PRIMARY_TABLE = 'particular_test_grades';
    public const FALLBACK_TABLE = 'particular_test_grades_fallback';

    protected $fillable = [
        'campus',
        'name',
        'from_percentage',
        'to_percentage',
        'for_test',
        'class',
        'section',
        'subject',
        'session',
    ];

    protected $casts = [
        'from_percentage' => 'decimal:2',
        'to_percentage' => 'decimal:2',
    ];

    public function getTable()
    {
        $configured = $this->table ?? static::PRIMARY_TABLE;
        if ($configured !== static::PRIMARY_TABLE) {
            return $configured;
        }

        return Schema::hasTable(static::PRIMARY_TABLE)
            ? static::PRIMARY_TABLE
            : static::FALLBACK_TABLE;
    }
}
