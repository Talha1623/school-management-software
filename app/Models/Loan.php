<?php

namespace App\Models;

use App\Services\StaffLoanRepaymentService;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class Loan extends Model
{
    use HasFactory;

    protected $fillable = [
        'staff_id',
        'requested_amount',
        'approved_amount',
        'initial_approved_amount',
        'repayment_instalments',
        'status',
    ];

    protected $casts = [
        'requested_amount' => 'decimal:2',
        'approved_amount' => 'decimal:2',
        'initial_approved_amount' => 'decimal:2',
        'repayment_instalments' => 'integer',
    ];

    /**
     * Tenant DBs may exist before the balance column migration ran — add column on first use.
     */
    public static function ensureBalanceColumns(): void
    {
        if (! Schema::hasTable('loans')) {
            return;
        }

        if (Schema::hasColumn('loans', 'initial_approved_amount')) {
            return;
        }

        try {
            Schema::table('loans', function (Blueprint $table) {
                $table->decimal('initial_approved_amount', 12, 2)->nullable()->after('approved_amount');
            });
        } catch (\Throwable $e) {
            if (Schema::hasColumn('loans', 'initial_approved_amount')) {
                return;
            }

            throw $e;
        }
    }

    protected static function booted(): void
    {
        static::saving(function () {
            static::ensureBalanceColumns();
        });
    }

    /**
     * Get the staff member that owns the loan.
     */
    public function staff()
    {
        return $this->belongsTo(Staff::class);
    }

    /**
     * Amount already recovered via salary deductions.
     */
    public function amountPaid(): float
    {
        return app(StaffLoanRepaymentService::class)->paidAmountForLoan($this);
    }

    /**
     * Remaining balance to be recovered from salary.
     */
    public function remainingAmount(): float
    {
        return round(max(0, $this->totalApprovedAmount() - $this->amountPaid()), 2);
    }

    /**
     * Original approved loan amount (before repayments).
     */
    public function totalApprovedAmount(): float
    {
        $requested = max(0, (float) ($this->requested_amount ?? 0));

        if (static::hasInitialApprovedColumn()) {
            $initial = (float) ($this->initial_approved_amount ?? 0);

            return round(max($requested, $initial), 2);
        }

        return round(max($requested, (float) ($this->approved_amount ?? 0)), 2);
    }

    public function effectiveStatus(): string
    {
        if ($this->status === 'Rejected') {
            return 'Rejected';
        }

        if ($this->status === 'Pending') {
            return 'Pending';
        }

        $paid = $this->amountPaid();
        $remaining = $this->remainingAmount();

        if ($paid > 0 && $remaining <= 0.009) {
            return 'Completed';
        }

        return 'Approved';
    }

    public static function hasInitialApprovedColumn(): bool
    {
        return Schema::hasTable('loans')
            && Schema::hasColumn('loans', 'initial_approved_amount');
    }
}
