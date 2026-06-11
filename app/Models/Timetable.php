<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Timetable extends Model
{
    use HasFactory, SoftDeletes;

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
