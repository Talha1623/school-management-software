<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class BalanceSheetSettlement extends Model
{
    protected $table = 'balance_sheet_settlements';

    protected $fillable = [
        'settlement_date',
        'campus',
        'user_type',
        'user_name',
        'created_by_type',
        'created_by_name',
        'total_payment',
        'method',
        'transaction_id',
        'remarks',
        'receipt_path',
    ];

    protected $casts = [
        'settlement_date' => 'date',
        'total_payment' => 'decimal:2',
    ];
}
