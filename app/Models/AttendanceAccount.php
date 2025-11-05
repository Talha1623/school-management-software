<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AttendanceAccount extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_name',
        'user_id_card',
        'password',
        'campus',
    ];
}
