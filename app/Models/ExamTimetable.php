<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamTimetable extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus',
        'class',
        'section',
        'subject',
        'exam_name',
        'exam_date',
        'starting_time',
        'ending_time',
        'room_block',
    ];
}
