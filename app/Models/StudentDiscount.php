<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentDiscount extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_code',
        'discount_title',
        'discount_amount',
        'created_by',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class, 'student_code', 'student_code');
    }
}
