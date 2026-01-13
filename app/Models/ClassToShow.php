<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ClassToShow extends Model
{
    use HasFactory;

    protected $table = 'classes_to_show';

    protected $fillable = [
        'campus',
        'class',
        'class_timing_from',
        'class_timing_to',
        'student_age_limit_from',
        'student_age_limit_to',
        'class_tuition_fee',
        'show_on_website_main_page',
    ];

    protected $casts = [
        'show_on_website_main_page' => 'string',
    ];
}
