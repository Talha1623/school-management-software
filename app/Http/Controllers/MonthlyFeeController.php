<?php

namespace App\Http\Controllers;

use App\Models\MonthlyFee;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentPayment;
use App\Models\AdvanceFee;
use App\Models\StudentDiscount;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Carbon\Carbon;

class MonthlyFeeController extends Controller
{
    /**
     * Display the generate monthly fee form.
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
        
        // Months of the year
        $months = [
            'January', 'February', 'March', 'April', 'May', 'June',
            'July', 'August', 'September', 'October', 'November', 'December'
        ];
        
        // Generate years (current year - 2 to current year + 5)
        $currentYear = date('Y');
        $years = [];
        for ($y = $currentYear - 2; $y <= $currentYear + 5; $y++) {
            $years[] = $y;
        }
        
        return view('accounting.generate-monthly-fee', compact('campuses', 'classes', 'sections', 'months', 'years', 'currentYear'));
    }

    public function getClassesByCampus(Request $request)
    {
        $campus = $request->get('campus');

        if (!$campus) {
            return response()->json(['classes' => []]);
        }

        $classes = ClassModel::whereNotNull('class_name')
            ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))])
            ->distinct()
            ->orderBy('class_name', 'asc')
            ->pluck('class_name')
            ->values();

        return response()->json(['classes' => $classes]);
    }

    /**
     * Store the generated monthly fee.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'class' => ['required', 'string', 'max:255'],
            'section' => ['required', 'string', 'max:255'],
            'fee_month' => ['required', 'string', 'max:255'],
            'fee_year' => ['required', 'string', 'max:255'],
            'due_date' => ['required', 'date'],
            'late_fee' => ['nullable', 'numeric', 'min:0'],
            'selected_students' => ['nullable', 'array'],
            'selected_students.*' => ['exists:students,id'],
        ]);

        // Set default value for late_fee if it's null or empty
        if (empty($validated['late_fee']) || $validated['late_fee'] === null) {
            $validated['late_fee'] = 0;
        }

        // Check if students are selected
        $selectedStudentIds = $request->input('selected_students', []);
        
        // Determine redirect route based on which route was used (accountant or accounting)
        $redirectRoute = request()->route()->getName() === 'accountant.generate-monthly-fee.store' 
            ? 'accountant.generate-monthly-fee' 
            : 'accounting.generate-monthly-fee';
        
        if (empty($selectedStudentIds)) {
            return redirect()
                ->route($redirectRoute)
                ->with('error', 'Please select at least one student to generate fees.');
        }

        // Create or update the monthly fee configuration
        $monthlyFee = MonthlyFee::firstOrCreate([
            'campus' => $validated['campus'],
            'class' => $validated['class'],
            'section' => $validated['section'],
            'fee_month' => $validated['fee_month'],
            'fee_year' => $validated['fee_year'],
        ], [
            'due_date' => $validated['due_date'],
            'late_fee' => $validated['late_fee'],
        ]);
        if ($monthlyFee->exists) {
            $monthlyFee->update([
                'due_date' => $validated['due_date'],
                'late_fee' => $validated['late_fee'],
            ]);
        }

        // Get selected students
        $students = Student::whereIn('id', $selectedStudentIds)->get();
        
        // Generate fee for each selected student
        $dueDate = Carbon::parse($validated['due_date']);
        $paymentTitle = "Monthly Fee - {$validated['fee_month']} {$validated['fee_year']}";
        $lateFeeAmount = (float) $validated['late_fee'];
        $isLateFeeApplicable = $lateFeeAmount > 0 && Carbon::today()->greaterThan($dueDate);
        
        $generatedCount = 0;
        $skippedCount = 0;
        
        foreach ($students as $student) {
            // Skip if student doesn't have monthly_fee or student_code
            if (empty($student->monthly_fee) || $student->monthly_fee <= 0 || empty($student->student_code)) {
                $skippedCount++;
                continue;
            }
            
            // Check if fee already exists for this student, month, and year
            $existingFee = StudentPayment::where('student_code', $student->student_code)
                ->where('payment_title', $paymentTitle)
                ->first();
            
            if ($existingFee) {
                $skippedCount++;
                continue;
            }
            
            // Get accountant name based on guard
            $accountantName = 'System';
            if (auth()->guard('accountant')->check()) {
                $accountantName = auth()->guard('accountant')->user()->name ?? 'System';
            } elseif (auth()->guard('admin')->check()) {
                $accountantName = auth()->guard('admin')->user()->name ?? 'System';
            }
            
            // Get student discount
            $studentDiscounts = StudentDiscount::where('student_code', $student->student_code)->get();
            $totalStudentDiscount = $studentDiscounts->sum(function($discount) {
                return (float) ($discount->discount_amount ?? 0);
            });
            
            // Calculate late fee (check if due date has passed)
            $calculatedLateFee = $isLateFeeApplicable ? $lateFeeAmount : 0;
            
            // Calculate actual amount to pay: monthly_fee - discount + late_fee
            $monthlyFeeAmount = (float) $student->monthly_fee;
            
            // Create fee record for this student
            $generatedFee = StudentPayment::create([
                'campus' => $student->campus ?? $validated['campus'],
                'student_code' => $student->student_code,
                'payment_title' => $paymentTitle,
                'payment_amount' => $monthlyFeeAmount,
                'discount' => round($totalStudentDiscount, 2),
                'method' => 'Generated', // Indicates this is a generated fee, not actual payment
                'payment_date' => $dueDate->format('Y-m-d'), // Due date (will be updated when payment is made)
                'sms_notification' => 'Yes',
                'late_fee' => $calculatedLateFee,
                'accountant' => $accountantName,
            ]);

            // Apply advance fee if available
            // Recalculate late fee in case due date has passed since generation
            // Use the actual late_fee from the generated fee record, or recalculate if due date passed
            $actualLateFee = (float) ($generatedFee->late_fee ?? $calculatedLateFee);
            
            // If due date has passed and late fee should apply, ensure it's included
            if ($lateFeeAmount > 0 && Carbon::today()->greaterThan($dueDate)) {
                $actualLateFee = max($actualLateFee, $lateFeeAmount);
            }
            
            // Calculate actual amount to pay: monthly_fee - discount + late_fee
            $actualAmountToPay = max(0, $monthlyFeeAmount - $totalStudentDiscount) + $actualLateFee;
            
            $advanceFee = null;
            if (!empty($student->parent_account_id)) {
                $advanceFee = AdvanceFee::where('parent_id', (string) $student->parent_account_id)->first();
            }
            if (!$advanceFee && !empty($student->father_id_card)) {
                $advanceFee = AdvanceFee::where('id_card_number', $student->father_id_card)->first();
            }
            if ($advanceFee && (float) $advanceFee->available_credit > 0) {
                // Apply advance fee to the actual amount to pay (after discount, including late fee)
                $applyAmount = min((float) $advanceFee->available_credit, $actualAmountToPay);
                if ($applyAmount > 0) {
                    StudentPayment::create([
                        'campus' => $student->campus ?? $validated['campus'],
                        'student_code' => $student->student_code,
                        'payment_title' => $paymentTitle,
                        'payment_amount' => $applyAmount, // Amount paid from advance fee (after discount, including late fee)
                        'discount' => 0, // Discount is already in generated fee record
                        'method' => 'Advance Fee',
                        'payment_date' => Carbon::now()->format('Y-m-d'),
                        'sms_notification' => 'Yes',
                        'late_fee' => $actualLateFee, // Use actual late fee from generated fee record
                        'accountant' => $accountantName,
                    ]);

                    $advanceFee->available_credit = max(0, (float) $advanceFee->available_credit - $applyAmount);
                    $advanceFee->decrease = (float) ($advanceFee->decrease ?? 0) + $applyAmount;
                    $advanceFee->save();
                }
            }
            
            $generatedCount++;
        }

        // If no fees were generated, show warning/error message
        if ($generatedCount == 0) {
            $message = "No fees were generated. ";
            if ($skippedCount > 0) {
                $message .= "All selected students were skipped (no monthly fee set or fees already exist for this month/year).";
            } else {
                $message .= "Selected students don't have monthly fee configured.";
            }
            
            return redirect()
                ->route($redirectRoute)
                ->with('error', $message);
        }

        // Success message only if fees were generated
        $message = "Monthly fee generated successfully for {$generatedCount} student(s)!";
        if ($skippedCount > 0) {
            $message .= " {$skippedCount} student(s) skipped (no monthly fee set or already exists).";
        }

        return redirect()
            ->route($redirectRoute)
            ->with('success', $message);
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

        // Get sections for the selected class (optionally filter by campus)
        $sectionsQuery = Section::where('class', $className);
        if ($campus) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }

        $sections = $sectionsQuery->orderBy('name', 'asc')
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
     * Get students by campus, class, and section with fee status (AJAX).
     */
    public function getStudentsWithFeeStatus(Request $request)
    {
        $campus = $request->get('campus');
        $class = $request->get('class');
        $section = $request->get('section');
        $feeMonth = $request->get('fee_month');
        $feeYear = $request->get('fee_year');

        if (!$campus || !$class || !$section) {
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
            $studentsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($section))]);
        }

        $students = $studentsQuery->orderBy('student_code', 'asc')->get();

        // Build payment title if month and year are provided
        $paymentTitle = null;
        if ($feeMonth && $feeYear) {
            $paymentTitle = "Monthly Fee - {$feeMonth} {$feeYear}";
        }

        // Map students with fee status
        $studentsWithStatus = $students->map(function($student) use ($paymentTitle) {
            $hasFeeGenerated = false;
            
            if ($paymentTitle && $student->student_code) {
                $existingFee = StudentPayment::where('student_code', $student->student_code)
                    ->where('payment_title', $paymentTitle)
                    ->first();
                
                $hasFeeGenerated = (bool) $existingFee;
            }

            // Get parent name
            $parentName = $student->father_name ?? '';

            return [
                'id' => $student->id,
                'student_code' => $student->student_code ?? '',
                'student_name' => $student->student_name ?? '',
                'parent_name' => $parentName,
                'has_fee_generated' => $hasFeeGenerated,
                'status' => $hasFeeGenerated ? 'Generated' : 'Ready',
            ];
        });

        return response()->json(['students' => $studentsWithStatus]);
    }
}

