<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_title',
        'event_details',
        'event_type',
        'event_date',
    ];

    protected $casts = [
        'event_date' => 'date',
    ];
}

