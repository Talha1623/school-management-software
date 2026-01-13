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
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        if ($classes->isEmpty()) {
            $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
        }

        // Get sections
        $sections = Section::whereNotNull('name')->distinct()->pluck('name')->sort()->values();
        if ($sections->isEmpty()) {
            $sections = collect(['A', 'B', 'C', 'D', 'E']);
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
            'typeOptions',
            'statusOptions',
            'students',
            'filterCampus',
            'filterClass',
            'filterSection',
            'filterType',
            'filterStatus'
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
}

