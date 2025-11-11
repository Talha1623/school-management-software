<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SalaryDecrementPercentage extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus',
        'decrease',
        'accountant',
        'date',
    ];

    protected $casts = [
        'decrease' => 'decimal:2',
        'date' => 'date',
    ];
}

