<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'salary_month',
        'year',
        'present',
        'absent',
        'late',
        'leaves',
        'holidays',
        'sundays',
        'basic',
        'salary_generated',
        'amount_paid',
        'loan_repayment',
        'status',
    ];

    protected $casts = [
        'present' => 'integer',
        'absent' => 'integer',
        'late' => 'integer',
        'leaves' => 'integer',
        'holidays' => 'integer',
        'sundays' => 'integer',
        'basic' => 'decimal:2',
        'salary_generated' => 'decimal:2',
        'amount_paid' => 'decimal:2',
        'loan_repayment' => 'decimal:2',
    ];

    /**
     * Get the staff member that owns the salary.
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }
}

