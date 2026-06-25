<?php

namespace App\Http\Controllers;

use App\Models\CustomFee;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\FeeType;
use App\Models\AdminRole;
use App\Models\Message;
use App\Models\Student;
use App\Models\StudentPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Carbon\Carbon;

class CustomFeeController extends Controller
{
    /**
     * Display the generate custom fee form.
     */
    public function create(): View
    {
        // Get campuses from Campus model
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        
        // If no campuses found, get from classes or sections
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $campusesFromSections = Section::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            
            // Convert to collection of objects with campus_name property
            $campuses = collect();
            foreach ($allCampuses as $campusName) {
                $campuses->push((object)['campus_name' => $campusName]);
            }
        }
        
        // Classes load via AJAX after campus (same as fee types) â€” avoids wrong class before campus.
        $classes = collect();
        
        // Get sections from Section model
        $sections = Section::whereNotNull('name')
            ->distinct()
            ->orderBy('name', 'asc')
            ->pluck('name')
            ->sort()
            ->values();
        
        // Fee types load in the view via AJAX after campus is chosen (accounting / super admin only).

        $months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December',
        ];

        $currentYear = (int) date('Y');
        $years = [];
        for ($y = $currentYear - 2; $y <= $currentYear + 5; $y++) {
            $years[] = $y;
        }

        return view('accounting.generate-custom-fee', compact('campuses', 'classes', 'sections', 'months', 'years', 'currentYear'));
    }

    /**
     * Store the generated custom fee.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'class' => ['required', 'string', 'max:255'],
            'section' => ['nullable', 'string', 'max:255'],
            'fee_type' => ['required', 'string', 'max:255'],
            'fee_month' => ['nullable', 'string', 'max:255'],
            'fee_year' => ['nullable', 'string', 'max:255'],
            'amount' => ['required', 'numeric', 'min:0'],
            'selected_students' => ['required', 'array', 'min:1'],
            'selected_students.*' => ['exists:students,id'],
        ]);

        $selectedStudentIds = array_values(array_unique(array_map('intval', $request->input('selected_students', []))));

        $isAccountantRoute = request()->route()->getName() === 'accountant.generate-custom-fee.store';
        $redirectRoute = $isAccountantRoute
            ? 'accountant.generate-custom-fee'
            : 'accounting.generate-custom-fee';

        $paymentTitle = $this->buildCustomFeePaymentTitle(
            $validated['fee_type'],
            $validated['fee_month'] ?? null,
            $validated['fee_year'] ?? null
        );
        $amount = round((float) $validated['amount'], 2);

        $students = Student::whereIn('id', $selectedStudentIds)->orderBy('student_code')->get();

        if ($students->isEmpty()) {
            return redirect()
                ->route($redirectRoute)
                ->with('error', 'No students found for the selected IDs.');
        }

        $studentCodes = $students->pluck('student_code')->unique()->filter()->values();
        $discountTotals = \App\Models\StudentDiscount::query()
            ->whereIn('student_code', $studentCodes)
            ->get()
            ->groupBy('student_code')
            ->map(function ($rows) {
                return $rows->sum(function ($d) {
                    return (float) ($d->discount_amount ?? 0);
                });
            });

        $dueDate = Carbon::now()->addDays(15);
        $accountantName = $this->resolveRecordingAccountantName();

        $generatedCount = 0;
        $skippedNoCode = 0;
        $skippedPaid = 0;

        foreach ($students as $student) {
            if (empty($student->student_code)) {
                $skippedNoCode++;
                continue;
            }

            $result = $this->generateCustomFeeForStudent(
                $student,
                $paymentTitle,
                $amount,
                $dueDate,
                $accountantName,
                $validated['campus'],
                (float) ($discountTotals->get($student->student_code, 0) ?: 0)
            );

            if ($result === 'generated') {
                $generatedCount++;
            } elseif ($result === 'paid') {
                $skippedPaid++;
            }
        }

        if ($generatedCount === 0) {
            $message = 'No fees were generated. ';
            if ($skippedPaid > 0) {
                $message .= "{$skippedPaid} student(s) already have this fee head fully paid (\"{$paymentTitle}\"). ";
            }
            if ($skippedNoCode > 0) {
                $message .= "{$skippedNoCode} student(s) have no student code — assign a code first.";
            }
            if ($skippedPaid === 0 && $skippedNoCode === 0) {
                $message .= 'Check that students are selected and the fee type is correct.';
            }

            return redirect()
                ->route($redirectRoute)
                ->with('error', trim($message));
        }

        $sectionValue = $this->normalizeSectionFilter($validated['section'] ?? null);

        CustomFee::updateOrCreate(
            [
                'campus' => $validated['campus'],
                'class' => $validated['class'],
                'section' => $sectionValue,
                'fee_type' => trim($validated['fee_type']),
            ],
            [
                'amount' => $amount,
            ]
        );

        // Fee Type dropdown uses base head only — not "Card Fees - June 2026" ledger titles.
        FeeType::ensureExistsForCampus(trim($validated['fee_type']), $validated['campus']);

        $message = "Custom fee generated successfully for {$generatedCount} student(s)!";
        $skippedTotal = $skippedPaid + $skippedNoCode;
        if ($skippedTotal > 0) {
            $message .= " {$skippedTotal} student(s) skipped";
            if ($skippedPaid > 0 && $skippedNoCode > 0) {
                $message .= " ({$skippedPaid} already paid, {$skippedNoCode} without student code).";
            } elseif ($skippedPaid > 0) {
                $message .= ' (fee already fully paid).';
            } else {
                $message .= ' (no student code).';
            }
        }

        if ($isAccountantRoute) {
            $this->notifyAdminsAboutAccountantCustomFee($validated, $paymentTitle, $amount, $generatedCount);
        }

        return redirect()
            ->route($redirectRoute)
            ->with('success', $message); 
    }

    private function notifyAdminsAboutAccountantCustomFee(array $validated, string $paymentTitle, float $amount, int $generatedCount): void
    {
        $accountant = auth()->guard('accountant')->user();
        if (!$accountant) {
            return;
        }

        $section = trim((string) ($validated['section'] ?? ''));
        $classSection = trim($validated['class'] . ($section !== '' ? ' - ' . $section : ''));
        $text = sprintf(
            '%s generated custom fee "%s" (%s) for %d student(s). Campus: %s, Class: %s.',
            $accountant->name ?? 'Accountant',
            $paymentTitle,
            number_format($amount, 2),
            $generatedCount,
            $validated['campus'],
            $classSection
        );

        AdminRole::query()
            ->select('id')
            ->orderBy('id')
            ->get()
            ->each(function (AdminRole $admin) use ($accountant, $text) {
                Message::create([
                    'from_type' => 'accountant_notification',
                    'from_id' => $accountant->id,
                    'to_type' => 'admin',
                    'to_id' => $admin->id,
                    'text' => $text,
                    'attachment_path' => null,
                    'attachment_type' => null,
                    'read_at' => null,
                ]);
            });
    }

    /**
     * Fee types for Generate Custom Fee dropdown — master + templates only (never ledger month rows).
     */
    public function getFeeTypesByCampus(Request $request): JsonResponse
    {
        $campus = $request->get('campus');

        $query = FeeType::query()->whereNotNull('fee_name')->where('fee_name', '!=', '');

        if ($campus) {
            $campusLower = strtolower(trim((string) $campus));
            $query->where(function ($q) use ($campusLower) {
                $q->whereRaw('LOWER(TRIM(campus)) = ?', [$campusLower])
                    ->orWhereNull('campus')
                    ->orWhereRaw('TRIM(campus) = ?', ['']);
            });
        }

        $merged = $query->orderBy('fee_name', 'asc')->pluck('fee_name');

        if ($campus && trim((string) $campus) !== '') {
            $campusLower = strtolower(trim((string) $campus));
            $fromCustomFee = CustomFee::query()
                ->whereRaw('LOWER(TRIM(campus)) = ?', [$campusLower])
                ->whereNotNull('fee_type')
                ->where('fee_type', '!=', '')
                ->distinct()
                ->orderBy('fee_type')
                ->pluck('fee_type');
            $merged = $merged->merge($fromCustomFee);
        }

        $feeTypes = $this->feeTypesForCustomFeeDropdown($merged);

        return response()->json(['fee_types' => $feeTypes]);
    }

    /**
     * Saved template amount (custom_fees) for campus + class + section + fee type â€” accounting form only.
     */
    public function getCustomFeeAmount(Request $request): JsonResponse
    {
        $campus = trim((string) $request->get('campus', ''));
        $class = trim((string) $request->get('class', ''));
        $section = trim((string) $request->get('section', ''));
        $feeType = trim((string) $request->get('fee_type', ''));

        if ($campus === '' || $class === '' || $feeType === '') {
            return response()->json(['amount' => null]);
        }

        $sectionValue = $section !== '' ? $section : null;

        $row = CustomFee::query()
            ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)])
            ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($class)])
            ->where(function ($q) use ($sectionValue) {
                if ($sectionValue === null) {
                    $q->whereNull('section')->orWhereRaw('TRIM(section) = ?', ['']);
                } else {
                    $q->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($sectionValue)]);
                }
            })
            ->whereRaw('LOWER(TRIM(fee_type)) = ?', [strtolower($feeType)])
            ->orderByDesc('id')
            ->first();

        if (! $row) {
            return response()->json(['amount' => null]);
        }

        return response()->json(['amount' => round((float) $row->amount, 2)]);
    }

    /**
     * Get sections by class name (AJAX).
     */
    public function getSectionsByClass(Request $request): JsonResponse
    {
        $className = $request->get('class');
        $campus = $request->get('campus');

        if (! $className) {
            return response()->json(['sections' => []]);
        }

        $sectionsQuery = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim((string) $className))]);
        if ($campus) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $campus))]);
        }
        $sections = $sectionsQuery
            ->orderBy('name', 'asc')
            ->pluck('name')
            ->map(fn ($name) => trim((string) $name))
            ->filter(fn ($name) => $name !== '')
            ->unique()
            ->values();

        if ($sections->isEmpty() && $campus && $className) {
            $sections = Student::query()
                ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim((string) $campus))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim((string) $className))])
                ->whereNotNull('section')
                ->whereRaw('TRIM(section) != ?', [''])
                ->distinct()
                ->orderBy('section')
                ->pluck('section')
                ->map(fn ($name) => trim((string) $name))
                ->filter(fn ($name) => $name !== '')
                ->unique()
                ->values();
        }

        return response()->json([
            'sections' => $sections->map(fn ($name) => ['id' => $name, 'name' => $name])->values(),
        ]);
    }

    /**
     * Get students by campus, class, and section (AJAX) — same filters as store + fee status per head.
     */
    public function getStudents(Request $request): JsonResponse
    {
        $campus = $request->get('campus');
        $class = $request->get('class');
        $section = $request->get('section');
        $feeType = $request->get('fee_type');
        $feeMonth = $request->get('fee_month');
        $feeYear = $request->get('fee_year');

        if (! $campus || ! $class) {
            return response()->json(['students' => []]);
        }

        $paymentTitle = $feeType
            ? $this->buildCustomFeePaymentTitle($feeType, $feeMonth, $feeYear)
            : null;

        $students = $this->studentsQueryForCustomFee($campus, $class, $section)
            ->orderBy('student_code', 'asc')
            ->get();

        $studentsList = $students->map(function (Student $student) use ($paymentTitle) {
            $status = $this->customFeeStatusForStudent($student, $paymentTitle);

            return [
                'id' => $student->id,
                'student_code' => $student->student_code ?? '',
                'student_name' => $student->student_name ?? '',
                'parent_name' => $student->father_name ?? '',
                'section' => $student->section ?? '',
                'has_fee_generated' => $status['has_generated'],
                'can_generate' => $status['can_generate'],
                'status' => $status['label'],
                'remaining_due' => $status['remaining_due'],
            ];
        });

        return response()->json(['students' => $studentsList]);
    }

    public function getClassesByCampus(Request $request): JsonResponse
    {
        $campus = $request->get('campus');

        if (! $campus || trim((string) $campus) === '') {
            return response()->json(['classes' => []]);
        }

        $campusNorm = strtolower(trim((string) $campus));

        $classes = ClassModel::whereNotNull('class_name')
            ->whereRaw('LOWER(TRIM(campus)) = ?', [$campusNorm])
            ->distinct()
            ->orderBy('class_name', 'asc')
            ->pluck('class_name')
            ->values();

        if ($classes->isEmpty()) {
            $fromStudents = Student::query()
                ->whereNotNull('class')
                ->whereRaw('LOWER(TRIM(campus)) = ?', [$campusNorm])
                ->distinct()
                ->pluck('class')
                ->sort()
                ->values();
            $classes = $fromStudents->isEmpty()
                ? collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th'])
                : $fromStudents;
        }

        $classes = $classes->map(fn ($c) => trim((string) $c))
            ->filter(fn ($c) => $c !== '')
            ->unique()
            ->sort()
            ->values();

        return response()->json(['classes' => $classes]);
    }

    private function buildCustomFeePaymentTitle(string $feeType, ?string $feeMonth, ?string $feeYear): string
    {
        $feeType = $this->baseCustomFeeTypeName($feeType);
        $month = trim((string) ($feeMonth ?? ''));
        $year = trim((string) ($feeYear ?? ''));

        if ($month !== '' && $year !== '') {
            return "{$feeType} - {$month} {$year}";
        }

        return $feeType;
    }

    /**
     * Dropdown shows "Card Fees", not "Card Fees - June 2026" (ledger period titles stay off the list).
     */
    private function baseCustomFeeTypeName(string $name): string
    {
        $name = trim($name);
        if ($name === '') {
            return '';
        }

        $months = 'January|February|March|April|May|June|July|August|September|October|November|December';
        $stripped = preg_replace('/\s+-\s+(' . $months . ')\s+\d{4}$/i', '', $name);

        return trim((string) ($stripped !== null && $stripped !== '' ? $stripped : $name));
    }

    /**
     * @param  \Illuminate\Support\Collection<int, mixed>|\Illuminate\Support\Collection<int, string>  $names
     * @return \Illuminate\Support\Collection<int, string>
     */
    private function feeTypesForCustomFeeDropdown($names): \Illuminate\Support\Collection
    {
        return collect($names)
            ->map(fn ($name) => $this->baseCustomFeeTypeName((string) $name))
            ->filter(fn ($name) => $name !== '')
            ->unique(fn ($name) => strtolower($name))
            ->sortBy(fn ($name) => strtolower($name))
            ->values();
    }

    private function normalizeSectionFilter(?string $section): ?string
    {
        $section = trim((string) ($section ?? ''));

        return $section !== '' ? $section : null;
    }

    private function studentsQueryForCustomFee(string $campus, string $class, ?string $section)
    {
        $sectionValue = $this->normalizeSectionFilter($section);

        $query = Student::query()
            ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))])
            ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);

        if ($sectionValue !== null) {
            $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($sectionValue)]);
        }

        return $query;
    }

    /**
     * @return array{has_generated: bool, can_generate: bool, label: string, remaining_due: float}
     */
    private function customFeeStatusForStudent(Student $student, ?string $paymentTitle): array
    {
        if (empty($student->student_code)) {
            return [
                'has_generated' => false,
                'can_generate' => false,
                'label' => 'No student code',
                'remaining_due' => 0.0,
            ];
        }

        if ($paymentTitle === null || trim($paymentTitle) === '') {
            return [
                'has_generated' => false,
                'can_generate' => true,
                'label' => 'Ready',
                'remaining_due' => 0.0,
            ];
        }

        $generatedRows = StudentPayment::ledgerActive()
            ->where('student_code', $student->student_code)
            ->paymentTitleKey($paymentTitle)
            ->where('method', 'Generated')
            ->exists();

        $remainingDue = StudentPayment::remainingDueForTitle($student->student_code, $paymentTitle, 0.0);

        if ($remainingDue > 0.00001) {
            return [
                'has_generated' => $generatedRows,
                'can_generate' => true,
                'label' => $generatedRows ? 'Unpaid (update amount)' : 'Ready',
                'remaining_due' => round($remainingDue, 2),
            ];
        }

        if ($generatedRows) {
            return [
                'has_generated' => true,
                'can_generate' => false,
                'label' => 'Paid',
                'remaining_due' => 0.0,
            ];
        }

        $anyRow = StudentPayment::ledgerActive()
            ->where('student_code', $student->student_code)
            ->paymentTitleKey($paymentTitle)
            ->exists();

        return [
            'has_generated' => false,
            'can_generate' => ! $anyRow,
            'label' => $anyRow ? 'Paid' : 'Ready',
            'remaining_due' => 0.0,
        ];
    }

    /**
     * @return 'generated'|'paid'
     */
    private function generateCustomFeeForStudent(
        Student $student,
        string $paymentTitle,
        float $amount,
        Carbon $dueDate,
        string $accountantName,
        string $campus,
        float $totalStudentDiscount
    ): string {
        $code = (string) $student->student_code;
        $remainingDue = StudentPayment::remainingDueForTitle($code, $paymentTitle, $totalStudentDiscount);

        $generatedRows = StudentPayment::ledgerActive()
            ->where('student_code', $code)
            ->paymentTitleKey($paymentTitle)
            ->where('method', 'Generated')
            ->orderByDesc('id')
            ->get();

        $canonicalTitle = $generatedRows->isNotEmpty()
            ? (string) $generatedRows->first()->payment_title
            : $paymentTitle;

        if ($generatedRows->isNotEmpty()) {
            if ($remainingDue <= 0.00001) {
                return 'paid';
            }

            $latest = $generatedRows->first();
            $latest->update([
                'campus' => $student->campus ?? $campus,
                'payment_title' => $canonicalTitle,
                'payment_amount' => $amount,
                'discount' => 0,
                'late_fee' => 0,
                'payment_date' => $dueDate->format('Y-m-d'),
                'sms_notification' => 'Yes',
                'accountant' => $accountantName,
            ]);

            $duplicateIds = $generatedRows->pluck('id')->filter(fn ($id) => (int) $id !== (int) $latest->id)->all();
            if ($duplicateIds !== []) {
                StudentPayment::whereIn('id', $duplicateIds)->delete();
            }

            return 'generated';
        }

        if ($remainingDue <= 0.00001) {
            $alreadyPaid = StudentPayment::ledgerActive()
                ->where('student_code', $code)
                ->paymentTitleKey($paymentTitle)
                ->whereNotIn('method', ['Generated', 'Installment'])
                ->exists();

            if ($alreadyPaid) {
                return 'paid';
            }
        }

        StudentPayment::create([
            'campus' => $student->campus ?? $campus,
            'student_code' => $code,
            'payment_title' => $canonicalTitle,
            'payment_amount' => $amount,
            'discount' => 0,
            'method' => 'Generated',
            'payment_date' => $dueDate->format('Y-m-d'),
            'sms_notification' => 'Yes',
            'late_fee' => 0,
            'accountant' => $accountantName,
        ]);

        return 'generated';
    }

    private function resolveRecordingAccountantName(): string
    {
        if (auth()->guard('accountant')->check()) {
            return auth()->guard('accountant')->user()->name ?? 'System';
        }
        if (auth()->guard('admin')->check()) {
            return auth()->guard('admin')->user()->name ?? 'System';
        }

        return auth()->user()->name ?? 'System';
    }
}

