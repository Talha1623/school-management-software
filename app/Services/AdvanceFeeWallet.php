<?php

namespace App\Services;

use App\Models\AdvanceFee;
use App\Models\Student;
use App\Models\StudentPayment;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

/**
 * Parent advance-fee wallet: credit/debit tied to fee payments (Manage Advance Fees).
 */
final class AdvanceFeeWallet
{
    public static function isWalletMethod(?string $method): bool
    {
        $m = strtolower(trim((string) $method));

        return $m === 'wallet'
            || str_contains($m, 'wallet')
            || str_contains($m, 'advance fee')
            || str_contains($m, 'advance fees');
    }

    /**
     * SQL filter for wallet / advance-fee ledger methods (Detailed Income, reports).
     */
    public static function applyWalletMethodWhere(Builder $query, string $column = 'method'): void
    {
        $query->where(function ($q) use ($column) {
            $q->whereRaw('LOWER(TRIM(' . $column . ')) LIKE ?', ['%wallet%'])
                ->orWhereRaw('LOWER(TRIM(' . $column . ')) LIKE ?', ['%advance fee%']);
        });
    }

    /** Unpaid ledger rows — not counted as received income. */
    public static function isUnpaidLedgerMethod(?string $method): bool
    {
        $m = strtolower(trim((string) $method));

        return $m === 'generated' || $m === 'installment';
    }

    /**
     * Amount removed from available_credit — matches StudentPaymentController::applyWalletPayment (gross payment_amount).
     */
    public static function walletDebitFromLedgerPayment(StudentPayment|array $payment): float
    {
        $amount = is_array($payment)
            ? ($payment['payment_amount'] ?? 0)
            : ($payment->payment_amount ?? 0);

        return round(max(0, (float) $amount), 2);
    }

    /**
     * @return array{0: string, 1: string, 2: string}
     */
    private static function normalizeIdCard(?string $idCard): array
    {
        $digitMap = [
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ];
        $cleaned = trim(strtr((string) $idCard, $digitMap));
        $lower = strtolower($cleaned);
        $normalized = str_replace(['-', ' ', '_', '.', '/'], '', $lower);

        return [$cleaned, $lower, $normalized];
    }

    public static function findByIdCard(?string $idCard): ?AdvanceFee
    {
        if ($idCard === null || trim($idCard) === '') {
            return null;
        }

        [$cleaned, $lower, $normalized] = self::normalizeIdCard($idCard);

        return AdvanceFee::query()
            ->where(function ($query) use ($cleaned, $lower, $normalized) {
                $query->whereRaw('LOWER(TRIM(id_card_number)) = ?', [$lower])
                    ->orWhereRaw('TRIM(id_card_number) = ?', [$cleaned])
                    ->orWhereRaw(
                        'LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(id_card_number), "-", ""), " ", ""), "_", ""), ".", ""), "/", "")) = ?',
                        [$normalized]
                    );
            })
            ->orderByDesc('id')
            ->first();
    }

    public static function findForStudent(?Student $student): ?AdvanceFee
    {
        if ($student === null) {
            return null;
        }

        if (!empty($student->parent_account_id)) {
            $byParent = AdvanceFee::query()
                ->where('parent_id', (string) $student->parent_account_id)
                ->orderByDesc('id')
                ->first();
            if ($byParent) {
                return $byParent;
            }
        }

        return self::findByIdCard($student->father_id_card);
    }

    public static function findForStudentCode(?string $studentCode): ?AdvanceFee
    {
        if ($studentCode === null || trim($studentCode) === '') {
            return null;
        }

        $student = Student::query()
            ->where('student_code', $studentCode)
            ->first();

        return self::findForStudent($student);
    }

