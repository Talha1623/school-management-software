<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StudentNotification extends Model
{
    use HasFactory;

    protected $fillable = [
        'student_id',
        'title',
        'message',
        'data',
        'read_at',
        'created_by_type',
        'created_by_id',
    ];

    protected $casts = [
        'data' => 'array',
        'read_at' => 'datetime',
    ];

    public function student()
    {
        return $this->belongsTo(Student::class);
    }
}

