<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\FeeType;
use App\Models\StudentPayment;
use App\Models\Campus;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;

class FeeDefaultReportController extends Controller
{
    /**
     * Display the fee default reports with filters.
     */
    public function index(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterType = $request->get('filter_type');
        $filterStatus = $request->get('filter_status');

        // Get campuses from Campus model
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            // Fallback: get from other sources
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $allCampuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            
            $campuses = $allCampuses->map(function($campusName) {
                return (object)['campus_name' => $campusName, 'id' => null];
            });
        }

        // Get classes
        $classesQuery = ClassModel::whereNotNull('class_name');
        if ($filterCampus) {
            $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        $classes = $classesQuery->distinct()->pluck('class_name')->sort()->values();
        if ($classes->isEmpty()) {
            $classesFromSubjects = \App\Models\Subject::whereNotNull('class');
            if ($filterCampus) {
                $classesFromSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
            }
            $classesFromSubjects = $classesFromSubjects->distinct()->pluck('class')->sort()->values();
            $classes = $classesFromSubjects->isEmpty()
                ? collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th'])
                : $classesFromSubjects;
        }

        // Get sections
        $sectionsQuery = Section::whereNotNull('name');
        if ($filterCampus) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        if ($filterClass) {
            $sectionsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
        }
        $sections = $sectionsQuery->distinct()->pluck('name')->sort()->values();
        if ($sections->isEmpty()) {
            $sectionsFromSubjects = \App\Models\Subject::whereNotNull('section');
            if ($filterCampus) {
                $sectionsFromSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
            }
            if ($filterClass) {
                $sectionsFromSubjects->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
            }
            $sectionsFromSubjects = $sectionsFromSubjects->distinct()->pluck('section')->sort()->values();
            $sections = $sectionsFromSubjects->isEmpty()
                ? collect(['A', 'B', 'C', 'D', 'E'])
                : $sectionsFromSubjects;
        }

        // Type options for fee default reports
        $typeOptions = collect([
            'all_detailed' => 'All Detailed',
            'admission_fee_only' => 'Admission Fee Only',
            'transport_fee_only' => 'Transport Fee Only',
            'card_fee_only' => 'Card Fee Only'
        ]);

        // Get fee types (for reference, but we'll use typeOptions for the Type filter)
        $feeTypes = FeeType::whereNotNull('fee_name')->distinct()->pluck('fee_name')->sort()->values();
        if ($feeTypes->isEmpty()) {
            $feeTypes = collect(['Monthly Fee', 'Transport Fee', 'Custom Fee', 'Advance Fee']);
        }

        // Status options (active/deactive students only)
        $statusOptions = collect([
            'active' => 'Active Student',
            'deactive' => 'Deactive Student',
        ]);

        // Query students with fee default information
        $query = Student::query();

        // Apply filters
        if ($filterCampus) {
            $query->where('campus', $filterCampus);
        }
        if ($filterClass) {
            $query->where('class', $filterClass);
        }
        if ($filterSection) {
            $query->where('section', $filterSection);
        }
        if ($filterStatus === 'active') {
            $query->whereNotNull('admission_date');
        } elseif ($filterStatus === 'deactive') {
            $query->whereNull('admission_date');
        }

        // Get students
        $students = $query->orderBy('student_name')->get();

        $studentCodes = $students->pluck('student_code')
            ->filter()
            ->unique()
            ->values();

        $generatedPayments = StudentPayment::whereIn('student_code', $studentCodes)
            ->where('method', 'Generated')
            ->get()
            ->groupBy('student_code');
        $paidPayments = StudentPayment::whereIn('student_code', $studentCodes)
            ->where('method', '!=', 'Generated')
            ->get()
            ->groupBy('student_code');

        $reportRows = $students->map(function ($student) use ($generatedPayments, $paidPayments) {
            $code = $student->student_code;
            $generated = $generatedPayments->get($code, collect());
            $paid = $paidPayments->get($code, collect());

            $generatedByTitle = $generated->groupBy('payment_title');
            $paidByTitle = $paid->groupBy('payment_title');

            $dueInvoices = 0;
            $totalGenerated = 0;
            $totalPaid = 0;
            $totalLate = 0;

            foreach ($generatedByTitle as $title => $items) {
                $generatedAmount = $items->sum(function ($item) {
                    return (float) ($item->payment_amount ?? 0) - (float) ($item->discount ?? 0);
                });
                $generatedLate = $items->sum(function ($item) {
                    return (float) ($item->late_fee ?? 0);
                });
                $paidAmount = $paidByTitle->get($title, collect())->sum(function ($item) {
                    return (float) ($item->payment_amount ?? 0) - (float) ($item->discount ?? 0);
                });
                $paidLate = $paidByTitle->get($title, collect())->sum(function ($item) {
                    return (float) ($item->late_fee ?? 0);
                });

                $totalGenerated += $generatedAmount + $generatedLate;
                $totalLate += $generatedLate;
                $totalPaid += $paidAmount + $paidLate;

                $remainingAmount = max(0, $generatedAmount - $paidAmount);
                $remainingLate = max(0, $generatedLate - $paidLate);
                if (($remainingAmount + $remainingLate) > 0) {
                    $dueInvoices++;
                }
            }

            $lastPayment = $paid->sortByDesc('payment_date')->first();
            $remaining = max(0, $totalGenerated - $totalPaid);

            return [
                'student_code' => $student->student_code,
                'student_name' => $student->student_name,
                'parent_name' => $student->father_name,
                'class' => $student->class,
                'last_payment' => $lastPayment ? $lastPayment->payment_date : null,
                'due_invoices' => $dueInvoices,
                'total' => $totalGenerated,
                'paid' => $totalPaid,
                'late' => $totalLate,
                'remaining' => $remaining,
                'phone' => $student->father_phone,
                'whatsapp' => $student->whatsapp_number,
                'student_id' => $student->id,
            ];
        });

        // Filter by type and status if needed (this would require additional logic based on payment records)
        // For now, we'll return all students and let the view handle display

        return view('reports.fee-default', compact(
            'campuses',
            'classes',
            'sections',
            'feeTypes',
            'typeOptions',
            'statusOptions',
            'students',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterType',
            'filterStatus',
            'reportRows'
        ));
    }

