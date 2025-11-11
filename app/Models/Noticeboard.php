<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Noticeboard extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus',
        'title',
        'notice',
        'date',
        'image',
        'show_on',
    ];

    protected $casts = [
        'date' => 'date',
    ];
}
