<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdvanceFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'parent_id',
        'name',
        'email',
        'phone',
        'id_card_number',
        'available_credit',
        'increase',
        'decrease',
        'childs',
    ];

    protected $casts = [
        'available_credit' => 'decimal:2',
        'increase' => 'decimal:2',
        'decrease' => 'decimal:2',
    ];
}

