<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StaffAttendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'attendance_date',
        'status',
        'start_time',
        'end_time',
        'campus',
        'designation',
        'remarks',
    ];

    protected $casts = [
        'attendance_date' => 'date',
    ];

    /**
     * Get the staff member that owns the attendance.
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}

