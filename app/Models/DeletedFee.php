<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DeletedFee extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus',
        'student_code',
        'student_name',
        'parent_name',
        'payment_title',
        'payment_amount',
        'discount',
        'method',
        'payment_date',
        'deleted_by',
        'reason',
        'deleted_at',
        'original_payment_id',
        'original_data',
    ];

    protected $casts = [
        'payment_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'payment_date' => 'date',
        'deleted_at' => 'datetime',
        'original_data' => 'array',
    ];
}