    /**
     * Store a newly created campus (AJAX).
     */
    public function storeCampus(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'campus_name' => ['required', 'string', 'max:255', 'unique:campuses,campus_name'],
        ]);

        $campus = Campus::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Campus added successfully!',
            'campus' => $campus
        ]);
    }

    /**
     * Remove the specified campus (AJAX).
     */
    public function destroyCampus(Campus $campus): JsonResponse
    {
        $campus->delete();

        return response()->json([
            'success' => true,
            'message' => 'Campus deleted successfully!'
        ]);
    }

    /**
     * Get classes by campus (AJAX endpoint)
     */
    public function getClassesByCampus(Request $request): JsonResponse
    {
        $campus = $request->get('campus');

        $classesQuery = ClassModel::whereNotNull('class_name');
        if ($campus) {
            $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        $classes = $classesQuery->distinct()->pluck('class_name')->sort()->values();

        if ($classes->isEmpty()) {
            $classesFromSubjects = \App\Models\Subject::whereNotNull('class');
            if ($campus) {
                $classesFromSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $classesFromSubjects = $classesFromSubjects->distinct()->pluck('class')->sort()->values();
            $classes = $classesFromSubjects->isEmpty()
                ? collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th'])
                : $classesFromSubjects;
        }

        $classes = $classes->map(function($class) {
            return trim((string) $class);
        })->filter(function($class) {
            return $class !== '';
        })->unique()->sort()->values();

        return response()->json(['classes' => $classes]);
    }

    /**
     * Get sections by class (AJAX endpoint)
     */
    public function getSectionsByClass(Request $request): JsonResponse
    {
        $class = $request->get('class');
        $campus = $request->get('campus');

        if (!$class) {
            return response()->json(['sections' => []]);
        }

        $sectionsQuery = Section::whereNotNull('name')
            ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
        if ($campus) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        $sections = $sectionsQuery->distinct()->pluck('name')->sort()->values();

        if ($sections->isEmpty()) {
            $sectionsFromSubjects = \App\Models\Subject::whereNotNull('section')
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
            if ($campus) {
                $sectionsFromSubjects->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $sections = $sectionsFromSubjects->distinct()->pluck('section')->sort()->values();
        }

        return response()->json(['sections' => $sections]);
    }
}

