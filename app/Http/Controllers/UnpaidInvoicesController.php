<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\FeeType;
use App\Models\StudentPayment;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UnpaidInvoicesController extends Controller
{
    /**
     * Display the list of unpaid invoices with filters.
     */
    public function index(Request $request): View
    {
        // Get filter values
        $filterCampus = $request->get('filter_campus');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterType = $request->get('filter_type');

        // Get campuses from classes or sections
        $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
        $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
        $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
        
        if ($campuses->isEmpty()) {
            $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
        }

        // Get classes
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        if ($classes->isEmpty()) {
            $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
        }

        // Get sections
        $sections = Section::whereNotNull('name')->distinct()->pluck('name')->sort()->values();
        if ($sections->isEmpty()) {
            $sections = collect(['A', 'B', 'C', 'D', 'E']);
        }

        // Get fee types
        $feeTypes = FeeType::whereNotNull('fee_name')->distinct()->pluck('fee_name')->sort()->values();
        if ($feeTypes->isEmpty()) {
            $feeTypes = collect(['Monthly Fee', 'Transport Fee', 'Custom Fee', 'Advance Fee']);
        }

        // Query students based on filters
        $query = Student::query();
        
        if ($filterCampus) {
            $query->where('campus', $filterCampus);
        }
        if ($filterClass) {
            $query->where('class', $filterClass);
        }
        if ($filterSection) {
            $query->where('section', $filterSection);
        }

        $students = $query->orderBy('student_name')->get();

        // Prepare unpaid invoices list
        $unpaidInvoices = collect();

        foreach ($students as $student) {
            // Get all payments made by this student
            $payments = StudentPayment::where('student_code', $student->student_code)->get();
            
            // Group payments by fee type (payment_title)
            $paidByType = $payments->groupBy('payment_title');
            
            // Get expected fees for this student
            $expectedFees = collect();
            
            // Monthly fee
            if ($student->monthly_fee && $student->monthly_fee > 0) {
                $expectedFees->push([
                    'type' => 'Monthly Fee',
                    'amount' => $student->monthly_fee,
                ]);
            }
            
            // Add other fee types from FeeType model
            foreach ($feeTypes as $feeType) {
                // Check if this fee type is relevant (you can customize this logic)
                if ($filterType && $filterType != $feeType) {
                    continue;
                }
                
                // For now, we'll show monthly fee as unpaid if not fully paid
                // You can extend this to check other fee types
            }
            
            // Check for unpaid invoices
            foreach ($expectedFees as $expectedFee) {
                $feeType = $expectedFee['type'];
                $expectedAmount = $expectedFee['amount'];
                
                // Skip if type filter is set and doesn't match
                if ($filterType && $filterType != $feeType) {
                    continue;
                }
                
                // Calculate paid amount for this fee type
                $paidAmount = $paidByType->get($feeType, collect())->sum('payment_amount');
                $unpaidAmount = $expectedAmount - $paidAmount;
                
                // Only show if there's an unpaid amount
                if ($unpaidAmount > 0) {
                    $unpaidInvoices->push([
                        'student_code' => $student->student_code,
                        'student_name' => $student->student_name,
                        'campus' => $student->campus,
                        'class' => $student->class,
                        'section' => $student->section,
                        'fee_type' => $feeType,
                        'expected_amount' => $expectedAmount,
                        'paid_amount' => $paidAmount,
                        'unpaid_amount' => $unpaidAmount,
                        'status' => $paidAmount > 0 ? 'Partial' : 'Unpaid',
                    ]);
                }
            }
            
            // Also check for other fee types that might be in payments but not fully paid
            // This handles cases where fee types are in the system but not in student record
            if ($filterType) {
                $typePayments = $payments->where('payment_title', $filterType);
                // You can add logic here to check against expected fees for this type
            }
        }

        return view('reports.unpaid-invoices', compact(
            'campuses',
            'classes',
            'sections',
            'feeTypes',
            'unpaidInvoices',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterType'
        ));
    }
}

