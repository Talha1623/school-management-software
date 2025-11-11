<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Test extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus',
        'test_name',
        'for_class',
        'section',
        'subject',
        'test_type',
        'description',
        'date',
        'session',
    ];

    protected $casts = [
        'date' => 'date',
    ];
}

