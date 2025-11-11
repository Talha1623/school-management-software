<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class BehaviorCategory extends Model
{
    use HasFactory;

    protected $fillable = [
        'category_name',
        'campus',
    ];
}

