<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class FeeDecrementAmount extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus',
        'class',
        'section',
        'amount',
        'accountant',
        'date',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
    ];
}

