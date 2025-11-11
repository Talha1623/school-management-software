<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeDecrementPercentage extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus',
        'class',
        'section',
        'decrement',
        'accountant',
        'date',
    ];

    protected $casts = [
        'decrement' => 'decimal:2',
        'date' => 'date',
    ];
}

