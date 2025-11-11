<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus',
        'exam_name',
        'description',
        'exam_date',
        'session',
    ];

    protected $casts = [
        'exam_date' => 'date',
    ];
}

