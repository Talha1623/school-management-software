<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Campus;
use App\Models\StudentPayment;
use App\Models\MonthlyFee;
use App\Models\CustomFee;
use Illuminate\Http\Request;
use Illuminate\View\View;

class HeadWiseDuesController extends Controller
{
    /**
     * Display the head wise dues summary report.
     */
    public function index(Request $request): View
    {
        $filterCampus = $request->get('filter_campus');
        $filterClass = $request->get('filter_class');

        // Get all campuses
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $allCampuses = $campusesFromClasses->unique()->sort();
            $campuses = $allCampuses->map(function($campus) {
                return (object)['campus_name' => $campus];
            });
        }

        // Build class options for filter (scoped to campus if selected)
        $classOptions = ClassModel::whereNotNull('class_name');
        if ($filterCampus) {
            $classOptions->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
        }
        $classOptions = $classOptions->distinct()->pluck('class_name')->sort()->values();
        if ($classOptions->isEmpty()) {
            $classOptionsFromStudents = Student::whereNotNull('class');
            if ($filterCampus) {
                $classOptionsFromStudents->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($filterCampus))]);
            }
            $classOptionsFromStudents = $classOptionsFromStudents->distinct()->pluck('class')->sort()->values();
            $classOptions = $classOptionsFromStudents->isEmpty()
                ? collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th'])
                : $classOptionsFromStudents;
        }
        
        // Process data for all campuses
        $allCampusData = collect();
        $grandTotal = [
            'monthly_fee' => 0,
            'muhammad_talha' => 0,
            'card_fees' => 0,
            'total' => 0
        ];
        
        $campusesToProcess = $campuses;
        if ($filterCampus) {
            $campusesToProcess = $campuses->filter(function($campus) use ($filterCampus) {
                $campusName = is_object($campus) ? ($campus->campus_name ?? '') : $campus;
                return strtolower(trim($campusName)) === strtolower(trim($filterCampus));
            })->values();
        }

        foreach ($campusesToProcess as $campus) {
            $campusName = is_object($campus) ? ($campus->campus_name ?? '') : $campus;
            
            if (empty($campusName)) {
                continue;
            }
            
            // Get all classes for this campus
            $classes = ClassModel::where('campus', $campusName)
                ->whereNotNull('class_name')
                ->distinct()
                ->pluck('class_name')
                ->sort()
                ->values();
            
            if ($classes->isEmpty()) {
                // Fallback: get classes from students
                $classes = Student::where('campus', $campusName)
                    ->whereNotNull('class')
                    ->distinct()
                    ->pluck('class')
                    ->sort()
                    ->values();
            }

            if ($filterClass) {
                $classes = $classes->filter(function($className) use ($filterClass) {
                    return strtolower(trim($className)) === strtolower(trim($filterClass));
                })->values();
            }
            
            $headWiseData = collect();
            $campusTotal = [
                'monthly_fee' => 0,
                'muhammad_talha' => 0,
                'card_fees' => 0,
                'total' => 0
            ];
            
            // Calculate head wise dues for each class
            foreach ($classes as $className) {
                // Get all students for this class and campus
                $students = Student::where('campus', $campusName)
                    ->where('class', $className)
                    ->get();
                
                $classTotals = [
                    'monthly_fee' => 0,
                    'muhammad_talha' => 0,
                    'card_fees' => 0,
                    'total' => 0
                ];
                
                foreach ($students as $student) {
                    // Calculate Monthly Fee unpaid
                    $monthlyFeeUnpaid = $this->calculateMonthlyFeeUnpaid($student);
                    $classTotals['monthly_fee'] += $monthlyFeeUnpaid;
                    
                    // Calculate Muhammad Talha unpaid (custom fee)
                    $muhammadTalhaUnpaid = $this->calculateCustomFeeUnpaid($student, 'Muhammad Talha');
                    $classTotals['muhammad_talha'] += $muhammadTalhaUnpaid;
                    
                    // Calculate Card Fees unpaid
                    $cardFeesUnpaid = $this->calculateCardFeesUnpaid($student);
                    $classTotals['card_fees'] += $cardFeesUnpaid;
                }
                
                $classTotals['total'] = $classTotals['monthly_fee'] + $classTotals['muhammad_talha'] + $classTotals['card_fees'];
                
                // Add to campus totals
                $campusTotal['monthly_fee'] += $classTotals['monthly_fee'];
                $campusTotal['muhammad_talha'] += $classTotals['muhammad_talha'];
                $campusTotal['card_fees'] += $classTotals['card_fees'];
                $campusTotal['total'] += $classTotals['total'];
                
                $headWiseData->push([
                    'class' => $className,
                    'monthly_fee' => $classTotals['monthly_fee'],
                    'muhammad_talha' => $classTotals['muhammad_talha'],
                    'card_fees' => $classTotals['card_fees'],
                    'total' => $classTotals['total']
                ]);
            }
            
            // Add to grand totals
            $grandTotal['monthly_fee'] += $campusTotal['monthly_fee'];
            $grandTotal['muhammad_talha'] += $campusTotal['muhammad_talha'];
            $grandTotal['card_fees'] += $campusTotal['card_fees'];
            $grandTotal['total'] += $campusTotal['total'];
            
            // Only add campus data if it has classes
            if ($headWiseData->count() > 0) {
                $allCampusData->push([
                    'campus' => $campusName,
                    'data' => $headWiseData,
                    'total' => $campusTotal
                ]);
            }
        }
        
        return view('reports.head-wise-dues', compact(
            'allCampusData',
            'grandTotal',
            'campuses',
            'classOptions',
            'filterCampus',
            'filterClass'
        ));
    }

    /**
     * Get classes by campus (AJAX endpoint)
     */
    public function getClassesByCampus(Request $request): \Illuminate\Http\JsonResponse
    {
        $campus = $request->get('campus');

        $classesQuery = ClassModel::whereNotNull('class_name');
        if ($campus) {
            $classesQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        $classes = $classesQuery->distinct()->pluck('class_name')->sort()->values();

        if ($classes->isEmpty()) {
            $classesFromStudents = Student::whereNotNull('class');
            if ($campus) {
                $classesFromStudents->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $classesFromStudents = $classesFromStudents->distinct()->pluck('class')->sort()->values();
            $classes = $classesFromStudents->isEmpty()
                ? collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th'])
                : $classesFromStudents;
        }

        $classes = $classes->map(function($class) {
            return trim((string) $class);
        })->filter(function($class) {
            return $class !== '';
        })->unique()->sort()->values();

        return response()->json(['classes' => $classes]);
    }
    
    /**
     * Calculate unpaid monthly fee for a student.
     */
    private function calculateMonthlyFeeUnpaid($student): float
    {
        // Get monthly fee amount from student record
        $monthlyFeeAmount = $student->monthly_fee ?? 0;
        
        if ($monthlyFeeAmount == 0) {
            return 0;
        }
        
        // Get total paid for monthly fee
        $totalPaid = StudentPayment::where('student_code', $student->student_code)
            ->where('campus', $student->campus)
            ->where(function($query) {
                $query->where('payment_title', 'Monthly Fee')
                      ->orWhere('payment_title', 'like', '%Monthly%');
            })
            ->sum('payment_amount');
        
        $unpaid = max(0, $monthlyFeeAmount - $totalPaid);
        
        return $unpaid;
    }
    
    /**
     * Calculate unpaid custom fee for a student by fee name.
     */
    private function calculateCustomFeeUnpaid($student, $feeName): float
    {
        // Get custom fee amount from CustomFee table
        $customFee = CustomFee::where('campus', $student->campus)
            ->where('class', $student->class)
            ->where('section', $student->section)
            ->where('fee_type', $feeName)
            ->first();
        
        $customFeeAmount = $customFee ? ($customFee->amount ?? 0) : 0;
        
        // If not found in CustomFee, check if there are any payments with this title to determine if fee exists
        if ($customFeeAmount == 0) {
            // Check if student has any payments for this fee type (to see if fee was ever assigned)
            $hasPayment = StudentPayment::where('student_code', $student->student_code)
                ->where('campus', $student->campus)
                ->where('payment_title', $feeName)
                ->exists();
            
            if (!$hasPayment) {
                return 0;
            }
        }
        
        // Get total paid for this custom fee
        $totalPaid = StudentPayment::where('student_code', $student->student_code)
            ->where('campus', $student->campus)
            ->where('payment_title', $feeName)
            ->sum('payment_amount');
        
        $unpaid = max(0, $customFeeAmount - $totalPaid);
        
        return $unpaid;
    }
    
    /**
     * Calculate unpaid card fees for a student.
     */
    private function calculateCardFeesUnpaid($student): float
    {
        // Get card fee amount from student's other_fee_amount when fee_type is Card Fee
        $cardFeeAmount = 0;
        if (isset($student->fee_type) && 
            (strtolower($student->fee_type) == 'card fee' || strtolower($student->fee_type) == 'card')) {
            $cardFeeAmount = $student->other_fee_amount ?? 0;
        }
        
        // Also check CustomFee table
        if ($cardFeeAmount == 0) {
            $cardFee = CustomFee::where('campus', $student->campus)
                ->where('class', $student->class)
                ->where('section', $student->section)
                ->where(function($query) {
                    $query->where('fee_type', 'Card Fee')
                          ->orWhere('fee_type', 'Card Fees')
                          ->orWhere('fee_type', 'like', '%Card%');
                })
                ->first();
            
            $cardFeeAmount = $cardFee ? ($cardFee->amount ?? 0) : 0;
        }
        
        if ($cardFeeAmount == 0) {
            return 0;
        }
        
        // Get total paid for card fees
        $totalPaid = StudentPayment::where('student_code', $student->student_code)
            ->where('campus', $student->campus)
            ->where(function($query) {
                $query->where('payment_title', 'Card Fee')
                      ->orWhere('payment_title', 'Card Fees')
                      ->orWhere('payment_title', 'like', '%Card%');
            })
            ->sum('payment_amount');
        
        $unpaid = max(0, $cardFeeAmount - $totalPaid);
        
        return $unpaid;
    }
}
