<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Timetable extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus',
        'class',
        'section',
        'subject',
        'day',
        'starting_time',
        'ending_time',
    ];

    // Time fields are stored as time type in database
}
