<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transport extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus',
        'route_name',
        'number_of_vehicle',
        'description',
        'route_fare',
    ];

    protected $casts = [
        'number_of_vehicle' => 'integer',
        'route_fare' => 'decimal:2',
    ];
}
