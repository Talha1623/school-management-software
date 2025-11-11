<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'requested_amount',
        'approved_amount',
        'repayment_instalments',
        'status',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'repayment_instalments' => 'integer',
    ];

    /**
     * Get the staff member that owns the loan.
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}

