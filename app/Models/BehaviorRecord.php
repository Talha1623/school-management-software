<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BehaviorRecord extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'student_name',
        'type',
        'class',
        'section',
        'campus',
        'date',
        'description',
        'recorded_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /**
     * Get the student that owns the behavior record.
     */
    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}

