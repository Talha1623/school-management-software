<?php

namespace App\Http\Controllers;

use App\Models\ParentAccount;
use App\Models\Student;
use App\Models\StudentPayment;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FamilyFeeCalculatorController extends Controller
{
    private function normalizeIdCard(?string $idCard): array
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

    private function findParentAccount(?string $rawIdCard): ?ParentAccount
    {
        if (!$rawIdCard) {
            return null;
        }

        [$cleaned, $lower, $normalized] = $this->normalizeIdCard($rawIdCard);

        return ParentAccount::where(function ($query) use ($lower, $normalized, $rawIdCard) {
            $query->whereRaw('LOWER(TRIM(id_card_number)) = ?', [$lower])
                ->orWhereRaw('TRIM(id_card_number) = ?', [$rawIdCard])
                ->orWhereRaw(
                    'LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(id_card_number), "-", ""), " ", ""), "_", ""), ".", ""), "/", "")) = ?',
                    [$normalized]
                )
                ->orWhereRaw(
                    'LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(id_card_number), "-", ""), " ", ""), "_", ""), ".", ""), "/", "")) LIKE ?',
                    ['%' . $normalized . '%']
                );
        })->first();
    }

    private function findStudentsByFatherIdCard(?string $rawIdCard)
    {
        [$cleaned, $lower, $normalized] = $this->normalizeIdCard($rawIdCard);

        $studentsByFatherIdCard = Student::where(function ($query) use ($cleaned, $normalized, $rawIdCard) {
            $query->where('father_id_card', $cleaned)
                ->orWhere('father_id_card', $rawIdCard)
                ->orWhere('father_id_card', 'LIKE', $cleaned)
                ->orWhere('father_id_card', 'LIKE', '%' . $cleaned . '%')
                ->orWhere('father_id_card', 'LIKE', '%' . $rawIdCard . '%')
                ->orWhereRaw('LOWER(father_id_card) = LOWER(?)', [$cleaned])
                ->orWhereRaw('LOWER(father_id_card) = LOWER(?)', [$rawIdCard])
                ->orWhereRaw('TRIM(father_id_card) = ?', [$cleaned])
                ->orWhereRaw('TRIM(father_id_card) = ?', [$rawIdCard])
                ->orWhereRaw('LOWER(TRIM(father_id_card)) = LOWER(TRIM(?))', [$cleaned])
                ->orWhereRaw('CAST(father_id_card AS CHAR) = ?', [$cleaned])
                ->orWhereRaw('CAST(father_id_card AS CHAR) LIKE ?', ['%' . $cleaned . '%'])
                ->orWhereRaw(
                    'LOWER(REPLACE(REPLACE(REPLACE(REPLACE(REPLACE(TRIM(father_id_card), "-", ""), " ", ""), "_", ""), ".", ""), "/", "")) LIKE ?',
                    ['%' . $normalized . '%']
                );
        })
            ->select(
                'id',
                'student_name',
                'student_code',
                'class',
                'section',
                'campus',
                'monthly_fee',
                'transport_fare',
                'generate_other_fee',
                'other_fee_amount',
                'generate_admission_fee',
                'admission_fee_amount',
                'fee_type',
                'father_name',
                'father_phone',
                'father_email',
                'home_address'
            )
            ->get();

        if ($studentsByFatherIdCard->isNotEmpty()) {
            return $studentsByFatherIdCard;
        }

        return Student::whereRaw(
            'father_id_card = ? OR father_id_card LIKE ? OR LOWER(father_id_card) = LOWER(?)',
            [$cleaned, '%' . $cleaned . '%', $cleaned]
        )
            ->select(
                'id',
                'student_name',
                'student_code',
                'class',
                'section',
                'campus',
                'monthly_fee',
                'transport_fare',
                'generate_other_fee',
                'other_fee_amount',
                'generate_admission_fee',
                'admission_fee_amount',
                'fee_type',
                'father_name',
                'father_phone',
                'father_email',
                'home_address'
            )
            ->get();
    }

    private function buildFatherInfo(?ParentAccount $parentAccount, $students, string $rawIdCard): array
    {
        if ($parentAccount) {
            return [
                'id' => $parentAccount->id,
                'name' => $parentAccount->name,
                'id_card_number' => $parentAccount->id_card_number,
                'phone' => $parentAccount->phone,
                'email' => $parentAccount->email,
                'address' => $parentAccount->address,
            ];
        }

        $firstStudent = $students->first();
        return [
            'id' => null,
            'name' => $firstStudent->father_name ?? 'N/A',
            'id_card_number' => $rawIdCard,
            'phone' => $firstStudent->father_phone ?? 'N/A',
            'email' => $firstStudent->father_email ?? 'N/A',
            'address' => $firstStudent->home_address ?? 'N/A',
        ];
    }

    private function getAccountantName(): ?string
    {
        if (auth()->guard('accountant')->check()) {
            return auth()->guard('accountant')->user()->name ?? null;
        }
        if (auth()->guard('admin')->check()) {
            return auth()->guard('admin')->user()->name ?? null;
        }
        if (auth()->check()) {
            return auth()->user()->name ?? null;
        }
        return null;
    }

    public function payAll(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'father_id_card' => ['required', 'string', 'max:255'],
            'method' => ['nullable', 'string', 'max:255'],
        ]);

        $rawIdCard = $validated['father_id_card'];
        $parentAccount = $this->findParentAccount($rawIdCard);
        $studentsByFatherIdCard = $this->findStudentsByFatherIdCard($rawIdCard);

        $studentsByParentAccount = collect();
        if ($parentAccount) {
            $studentsByParentAccount = Student::where('parent_account_id', $parentAccount->id)
                ->select(
                    'id',
                    'student_name',
                    'student_code',
                    'class',
                    'section',
                    'campus',
                    'monthly_fee',
                    'transport_fare',
                    'generate_other_fee',
                    'other_fee_amount',
                    'generate_admission_fee',
                    'admission_fee_amount',
                    'fee_type'
                )
                ->get();
        }

        $students = $studentsByParentAccount->merge($studentsByFatherIdCard)->unique('id')->sortBy('student_name')->values();

        if ($students->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No children found with this Father ID Card Number',
            ], 404);
        }

        $paymentDate = Carbon::now()->format('Y-m-d');
        $month = Carbon::now()->format('F');
        $year = Carbon::now()->format('Y');
        $method = $validated['method'] ?? 'Cash Payment';
        $accountantName = $this->getAccountantName();

        $receiptStudents = [];
        $grandTotal = 0;

        foreach ($students as $student) {
            $monthlyFee = (float) ($student->monthly_fee ?? 0);
            $transportFee = (float) ($student->transport_fare ?? 0);
            $admissionFee = !empty($student->generate_admission_fee) ? (float) ($student->admission_fee_amount ?? 0) : 0;
            $otherFee = !empty($student->generate_other_fee) ? (float) ($student->other_fee_amount ?? 0) : 0;

            $total = $monthlyFee + $transportFee + $admissionFee + $otherFee;
            $grandTotal += $total;

            $this->payStudentFee($student, "Monthly Fee - {$month} {$year}", $monthlyFee, $method, $paymentDate, $accountantName);
            $this->payStudentFee($student, "Transport Fee - {$month} {$year}", $transportFee, $method, $paymentDate, $accountantName);
            $this->payStudentFee($student, 'Admission Fee', $admissionFee, $method, $paymentDate, $accountantName);

            $otherTitle = !empty($student->fee_type) ? $student->fee_type : 'Other Fee';
            $this->payStudentFee($student, $otherTitle, $otherFee, $method, $paymentDate, $accountantName);

            $receiptStudents[] = [
                'id' => $student->id,
                'student_name' => $student->student_name,
                'student_code' => $student->student_code,
                'class' => $student->class,
                'section' => $student->section,
                'campus' => $student->campus,
                'monthly_fee' => $monthlyFee,
                'transport_fare' => $transportFee,
                'admission_fee_amount' => $admissionFee,
                'other_fee_amount' => $otherFee,
                'total' => $total,
            ];
        }

        $fatherInfo = $this->buildFatherInfo($parentAccount, $students, $rawIdCard);

        return response()->json([
            'success' => true,
            'message' => 'All fees paid successfully.',
            'father' => $fatherInfo,
            'students' => $receiptStudents,
            'grand_total' => $grandTotal,
            'payment_date' => $paymentDate,
            'payment_method' => $method,
        ]);
    }

    private function payStudentFee(Student $student, string $paymentTitle, float $amount, string $method, string $paymentDate, ?string $accountantName): void
    {
        if ($amount <= 0 || empty($student->student_code)) {
            return;
        }

        $existingFee = StudentPayment::where('student_code', $student->student_code)
            ->where('payment_title', $paymentTitle)
            ->where('method', 'Generated')
            ->first();

        if ($existingFee) {
            $existingFee->update([
                'payment_amount' => $amount,
                'discount' => 0,
                'method' => $method,
                'payment_date' => $paymentDate,
                'sms_notification' => 'Yes',
                'late_fee' => $existingFee->late_fee ?? 0,
                'accountant' => $accountantName,
            ]);
            return;
        }

        StudentPayment::create([
            'campus' => $student->campus ?? null,
            'student_code' => $student->student_code,
            'payment_title' => $paymentTitle,
            'payment_amount' => $amount,
            'discount' => 0,
            'method' => $method,
            'payment_date' => $paymentDate,
            'sms_notification' => 'Yes',
            'late_fee' => 0,
            'accountant' => $accountantName,
        ]);
    }
}
