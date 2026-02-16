<?php

namespace App\Http\Controllers;

use App\Models\ParentAccount;
use App\Models\ParentAccountRequest;
use App\Models\Student;
use App\Models\StudentPayment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ParentInfoRequestController extends Controller
{
    /**
     * Display the parent info request page.
     */
    public function index(): View
    {
        // Calculate statistics for summary cards
        $totalParents = ParentAccount::count();
        
        // Parents with credit (paid amounts > 0)
        $parentsWithCredit = ParentAccount::with('students')->get()->filter(function ($parent) {
            $studentCodes = $parent->students->pluck('student_code')->filter()->values();
            if ($studentCodes->isEmpty()) {
                return false;
            }
            $paidTotal = StudentPayment::whereIn('student_code', $studentCodes)
                ->where('method', '!=', 'Generated')
                ->sum('payment_amount');
            return $paidTotal > 0;
        })->count();
        
        // Defaulter parents (with outstanding dues)
        $defaulterParents = ParentAccount::with('students')->get()->filter(function ($parent) {
            $studentCodes = $parent->students->pluck('student_code')->filter()->values();
            if ($studentCodes->isEmpty()) {
                return false;
            }
            $dueTotal = StudentPayment::whereIn('student_code', $studentCodes)
                ->where('method', 'Generated')
                ->get()
                ->sum(function ($payment) {
                    $amount = (float) ($payment->payment_amount ?? 0);
                    $discount = (float) ($payment->discount ?? 0);
                    $lateFee = (float) ($payment->late_fee ?? 0);
                    return $amount - $discount + $lateFee;
                });
            return $dueTotal > 0;
        })->count();
        
        // Total linked students
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
        // This will filter data based on the form inputs
        // For now, just redirect back with filters applied
        return redirect()
            ->route('parent.info-request')
            ->with('success', 'Filter applied successfully!');
    }

    /**
     * Print all parents report.
     */
    public function allParentsReport(): View
    {
        $parents = ParentAccount::with('students')->orderBy('name')->get();

        return view('parent.info-reports.all-parents-print', [
            'parents' => $parents,
            'printedAt' => now()->format('d-m-Y H:i'),
            'autoPrint' => request()->get('auto_print'),
        ]);
    }

    /**
     * Print parent credit report (paid totals).
     */
    public function parentCreditReport(): View
    {
        $parents = ParentAccount::with('students')->orderBy('name')->get();
        $rows = $parents->map(function ($parent) {
            $studentCodes = $parent->students->pluck('student_code')->filter()->values();
            $paidTotal = 0;
            if ($studentCodes->isNotEmpty()) {
                $paidTotal = StudentPayment::whereIn('student_code', $studentCodes)
                    ->where('method', '!=', 'Generated')
                    ->sum('payment_amount');
            }
            return [
                'parent' => $parent,
                'student_count' => $parent->students->count(),
                'paid_total' => (float) $paidTotal,
            ];
        });

        return view('parent.info-reports.parent-credit-print', [
            'rows' => $rows,
            'printedAt' => now()->format('d-m-Y H:i'),
            'autoPrint' => request()->get('auto_print'),
        ]);
    }

    /**
     * Print family tree report (parents with students).
     */
    public function familyTreeReport(): View
    {
        $parents = ParentAccount::with('students')->orderBy('name')->get();

        return view('parent.info-reports.family-tree-print', [
            'parents' => $parents,
            'printedAt' => now()->format('d-m-Y H:i'),
            'autoPrint' => request()->get('auto_print'),
        ]);
    }

    /**
     * Print defaulter parents report (dues).
     */
    public function defaulterParentsReport(): View
    {
        $parents = ParentAccount::with('students')->orderBy('name')->get();
        $rows = $parents->map(function ($parent) {
            $studentCodes = $parent->students->pluck('student_code')->filter()->values();
            $dueTotal = 0;
            if ($studentCodes->isNotEmpty()) {
                $dueTotal = StudentPayment::whereIn('student_code', $studentCodes)
                    ->where('method', 'Generated')
                    ->get()
                    ->sum(function ($payment) {
                        $amount = (float) ($payment->payment_amount ?? 0);
                        $discount = (float) ($payment->discount ?? 0);
                        $lateFee = (float) ($payment->late_fee ?? 0);
                        return $amount - $discount + $lateFee;
                    });
            }
            return [
                'parent' => $parent,
                'student_count' => $parent->students->count(),
                'due_total' => (float) $dueTotal,
            ];
        })->filter(function ($row) {
            return $row['due_total'] > 0;
        })->values();

        return view('parent.info-reports.defaulter-parents-print', [
            'rows' => $rows,
            'printedAt' => now()->format('d-m-Y H:i'),
            'autoPrint' => request()->get('auto_print'),
        ]);
    }
}

