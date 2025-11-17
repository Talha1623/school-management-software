<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'attendance_date',
        'status',
        'campus',
        'class',
        'section',
        'remarks',
    ];

    protected $casts = [
        'attendance_date' => 'date',
    ];

    /**
     * Get the student that owns the attendance.
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}

