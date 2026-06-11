<?php

namespace App\Services;

use App\Models\AdvanceFee;
use App\Models\ParentAccount;
use App\Models\Student;
use App\Models\StudentPayment;
use Carbon\Carbon;
use Illuminate\Support\Collection;

/**
 * Parent wallet transaction list aligned with web fee logic:
 * — StudentPayment::ledgerActive() (same as GET /fee-payment/history)
 * — Wallet deduction matches fee payment code: payment_amount + late_fee
 * — Default list order: newest first (same as fee-payment/history)
 */
final class ParentWalletHistory
{
    /**
     * Actual amount removed from AdvanceFee when a fee is paid via Wallet.
     */
    public static function walletDebitFromPayment(StudentPayment $payment): float
    {
        return AdvanceFeeWallet::walletDebitFromLedgerPayment($payment);
    }

    /**
     * Student codes whose wallet payments belong to this parent (same children scope as wallet balance).
     *
     * @return array<int, string>
     */
    public static function studentCodesForParent(ParentAccount $parent): array
    {
        $codes = $parent->students()->pluck('student_code')->filter()->values();

        if (!empty($parent->id_card_number)) {
            $extra = Student::where('father_id_card', $parent->id_card_number)
                ->pluck('student_code')
                ->filter();
            $codes = $codes->merge($extra)->unique()->values();
        }

        return $codes->toArray();
    }

