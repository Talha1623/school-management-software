<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;

class StudentPayment extends Model
{
    use HasFactory;

    protected $fillable = [
        'campus',
        'student_code',
        'payment_title',
        'payment_amount',
        'discount',
        'method',
        'payment_date',
        'sms_notification',
        'accountant',
        'late_fee',
    ];
    
    /**
     * Get the student that owns the payment.
     */
    public function student()
    {
        return $this->belongsTo(Student::class, 'student_code', 'student_code');
    }

    protected $casts = [
        'payment_amount' => 'decimal:2',
        'discount' => 'decimal:2',
        'late_fee' => 'decimal:2',
        'payment_date' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $payment) {
            if ($payment->discount === null) {
                $payment->discount = 0;
            }
            if ($payment->late_fee === null) {
                $payment->late_fee = 0;
            }
        });
    }

    /**
     * Exclude rows still present in DB but marked as removed in deleted_fees (audit after fee delete on web).
     * Normal deletes hard-remove the payment row so they never match.
     */
    public function scopeLedgerActive(Builder $query): Builder
    {
        if (!Schema::hasTable('deleted_fees')) {
            return $query;
        }

        $table = $query->getModel()->getTable();

        return $query->whereNotExists(function ($sub) use ($table) {
            $sub->from('deleted_fees')
                ->whereColumn('deleted_fees.original_payment_id', $table . '.id')
                ->whereNotNull('deleted_fees.original_payment_id');
        });
    }

    /**
     * Paid ledger rows for a fee title that apply to the current charge (latest generated/installment row).
     * Excludes payments created before that charge row so a new Custom Fee does not inherit old "Partial" from the same fee name.
     *
     * @param  Collection<int, self>  $paidForTitle
     * @return Collection<int, self>
     */
    public static function paidLedgerRowsForLatestGeneratedTitle(Collection $paidForTitle, ?self $latestGenerated): Collection
    {
        if ($paidForTitle->isEmpty() || $latestGenerated === null) {
            return $paidForTitle;
        }

        $anchor = $latestGenerated->created_at;
        if ($anchor === null) {
            return $paidForTitle;
        }

        return $paidForTitle->filter(function (self $p) use ($anchor) {
            $t = $p->created_at ?? $p->payment_date;
            if ($t === null) {
                return false;
            }

            return $t->greaterThanOrEqualTo($anchor);
        })->values();
    }

    /**
     * Map base fee titles that have installment rows (…/1, …/2) so the lump-sum title is ignored.
     *
     * @return array<string, bool>
     */
    public static function installmentBaseTitlesForStudent(string $studentCode): array
    {
        $map = [];
        $rows = self::query()
            ->ledgerActive()
            ->where('student_code', $studentCode)
            ->distinct()
            ->pluck('payment_title');
        foreach ($rows as $t) {
            if (preg_match('/^(.+)\/\d+$/', (string) $t, $m)) {
                $map[$m[1]] = true;
            }
        }

        return $map;
    }

    /**
     * After splitting a fee into installments (…/1, …/2), remove the lump-sum Generated row
     * so it does not reappear when all installment rows are marked paid.
     */
    public static function removeOrphanedBaseGeneratedForInstallment(string $studentCode, string $installmentTitle): void
    {
        $studentCode = trim($studentCode);
        if ($studentCode === '' || !preg_match('/^(.+)\/\d+$/', trim($installmentTitle), $matches)) {
            return;
        }

        $baseTitle = trim((string) $matches[1]);
        if ($baseTitle === '') {
            return;
        }

        self::query()
            ->ledgerActive()
            ->where('student_code', $studentCode)
            ->where('payment_title', $baseTitle)
            ->whereIn('method', ['Generated', 'Installment'])
            ->delete();
    }

    /**
     * Unpaid balance for one fee title — same rules as StudentPaymentController::getStudentByCode.
     * Fixes vouchers showing e.g. Admission Fee after it was already paid (paid rows net against generated).
     */
    /**
     * @return array{amount: float, late_fee: float, total: float}
     */
    public static function paymentTitleKey(string $title): string
    {
        return strtolower(trim($title));
    }

    public function scopePaymentTitleKey(Builder $query, string $title): Builder
    {
        return $query->whereRaw('LOWER(TRIM(payment_title)) = ?', [self::paymentTitleKey($title)]);
    }

    public static function remainingDuePartsForTitle(string $studentCode, string $title, float $totalStudentDiscount = 0): array
    {
        $title = trim((string) $title);
        $titleKey = self::paymentTitleKey($title);
        $installmentBaseTitles = self::installmentBaseTitlesForStudent($studentCode);

        $items = self::query()
            ->ledgerActive()
            ->where('student_code', $studentCode)
            ->paymentTitleKey($title)
            ->whereIn('method', ['Generated', 'Installment'])
            ->get();

        if ($items->isEmpty()) {
            return ['amount' => 0.0, 'late_fee' => 0.0, 'total' => 0.0];
        }

        $canonicalTitle = (string) ($items->sortByDesc('id')->first()->payment_title ?? $title);
        $isInstallment = (bool) preg_match('/\/\d+$/', $canonicalTitle);
        $installmentBaseTitlesLower = array_change_key_case($installmentBaseTitles, CASE_LOWER);
        if (! $isInstallment && isset($installmentBaseTitlesLower[self::paymentTitleKey($canonicalTitle)])) {
            return ['amount' => 0.0, 'late_fee' => 0.0, 'total' => 0.0];
        }

        $isMonthlyFee = str_starts_with($canonicalTitle, 'Monthly Fee - ');

        $latestGenerated = $items->sortByDesc('id')->first();

        $originalAmount = $items->sum(function ($item) {
            return (float) ($item->payment_amount ?? 0);
        });

        $generatedLate = $items->sum(function ($item) {
            return (float) ($item->late_fee ?? 0);
        });

        $generatedDiscount = $items->sum(function ($item) {
            return (float) ($item->discount ?? 0);
        });

        $paidFees = self::query()
            ->ledgerActive()
            ->where('student_code', $studentCode)
            ->paymentTitleKey($title)
            ->whereNotIn('method', ['Generated', 'Installment'])
            ->get();

        $paidFees = self::paidLedgerRowsForLatestGeneratedTitle($paidFees, $latestGenerated);

        $paidDiscount = $paidFees->sum(function ($item) {
            return (float) ($item->discount ?? 0);
        });

        $appliedStudentDiscount = 0;
        if ($isMonthlyFee && $totalStudentDiscount > 0 && ! $isInstallment) {
            $appliedStudentDiscount = round($totalStudentDiscount, 2);
        }

        $totalDiscount = $generatedDiscount + $paidDiscount + $appliedStudentDiscount;

        $paidAmountOnly = $paidFees->sum(function ($item) {
            $amount = (float) ($item->payment_amount ?? 0);
            $late = (float) ($item->late_fee ?? 0);

            return max(0, $amount - $late);
        });
        $paidLate = $paidFees->sum(function ($item) {
            return (float) ($item->late_fee ?? 0);
        });

        $remainingAmount = max(0, ($originalAmount - $totalDiscount) - $paidAmountOnly);
        $remainingLate = max(0, $generatedLate - $paidLate);

        return [
            'amount' => round($remainingAmount, 2),
            'late_fee' => round($remainingLate, 2),
            'total' => round($remainingAmount + $remainingLate, 2),
        ];
    }

    public static function remainingDueForTitle(string $studentCode, string $title, float $totalStudentDiscount = 0): float
    {
        $parts = self::remainingDuePartsForTitle($studentCode, $title, $totalStudentDiscount);

        return $parts['total'];
    }

    /**
     * Pending fee rows for thermal/particular receipts — same rules as StudentPaymentController::getStudentByCode
     * (installments, skip lump title when /1 /2 exist, discounts), with ledgerActive() on payment queries.
     *
     * @return array{pendingFees: array<int, array{title: string, amount: float, late_fee: float, total: float}>, totalDue: float}
     */
    public static function pendingFeeLinesForThermalReceipt(string $studentCode): array
    {
        $studentCode = trim((string) $studentCode);
        if ($studentCode === '') {
            return ['pendingFees' => [], 'totalDue' => 0.0];
        }

        $generatedFees = self::query()
            ->ledgerActive()
            ->where('student_code', $studentCode)
            ->whereIn('method', ['Generated', 'Installment'])
            ->get();

        $paidFees = self::query()
            ->ledgerActive()
            ->where('student_code', $studentCode)
            ->where('method', '!=', 'Generated')
            ->where('method', '!=', 'Installment')
            ->get();

        $studentDiscounts = \App\Models\StudentDiscount::where('student_code', $studentCode)->get();
        $totalStudentDiscount = $studentDiscounts->sum(function ($discount) {
            return (float) ($discount->discount_amount ?? 0);
        });

        $pendingFees = [];
        $totalDue = 0.0;
        $generatedByTitle = $generatedFees->groupBy('payment_title');
        $paidByTitle = $paidFees->groupBy('payment_title');

        $installmentBaseTitles = self::installmentBaseTitlesForStudent($studentCode);

        foreach ($generatedByTitle as $title => $items) {
            $isInstallment = (bool) preg_match('/\/\d+$/', (string) $title);
            if (! $isInstallment && isset($installmentBaseTitles[$title])) {
                continue;
            }

            $isMonthlyFee = str_starts_with((string) $title, 'Monthly Fee - ');

            $latestGenerated = $items->sortByDesc('id')->first();
            $paidForTitle = self::paidLedgerRowsForLatestGeneratedTitle(
                $paidByTitle->get($title, collect()),
                $latestGenerated
            );

            $originalAmount = $items->sum(function ($item) {
                return (float) ($item->payment_amount ?? 0);
            });

            $generatedLate = $items->sum(function ($item) {
                return (float) ($item->late_fee ?? 0);
            });

            $generatedDiscount = 0;
            if ($isInstallment) {
                $generatedDiscount = $items->sum(function ($item) {
                    return (float) ($item->discount ?? 0);
                });
            }

            $paidDiscount = $paidForTitle->sum(function ($item) {
                return (float) ($item->discount ?? 0);
            });

            $appliedStudentDiscount = 0;
            if ($isMonthlyFee && $totalStudentDiscount > 0 && ! $isInstallment) {
                $appliedStudentDiscount = round($totalStudentDiscount, 2);
            }

            $totalDiscount = $generatedDiscount + $paidDiscount + $appliedStudentDiscount;

            $paidAmountOnly = $paidForTitle->sum(function ($item) {
                $amount = (float) ($item->payment_amount ?? 0);
                $late = (float) ($item->late_fee ?? 0);

                return max(0, $amount - $late);
            });
            $paidLate = $paidForTitle->sum(function ($item) {
                return (float) ($item->late_fee ?? 0);
            });

            $remainingAmount = max(0, ($originalAmount - $totalDiscount) - $paidAmountOnly);
            $remainingLate = max(0, $generatedLate - $paidLate);
            $remainingTotal = $remainingAmount + $remainingLate;

            if ($remainingTotal > 0) {
                $pendingFees[] = [
                    'title' => $title,
                    'amount' => round($remainingAmount, 2),
                    'late_fee' => round($remainingLate, 2),
                    'total' => round($remainingTotal, 2),
                ];
                $totalDue += $remainingTotal;
            }
        }

        return ['pendingFees' => $pendingFees, 'totalDue' => round($totalDue, 2)];
    }
}

