<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ManagementExpense extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus',
        'category',
        'title',
        'description',
        'amount',
        'method',
        'invoice_receipt',
        'date',
        'notify_admin',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'date' => 'date',
        'notify_admin' => 'boolean',
    ];
}

