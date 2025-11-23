<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HomeworkDiary extends Model
{
    use HasFactory;

    protected $fillable = [
        'subject_id',
        'campus',
        'class',
        'section',
        'date',
        'homework_content',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function subject()
    {
        return $this->belongsTo(Subject::class);
    }
}
