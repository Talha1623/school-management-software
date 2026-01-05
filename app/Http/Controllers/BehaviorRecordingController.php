<?php

namespace App\Http\Controllers;

use App\Models\BehaviorRecord;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

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
        $filterDate = $request->get('filter_date', date('Y-m-d'));

        // Get classes - filter by teacher's assigned classes if teacher
        $classes = collect();
        $staff = Auth::guard('staff')->user();
        
        if ($staff && $staff->isTeacher()) {
            // Get classes from teacher's assigned subjects
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->get();
            
            // Get classes from teacher's assigned sections
            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->get();
            
            // Merge classes from both sources
            $classes = $assignedSubjects->pluck('class')
                ->merge($assignedSections->pluck('class'))
                ->map(function($class) {
                    return trim($class);
                })
                ->filter(function($class) {
                    return !empty($class);
                })
                ->unique()
                ->sort()
                ->values();
        } else {
            // For non-teachers, get all classes
            $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
            
            if ($classes->isEmpty()) {
                $classesFromSubjects = Subject::whereNotNull('class')->distinct()->pluck('class')->sort();
                $classes = $classesFromSubjects->isEmpty() ? collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']) : $classesFromSubjects;
            }
        }

        // Get sections based on selected class - filter by teacher's assigned subjects if teacher
        $sections = collect();
        if ($filterClass) {
            if ($staff && $staff->isTeacher()) {
                // Get sections from teacher's assigned subjects for this class
                $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                    ->get();
                
                // Get sections from teacher's assigned sections for this class
                $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                    ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                    ->get();
                
                // Merge sections from both sources
                $sections = $assignedSubjects->pluck('section')
                    ->merge($assignedSections->pluck('name'))
                    ->map(function($section) {
                        return trim($section);
                    })
                    ->filter(function($section) {
                        return !empty($section);
                    })
                    ->unique()
                    ->sort()
                    ->values();
            } else {
                // For non-teachers, get all sections
                $sections = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                    ->whereNotNull('name')
                    ->distinct()
                    ->pluck('name')
                    ->sort()
                    ->values();
                
                if ($sections->isEmpty()) {
                    $sectionsFromSubjects = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))])
                        ->whereNotNull('section')
                        ->distinct()
                        ->pluck('section')
                        ->sort()
                        ->values();
                    $sections = $sectionsFromSubjects;
                }
            }
        }

        // Get students based on filters - only those connected to parents
        $students = collect();
        $campusName = null;
        
        if ($filterClass) {
            $studentsQuery = Student::query();
            
            // Only get students who have parent connection (parent_account_id or father_name)
            $studentsQuery->where(function($query) {
                $query->whereNotNull('parent_account_id')
                      ->orWhere(function($q) {
                          $q->whereNotNull('father_name')
                            ->where('father_name', '!=', '');
                      });
            });
            
            $studentsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($filterClass))]);
            
            if ($filterSection) {
                $studentsQuery->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($filterSection))]);
            }
            
            $students = $studentsQuery->orderBy('student_code', 'asc')
                ->orderBy('student_name', 'asc')
                ->get();
            
            // Get campus from first student or use default
            if ($students->count() > 0) {
                $campusName = $students->first()->campus ?? 'Main Campus';
            }
        }

        $types = collect(['daily behavior' => 'Daily Behavior']);

        return view('student-behavior.recording', compact(
            'types',
            'classes',
            'sections',
            'students',
            'campusName',
            'filterType',
            'filterClass',
            'filterSection',
            'filterDate'
        ));
    }

    /**
     * Get sections by class (AJAX endpoint)
     */
    public function getSectionsByClass(Request $request)
    {
        $class = $request->get('class');
        
        if (!$class) {
            return response()->json(['sections' => []]);
        }

        $staff = Auth::guard('staff')->user();
        $sections = collect();
        
        // Filter by teacher's assigned subjects and sections if teacher
        if ($staff && $staff->isTeacher()) {
            // Get sections from teacher's assigned subjects for this class
            $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                ->get();
            
            // Get sections from teacher's assigned sections for this class
            $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($staff->name ?? ''))])
                ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                ->get();
            
            // Merge sections from both sources
            $sections = $assignedSubjects->pluck('section')
                ->merge($assignedSections->pluck('name'))
                ->map(function($section) {
                    return trim($section);
                })
                ->filter(function($section) {
                    return !empty($section);
                })
                ->unique()
                ->sort()
                ->values();
        } else {
            // For non-teachers, get all sections
            $sections = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                ->whereNotNull('name')
                ->distinct()
                ->pluck('name')
                ->sort()
                ->values();
            
            if ($sections->isEmpty()) {
                $sections = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                    ->whereNotNull('section')
                    ->distinct()
                    ->pluck('section')
                    ->sort()
                    ->values();
            }
        }

        return response()->json(['sections' => $sections]);
    }

    /**
     * Store behavior record
     */
    public function store(Request $request)
    {
        try {
            $validated = $request->validate([
                'student_id' => ['required', 'exists:students,id'],
                'type' => ['required', 'string'],
                'points' => ['required', 'integer'],
                'class' => ['required', 'string'],
                'section' => ['nullable', 'string'],
                'campus' => ['required', 'string'],
                'date' => ['required', 'date'],
            ]);

            // Get student details
            $student = Student::findOrFail($validated['student_id']);

            // Normalize section - convert empty string to null
            $section = !empty($validated['section']) ? $validated['section'] : null;

            // Check if record already exists for this student, type, and date
            $existingRecord = BehaviorRecord::where('student_id', $validated['student_id'])
                ->where('type', $validated['type'])
                ->whereDate('date', $validated['date'])
                ->first();

            if ($existingRecord) {
                // Update existing record
                $existingRecord->update([
                    'points' => $validated['points'],
                    'section' => $section,
                    'description' => $validated['points'] > 0 ? '+' . $validated['points'] . ' Points' : $validated['points'] . ' Points',
                ]);
            } else {
                // Create new record
                BehaviorRecord::create([
                    'student_id' => $validated['student_id'],
                    'student_name' => $student->student_name,
                    'type' => $validated['type'],
                    'points' => $validated['points'],
                    'class' => $validated['class'],
                    'section' => $section,
                    'campus' => $validated['campus'],
                    'date' => $validated['date'],
                    'description' => $validated['points'] > 0 ? '+' . $validated['points'] . ' Points' : $validated['points'] . ' Points',
                    'recorded_by' => auth()->user()->name ?? auth()->user()->email ?? 'System',
                ]);
            }

            return response()->json([
                'success' => true,
                'message' => 'Behavior record saved successfully'
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors()
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Error saving behavior record: ' . $e->getMessage()
            ], 500);
        }
    }
}

