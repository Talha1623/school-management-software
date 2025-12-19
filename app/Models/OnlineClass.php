<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OnlineClass extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus',
        'class',
        'section',
        'class_topic',
        'start_date',
        'start_time',
        'timing',
        'password',
        'link',
        'created_by',
    ];

    protected $casts = [
        'start_date' => 'date',
    ];
}
