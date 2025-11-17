<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Task extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_title',
        'description',
        'type',
        'assign_to',
        'status',
    ];

    protected $casts = [
        'status' => 'string',
    ];
}

