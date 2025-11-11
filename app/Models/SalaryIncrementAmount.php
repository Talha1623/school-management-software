<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryIncrementAmount extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus',
        'increase',
        'accountant',
        'date',
    ];

    protected $casts = [
        'increase' => 'decimal:2',
        'date' => 'date',
    ];
}

