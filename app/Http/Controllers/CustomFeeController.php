<?php

namespace App\Http\Controllers;

use App\Models\CustomFee;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\FeeType;
use App\Models\Student;
use App\Models\StudentPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
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
        
        // Get classes from ClassModel
        $classes = ClassModel::orderBy('class_name', 'asc')->get();
        
        // If no classes found, provide empty collection
        if ($classes->isEmpty()) {
            $classes = collect();
        }
        
        // Get sections from Section model
        $sections = Section::whereNotNull('name')
            ->distinct()
            ->orderBy('name', 'asc')
            ->pluck('name')
            ->sort()
            ->values();
        
        // Get fee types from FeeType model
        $feeTypes = FeeType::whereNotNull('fee_name')
            ->distinct()
            ->orderBy('fee_name', 'asc')
            ->pluck('fee_name')
            ->sort()
            ->values();
        
        return view('accounting.generate-custom-fee', compact('campuses', 'classes', 'sections', 'feeTypes'));
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
            'amount' => ['required', 'numeric', 'min:0'],
            'selected_students' => ['nullable', 'array'],
            'selected_students.*' => ['exists:students,id'],
        ]);

        // Check if students are selected
        $selectedStudentIds = $request->input('selected_students', []);

        // Determine redirect route based on which route was used (accountant or accounting)
        $isAccountantRoute = request()->route()->getName() === 'accountant.generate-custom-fee.store';
        $redirectRoute = $isAccountantRoute 
            ? 'accountant.generate-custom-fee' 
            : 'accounting.generate-custom-fee';
        
        $sectionValue = trim((string) ($validated['section'] ?? ''));
        $sectionValue = $sectionValue !== '' ? $sectionValue : null;

        $studentsQuery = Student::query()
            ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($validated['campus']))])
            ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($validated['class']))]);

        if ($sectionValue !== null) {
            $studentsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($sectionValue)]);
        }

        if (!empty($selectedStudentIds)) {
            $studentsQuery->whereIn('id', $selectedStudentIds);
        }

        // Create the custom fee configuration (if it doesn't exist)
        $customFee = CustomFee::firstOrCreate([
            'campus' => $validated['campus'],
            'class' => $validated['class'],
            'section' => $sectionValue,
            'fee_type' => $validated['fee_type'],
        ], [
            'amount' => $validated['amount'],
        ]);

        // Get selected students or whole class/section if none selected
        $students = $studentsQuery->get();

        if ($students->isEmpty()) {
            return redirect()
                ->route($redirectRoute)
                ->with('error', 'No students found for the selected campus, class, and section.');
        }
        
        // Generate fee for each selected student
        $paymentTitle = $validated['fee_type'];
        $amount = (float) $validated['amount'];
        $dueDate = Carbon::now()->addDays((int) 15); // Default due date
        
        $generatedCount = 0;
        $skippedCount = 0;
        $generatedStudentCodes = [];
        
        foreach ($students as $student) {
            // Skip if student doesn't have student_code
            if (empty($student->student_code)) {
                $skippedCount++;
                continue;
            }
            
            // Check if unpaid fee already exists for this student and fee type
            // Only skip if there's an unpaid fee (method = 'Generated' means unpaid)
            $existingGeneratedFee = StudentPayment::where('student_code', $student->student_code)
                ->where('payment_title', $paymentTitle)
                ->where('method', 'Generated')
                ->first();
            
            if ($existingGeneratedFee) {
                // Check if this fee has been paid
                $totalGenerated = (float) ($existingGeneratedFee->payment_amount ?? 0) - (float) ($existingGeneratedFee->discount ?? 0);
                $totalPaid = StudentPayment::where('student_code', $student->student_code)
                    ->where('payment_title', $paymentTitle)
                    ->where('method', '!=', 'Generated')
                    ->sum(DB::raw('COALESCE(payment_amount, 0)'));
                
                // Only skip if there's an unpaid balance
                if ($totalGenerated > $totalPaid) {
                    $skippedCount++;
                    continue;
                }
                // If fee is fully paid, allow generating a new one
            }
            
            // Get accountant name based on guard
            $accountantName = 'System';
            if (auth()->guard('accountant')->check()) {
                $accountantName = auth()->guard('accountant')->user()->name ?? 'System';
            } elseif (auth()->guard('admin')->check()) {
                $accountantName = auth()->guard('admin')->user()->name ?? 'System';
            }
            
            // Create fee record for this student
            StudentPayment::create([
                'campus' => $student->campus ?? $validated['campus'],
                'student_code' => $student->student_code,
                'payment_title' => $paymentTitle,
                'payment_amount' => $amount,
                'discount' => 0,
                'method' => 'Generated', // Indicates this is a generated fee, not actual payment
                'payment_date' => $dueDate->format('Y-m-d'), // Due date (will be updated when payment is made)
                'sms_notification' => 'Yes',
                'late_fee' => 0,
                'accountant' => $accountantName,
            ]);
            
            $generatedStudentCodes[] = $student->student_code;
            $generatedCount++;
        }

        // If no fees were generated, show warning/error message
        if ($generatedCount == 0) {
            $message = "No fees were generated. ";
            if ($skippedCount > 0) {
                $message .= "All selected students were skipped (fees already exist for this fee type).";
            } else {
                $message .= "Selected students don't have student codes.";
            }
            
            return redirect()
                ->route($redirectRoute)
                ->with('error', $message);
        }

        // Success message only if fees were generated
        $message = "Custom fee generated successfully for {$generatedCount} student(s)!";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} student(s) skipped (fees already exist).";
        }

        return redirect()
            ->route($redirectRoute)
            ->with('success', $message);
    }

    /**
     * Get fee types by campus (AJAX)
     */
    public function getFeeTypesByCampus(Request $request): JsonResponse
    {
        $campus = $request->get('campus');

        $query = FeeType::query();
        if ($campus) {
            $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }

        $feeTypes = $query->orderBy('fee_name', 'asc')
            ->pluck('fee_name')
            ->unique()
            ->values();

        return response()->json(['fee_types' => $feeTypes]);
    }

    /**
     * Get sections by class name (AJAX).
     */
    public function getSectionsByClass(Request $request)
    {
        $className = $request->get('class');
        $campus = $request->get('campus');
        
        if (!$className) {
            return response()->json(['sections' => []]);
        }

        // Get sections for the selected class
        $sectionsQuery = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($className))]);
        if ($campus) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        $sections = $sectionsQuery
            ->orderBy('name', 'asc')
            ->get(['id', 'name'])
            ->map(function($section) {
                return [
                    'id' => $section->id,
                    'name' => $section->name
                ];
            });

        return response()->json(['sections' => $sections]);
    }

    /**
     * Get students by campus, class, and section (AJAX).
     */
    public function getStudents(Request $request)
    {
        $campus = $request->get('campus');
        $class = $request->get('class');
        $section = $request->get('section');

        if (!$campus || !$class) {
            return response()->json(['students' => []]);
        }

        // Get students matching the criteria
        $studentsQuery = Student::query();
        
        if ($campus) {
            $studentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        
        if ($class) {
            $studentsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
        }
        
        if ($section) {
            // Include students with matching section OR null section (for transferred students)
            $studentsQuery->where(function($query) use ($section) {
                $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))])
                      ->orWhereNull('section')
                      ->orWhere('section', '');
            });
        }

        $students = $studentsQuery->orderBy('student_code', 'asc')->get();

        // Map students
        $studentsList = $students->map(function($student) {
            // Get parent name
            $parentName = $student->father_name ?? '';

            return [
                'id' => $student->id,
                'student_code' => $student->student_code ?? '',
                'student_name' => $student->student_name ?? '',
                'parent_name' => $parentName,
            ];
        });

        return response()->json(['students' => $studentsList]);
    }
}

