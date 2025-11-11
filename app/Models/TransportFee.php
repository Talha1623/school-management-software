<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TransportFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus',
        'class',
        'section',
        'fee_month',
        'fee_year',
    ];

    protected $casts = [
        'fee_month' => 'string',
        'fee_year' => 'string',
    ];
}

