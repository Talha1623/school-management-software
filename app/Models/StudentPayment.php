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
        'accountant',
        'late_fee',
    ];
    
    /**
     * Get the student that owns the payment.
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_code', 'student_code');
    }

    protected $casts = [
        'payment_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'late_fee' => 'decimal:2',
        'payment_date' => 'datetime',
    ];
}

