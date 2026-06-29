<?php

namespace App\Http\Controllers;

use App\Models\ParentAccount;
use App\Models\GeneralSetting;
use App\Models\Student;
use App\Models\StudentPayment;
use App\Services\FeePaymentWebTables;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParentInfoRequestController extends Controller
{
    /**
     * Display the parent info request page.
     */
    public function index(): View
    {
        $totalParents = ParentAccount::count();

        $parentsWithCredit = ParentAccount::query()->get()->filter(function ($parent) {
            $studentCodes = FeePaymentWebTables::studentsForParentAccount($parent)
                ->pluck('student_code')
                ->filter()
                ->values();

            if ($studentCodes->isEmpty()) {
                return false;
            }

            $paidTotal = StudentPayment::query()
                ->whereIn('student_code', $studentCodes)
                ->whereNotIn('method', ['Generated', 'Installment'])
                ->sum('payment_amount');

            return $paidTotal > 0;
        })->count();

        $defaulterParents = ParentAccount::query()->get()->filter(function ($parent) {
            return FeePaymentWebTables::parentOutstandingDueTotal($parent) > 0.00001;
        })->count();

        $totalLinkedStudents = Student::whereNotNull('parent_account_id')->count();

        return view('parent.info-request', compact(
            'totalParents',
            'parentsWithCredit',
            'defaulterParents',
            'totalLinkedStudents'
        ));
    }

    /**
     * Filter data based on criteria.
     */
    public function filter(Request $request)
    {
        return redirect()
            ->route('parent.info-request')
            ->with('success', 'Filter applied successfully!');
    }

    /**
     * Standalone print layout for all parents (no sidebar).
     */
    public function allParentsReport(): View
    {
        $parents = ParentAccount::with('students')->orderBy('name')->get();
        $rows = $parents->map(function ($parent) {
            $students = FeePaymentWebTables::studentsForParentAccount($parent);

            return [
                'parent' => $parent,
                'student_count' => $students->count(),
                'students' => $students,
                'due_total' => FeePaymentWebTables::parentOutstandingDueTotal($parent),
            ];
        });

        return view('parent.info-reports.all-parents-print', [
            'rows' => $rows,
            'settings' => GeneralSetting::getSettings(),
            'printedAt' => now()->format('d M Y, h:i A'),
            'grandTotalDue' => round((float) $rows->sum('due_total'), 2),
        ]);
    }

    /**
     * Print parent credit report (paid totals).
     */
    public function parentCreditReport(): View
    {
        $parents = ParentAccount::orderBy('name')->get();
        $rows = $parents->map(function ($parent) {
            $students = FeePaymentWebTables::studentsForParentAccount($parent);
            $studentCodes = $students->pluck('student_code')->filter()->values();
            $paidTotal = 0.0;

            if ($studentCodes->isNotEmpty()) {
                $paidTotal = (float) StudentPayment::query()
                    ->whereIn('student_code', $studentCodes)
                    ->whereNotIn('method', ['Generated', 'Installment'])
                    ->sum('payment_amount');
            }

            return [
                'parent' => $parent,
                'student_count' => $students->count(),
                'paid_total' => $paidTotal,
            ];
        });

        return view('parent.info-reports.parent-credit-print', [
            'rows' => $rows,
            'settings' => GeneralSetting::getSettings(),
            'printedAt' => now()->format('d M Y, h:i A'),
            'grandTotal' => (float) $rows->sum('paid_total'),
        ]);
    }

    /**
     * Print family tree report (parents with students).
     */
    public function familyTreeReport(): View
    {
        $parents = ParentAccount::orderBy('name')->get();
        $rows = $parents->map(function ($parent) {
            $students = FeePaymentWebTables::studentsForParentAccount($parent);

            return [
                'parent' => $parent,
                'students' => $students,
                'due_total' => FeePaymentWebTables::parentOutstandingDueTotal($parent),
            ];
        });

        $totalLinkedStudents = $rows->sum(fn ($row) => $row['students']->count());

        return view('parent.info-reports.family-tree-print', [
            'rows' => $rows,
            'settings' => GeneralSetting::getSettings(),
            'printedAt' => now()->format('d M Y, h:i A'),
            'totalLinkedStudents' => $totalLinkedStudents,
            'grandTotalDue' => round((float) $rows->sum('due_total'), 2),
        ]);
    }

    /**
     * Print defaulter parents report (dues).
     */
    public function defaulterParentsReport(): View
    {
        $parents = ParentAccount::orderBy('name')->get();
        $rows = $parents->map(function ($parent) {
            $students = FeePaymentWebTables::studentsForParentAccount($parent);
            $dueTotal = FeePaymentWebTables::parentOutstandingDueTotal($parent);

            return [
                'parent' => $parent,
                'student_count' => $students->count(),
                'due_total' => $dueTotal,
                'email' => $this->resolveParentEmail($parent, $students),
            ];
        })->filter(function ($row) {
            return ($row['due_total'] ?? 0) > 0.00001;
        })->values();

        return view('parent.info-reports.defaulter-parents-print', [
            'rows' => $rows,
            'settings' => GeneralSetting::getSettings(),
            'printedAt' => now()->format('d M Y, h:i A'),
            'grandTotal' => round((float) $rows->sum('due_total'), 2),
        ]);
    }

    /**
     * @param \Illuminate\Support\Collection<int, Student>|\Illuminate\Database\Eloquent\Collection<int, Student> $students
     */
    private function resolveParentEmail(ParentAccount $parent, $students): string
    {
        foreach ($students as $student) {
            $fatherEmail = trim((string) ($student->father_email ?? ''));
            if ($fatherEmail !== '') {
                return $fatherEmail;
            }
        }

        if (! $parent->hasPlaceholderEmail()) {
            $parentEmail = trim((string) ($parent->email ?? ''));
            if ($parentEmail !== '') {
                return $parentEmail;
            }
        }

        return 'N/A';
    }
}
