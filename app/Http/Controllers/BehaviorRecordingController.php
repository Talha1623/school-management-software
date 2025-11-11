<?php

namespace App\Http\Controllers;

use App\Models\BehaviorRecord;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BehaviorRecordingController extends Controller
{
    /**
     * Display the behavior records with filters.
     */
    public function index(Request $request): View
    {
        // Get filter values
        $filterType = $request->get('filter_type');
        $filterClass = $request->get('filter_class');
        $filterSection = $request->get('filter_section');
        $filterDate = $request->get('filter_date');

        // Get behavior types from records
        $types = BehaviorRecord::whereNotNull('type')->distinct()->pluck('type')->sort()->values();
        
        if ($types->isEmpty()) {
            $types = collect(['Positive', 'Negative', 'Warning', 'Excellent', 'Needs Improvement']);
        }

        // Get classes
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        
        if ($classes->isEmpty()) {
            $classes = Student::whereNotNull('class')->distinct()->pluck('class')->sort()->values();
        }

        // Get sections
        $sections = Section::whereNotNull('name')->distinct()->pluck('name')->sort()->values();
        
        if ($sections->isEmpty()) {
            $sections = Student::whereNotNull('section')->distinct()->pluck('section')->sort()->values();
        }

        // Query behavior records
        $query = BehaviorRecord::with('student');

        if ($filterType) {
            $query->where('type', $filterType);
        }
        if ($filterClass) {
            $query->where('class', $filterClass);
        }
        if ($filterSection) {
            $query->where('section', $filterSection);
        }
        if ($filterDate) {
            $query->whereDate('date', $filterDate);
        }

        $behaviorRecords = $query->orderBy('date', 'desc')->get();

        return view('student-behavior.recording', compact(
            'types',
            'classes',
            'sections',
            'behaviorRecords',
            'filterType',
            'filterClass',
            'filterSection',
            'filterDate'
        ));
    }
}

