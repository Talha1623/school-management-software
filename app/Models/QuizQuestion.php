<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QuizQuestion extends Model
{
    protected $fillable = [
        'quiz_id',
        'question_number',
        'question',
        'answer1',
        'marks1',
        'answer2',
        'marks2',
        'answer3',
        'marks3',
    ];

    protected $casts = [
        'marks1' => 'integer',
        'marks2' => 'integer',
        'marks3' => 'integer',
        'question_number' => 'integer',
    ];

    public function quiz(): BelongsTo
    {
        return $this->belongsTo(Quiz::class);
    }
}