    /**
     * @param  array{month?: int, year?: int, search?: string}  $filters
     * @return array{
     *     transactions: Collection<int, array<string, mixed>>,
     *     totalIncrease: float,
     *     totalDecrease: float,
     *     currentBalance: float,
     *     walletPaidSum: float,
     *     ledgerNet: float
     * }
     */
    public static function buildTransactions(
        AdvanceFee $advanceFee,
        ParentAccount $parent,
        array $filters = []
    ): array {
        $studentCodes = self::studentCodesForParent($parent);

        $students = $parent->students()->select('id', 'student_name', 'student_code')->get();
        $studentByCode = $students->keyBy('student_code');
        if (!empty($parent->id_card_number)) {
            $byCard = Student::where('father_id_card', $parent->id_card_number)
                ->select('id', 'student_name', 'student_code')
                ->get();
            foreach ($byCard as $stu) {
                if ($stu->student_code && !$studentByCode->has($stu->student_code)) {
                    $studentByCode->put($stu->student_code, $stu);
                }
            }
        }

        if ($studentCodes === []) {
            $walletPayments = collect();
        } else {
            $walletPaymentsQuery = StudentPayment::query()
                ->ledgerActive()
                ->whereIn('student_code', $studentCodes)
                ->whereRaw('LOWER(TRIM(method)) = ?', ['wallet']);

            if (!empty($filters['month'])) {
                $walletPaymentsQuery->whereMonth('payment_date', (int) $filters['month']);
            }
            if (!empty($filters['year'])) {
                $walletPaymentsQuery->whereYear('payment_date', (int) $filters['year']);
            }

            $walletPayments = $walletPaymentsQuery
                ->orderBy('payment_date', 'asc')
                ->orderBy('id', 'asc')
                ->get();
        }

        $totalIncrease = (float) ($advanceFee->increase ?? 0);
        $totalDecrease = (float) ($advanceFee->decrease ?? 0);
        $currentBalance = (float) ($advanceFee->available_credit ?? 0);

        $walletPaidSum = round((float) $walletPayments->sum(function ($p) {
            return self::walletDebitFromPayment($p);
        }), 2);

        $transactions = collect();

        if ($totalIncrease > 0) {
            $transactions->push([
                'id' => 'credit-' . $advanceFee->id,
                'date' => $advanceFee->created_at ? $advanceFee->created_at->format('Y-m-d') : null,
                'date_formatted' => $advanceFee->created_at ? $advanceFee->created_at->format('d M Y') : null,
                'type' => 'credit',
                'title' => 'Wallet Credit Added',
                'description' => 'Wallet top-up / credit received',
                'student_id' => null,
                'student_name' => null,
                'student_code' => null,
                'payment_method' => 'Wallet',
                'transaction_ref' => (string) $advanceFee->id,
                'credit' => round($totalIncrease, 2),
                'debit' => 0.0,
                'amount' => round($totalIncrease, 2),
                'payment_amount' => null,
                'discount' => null,
                'late_fee' => null,
                'payment_date' => null,
                'payment_date_formatted' => null,
                'accountant' => null,
            ]);
        }

        foreach ($walletPayments as $payment) {
            $student = $studentByCode->get($payment->student_code);
            $debitTotal = self::walletDebitFromPayment($payment);
            $paymentDate = $payment->payment_date ? Carbon::parse($payment->payment_date) : null;

            $transactions->push([
                'id' => 'debit-' . $payment->id,
                'date' => $paymentDate ? $paymentDate->format('Y-m-d') : null,
                'date_formatted' => $paymentDate ? $paymentDate->format('d M Y') : null,
                'type' => 'debit',
                'title' => 'Fee Payment via Wallet',
                'description' => $payment->payment_title ?? 'Wallet fee payment',
                'student_id' => $student->id ?? null,
                'student_name' => $student->student_name ?? null,
                'student_code' => $payment->student_code ?? null,
                'payment_method' => $payment->method ?? 'Wallet',
                'transaction_ref' => (string) $payment->id,
                'credit' => 0.0,
                'debit' => $debitTotal,
                'amount' => $debitTotal,
                'payment_amount' => (float) ($payment->payment_amount ?? 0),
                'discount' => (float) ($payment->discount ?? 0),
                'late_fee' => (float) ($payment->late_fee ?? 0),
                'payment_date' => $paymentDate ? $paymentDate->format('Y-m-d H:i:s') : null,
                'payment_date_formatted' => $paymentDate ? $paymentDate->format('d-m-Y h:i:s A') : null,
                'accountant' => $payment->accountant ?? null,
            ]);
        }

        $manualDebitAdjustment = round($totalDecrease - $walletPaidSum, 2);
        if ($manualDebitAdjustment > 0) {
            $transactions->push([
                'id' => 'debit-adjust-' . $advanceFee->id,
                'date' => $advanceFee->updated_at ? $advanceFee->updated_at->format('Y-m-d') : null,
                'date_formatted' => $advanceFee->updated_at ? $advanceFee->updated_at->format('d M Y') : null,
                'type' => 'debit',
                'title' => 'Wallet Adjustment',
                'description' => 'Manual wallet deduction/adjustment',
                'student_id' => null,
                'student_name' => null,
                'student_code' => null,
                'payment_method' => 'Wallet',
                'transaction_ref' => (string) $advanceFee->id,
                'credit' => 0.0,
                'debit' => $manualDebitAdjustment,
                'amount' => $manualDebitAdjustment,
                'payment_amount' => null,
                'discount' => null,
                'late_fee' => null,
                'payment_date' => null,
                'payment_date_formatted' => null,
                'accountant' => null,
            ]);
        }

        $transactions = $transactions
            ->sortBy([
                ['date', 'asc'],
                ['transaction_ref', 'asc'],
            ])
            ->values();

        $running = 0.0;
        $transactions = $transactions->map(function (array $t) use (&$running) {
            $delta = (float) ($t['credit'] ?? 0) - (float) ($t['debit'] ?? 0);
            $running = max(0.0, round($running + $delta, 2));
            $t['running_balance'] = $running;
            $t['formatted_amount'] = 'Rs. ' . number_format((float) ($t['amount'] ?? 0), 2);
            $t['formatted_running_balance'] = 'Rs. ' . number_format($t['running_balance'], 2);

            return $t;
        });

        // Same default order as web fee-payment/history: newest payment first
        $transactions = $transactions->reverse()->values();

        if (!empty($filters['search'])) {
            $search = strtolower(trim((string) $filters['search']));
            $transactions = $transactions->filter(function (array $t) use ($search) {
                return str_contains(strtolower((string) ($t['title'] ?? '')), $search)
                    || str_contains(strtolower((string) ($t['description'] ?? '')), $search)
                    || str_contains(strtolower((string) ($t['student_name'] ?? '')), $search)
                    || str_contains(strtolower((string) ($t['student_code'] ?? '')), $search)
                    || str_contains(strtolower((string) ($t['transaction_ref'] ?? '')), $search);
            })->values();
        }

        $ledgerNet = round($totalIncrease - $totalDecrease, 2);

        return [
            'transactions' => $transactions,
            'totalIncrease' => $totalIncrease,
            'totalDecrease' => $totalDecrease,
            'currentBalance' => $currentBalance,
            'walletPaidSum' => $walletPaidSum,
            'ledgerNet' => $ledgerNet,
        ];
    }
}
