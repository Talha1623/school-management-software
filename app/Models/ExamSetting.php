<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ExamSetting extends Model
{
    use HasFactory;

    protected $table = 'exam_settings';

    protected $fillable = [
        'admit_card_instructions',
        'fail_student_if',
    ];

    public static function getSettings(): self
    {
        return self::first() ?? self::create([
            'admit_card_instructions' => '',
            'fail_student_if' => '',
        ]);
    }
}
