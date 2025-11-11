<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CustomPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus',
        'payment_title',
        'payment_amount',
        'accountant',
        'method',
        'notify_admin',
        'payment_date',
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
        'payment_date' => 'date',
    ];
}

