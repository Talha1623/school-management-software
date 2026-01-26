<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Campus extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus_name',
        'code_prefix',
        'campus_address',
        'phone',
        'email',
        'description',
    ];
}
