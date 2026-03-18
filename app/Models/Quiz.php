<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Quiz extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus',
        'quiz_name',
        'description',
        'for_class',
        'section',
        'total_questions',
        'start_date_time',
        'duration_minutes',
    ];

    protected $casts = [
        'start_date_time' => 'datetime',
        'duration_minutes' => 'integer',
        'total_questions' => 'integer',
    ];

    public function questions()
    {
        return $this->hasMany(QuizQuestion::class)->orderBy('question_number');
    }
}

