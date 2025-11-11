<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MonthlyFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus',
        'class',
        'section',
        'fee_month',
        'fee_year',
        'due_date',
        'late_fee',
    ];

    protected $casts = [
        'due_date' => 'date',
        'late_fee' => 'decimal:2',
    ];
}

