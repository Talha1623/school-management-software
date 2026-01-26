<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Models\Student;

class OnlineRejectedPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'payment_id',
        'student_code',
        'parent_name',
        'paid_amount',
        'expected_amount',
        'payment_date',
        'status',
        'remarks',
    ];

    protected $casts = [
        'paid_amount' => 'decimal:2',
        'expected_amount' => 'decimal:2',
        'payment_date' => 'date',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_code', 'student_code');
    }
}
