<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeIncrementPercentage extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus',
        'class',
        'section',
        'increase',
        'accountant',
        'date',
    ];

    protected $casts = [
        'increase' => 'decimal:2',
        'date' => 'date',
    ];
}

