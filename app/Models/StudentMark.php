<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentMark extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'test_name',
        'campus',
        'class',
        'section',
        'subject',
        'marks_obtained',
        'total_marks',
        'passing_marks',
        'teacher_remarks',
    ];

    protected $casts = [
        'marks_obtained' => 'decimal:2',
        'total_marks' => 'decimal:2',
        'passing_marks' => 'decimal:2',
    ];

    /**
     * Get the student that owns the mark.
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class);
    }
}
