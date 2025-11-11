<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus',
        'class',
        'section',
        'fee_type',
        'amount',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
    ];
}

