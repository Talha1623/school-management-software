<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CombinedResultGrade extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus',
        'name',
        'from_percentage',
        'to_percentage',
        'session',
    ];

    protected $casts = [
        'from_percentage' => 'decimal:2',
        'to_percentage' => 'decimal:2',
    ];
}