    /**
     * Deduct from parent wallet when a fee is paid via Wallet.
     *
     * @return array{ok: bool, debited: float, message: string}
     */
    public static function debitForStudent(Student $student, float $amount): array
    {
        $amount = round(max(0, $amount), 2);
        if ($amount <= 0) {
            return ['ok' => true, 'debited' => 0.0, 'message' => ''];
        }

        $advanceFee = self::findForStudent($student);
        if (!$advanceFee) {
            return [
                'ok' => false,
                'debited' => 0.0,
                'message' => "No wallet found for this student's parent.",
            ];
        }

        $available = max(0, (float) ($advanceFee->available_credit ?? 0));
        $debit = min($amount, $available);
        if ($debit <= 0) {
            return [
                'ok' => false,
                'debited' => 0.0,
                'message' => 'Insufficient wallet balance. Available: Rs. ' . number_format($available, 2),
            ];
        }

        DB::transaction(function () use ($advanceFee, $debit) {
            $advanceFee->refresh();
            $advanceFee->available_credit = max(0, round((float) ($advanceFee->available_credit ?? 0) - $debit, 2));
            $advanceFee->decrease = round((float) ($advanceFee->decrease ?? 0) + $debit, 2);
            $advanceFee->save();
        });

        return ['ok' => true, 'debited' => $debit, 'message' => ''];
    }

    /**
     * When a wallet fee payment is deleted, return the debited amount to Manage Advance Fees.
     */
    public static function restoreCreditOnPaymentDelete(StudentPayment $payment, ?Student $student = null): float
    {
        if (!self::isWalletMethod($payment->method)) {
            return 0.0;
        }

        $amount = self::walletDebitFromLedgerPayment($payment);
        if ($amount <= 0) {
            return 0.0;
        }

        $student ??= Student::query()
            ->where('student_code', $payment->student_code)
            ->first();

        $advanceFee = self::findForStudent($student);
        if (!$advanceFee && !empty($payment->student_code)) {
            $advanceFee = self::findForStudentCode((string) $payment->student_code);
        }

        if (!$advanceFee) {
            return 0.0;
        }

        DB::transaction(function () use ($advanceFee, $amount) {
            $advanceFee->refresh();
            $advanceFee->available_credit = round((float) ($advanceFee->available_credit ?? 0) + $amount, 2);
            $advanceFee->decrease = max(0, round((float) ($advanceFee->decrease ?? 0) - $amount, 2));
            $advanceFee->save();
        });

        return $amount;
    }

    /**
     * When a deleted wallet payment is restored, deduct the wallet again (if balance allows).
     *
     * @param  array<string, mixed>  $paymentData
     * @return array{ok: bool, message: string, amount: float}
     */
    public static function deductCreditOnPaymentRestore(array $paymentData, ?Student $student = null): array
    {
        $method = (string) ($paymentData['method'] ?? '');
        if (!self::isWalletMethod($method)) {
            return ['ok' => true, 'message' => '', 'amount' => 0.0];
        }

        $amount = self::walletDebitFromLedgerPayment($paymentData);
        if ($amount <= 0) {
            return ['ok' => true, 'message' => '', 'amount' => 0.0];
        }

        $studentCode = (string) ($paymentData['student_code'] ?? '');
        $student ??= $studentCode !== ''
            ? Student::query()->where('student_code', $studentCode)->first()
            : null;

        $advanceFee = self::findForStudent($student);
        if (!$advanceFee) {
            return [
                'ok' => false,
                'message' => 'No parent wallet found for this student. Cannot restore a wallet payment.',
                'amount' => $amount,
            ];
        }

        $available = max(0, (float) ($advanceFee->available_credit ?? 0));
        if ($available < $amount - 0.01) {
            return [
                'ok' => false,
                'message' => 'Insufficient wallet balance to restore this payment. Available: Rs. '
                    . number_format($available, 2) . ', required: Rs. ' . number_format($amount, 2) . '.',
                'amount' => $amount,
            ];
        }

        DB::transaction(function () use ($advanceFee, $amount) {
            $advanceFee->refresh();
            $advanceFee->available_credit = max(0, round((float) ($advanceFee->available_credit ?? 0) - $amount, 2));
            $advanceFee->decrease = round((float) ($advanceFee->decrease ?? 0) + $amount, 2);
            $advanceFee->save();
        });

        return ['ok' => true, 'message' => '', 'amount' => $amount];
    }
}
