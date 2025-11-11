<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus',
        'student_code',
        'payment_title',
        'payment_amount',
        'discount',
        'method',
        'payment_date',
        'sms_notification',
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'payment_date' => 'date',
    ];
}

