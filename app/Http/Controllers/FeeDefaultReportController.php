<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\FeeType;
use App\Models\StudentPayment;
use Illuminate\Http\Request;
use Illuminate\View\View;

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

        // Status options
        $statusOptions = collect(['Paid', 'Pending', 'Default', 'Partial', 'Overdue']);

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

        // Get students
        $students = $query->orderBy('student_name')->get();

        // Filter by type and status if needed (this would require additional logic based on payment records)
        // For now, we'll return all students and let the view handle display

        return view('reports.fee-default', compact(
            'campuses',
            'classes',
            'sections',
            'feeTypes',
            'statusOptions',
            'students',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterType',
            'filterStatus'
        ));
    }
}

