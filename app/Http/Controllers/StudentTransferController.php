<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class StudentTransferController extends Controller
{
    /**
     * Display the student transfer page.
     */
    public function index(): View
    {
        // Get campuses from Campus model (Manage Campuses)
        $campuses = Campus::orderBy('campus_name', 'asc')->pluck('campus_name');
        
        if ($campuses->isEmpty()) {
            // Fallback to other sources if Campus table is empty
            $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
            $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
            $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
            
            if ($campuses->isEmpty()) {
                $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
            }
        }

        // Get classes from ClassModel
        $classes = ClassModel::whereNotNull('class_name')->distinct()->pluck('class_name')->sort()->values();
        if ($classes->isEmpty()) {
            $classes = collect(['Nursery', 'KG', '1st', '2nd', '3rd', '4th', '5th', '6th', '7th', '8th', '9th', '10th', '11th', '12th']);
        }

        return view('student.transfer', compact('campuses', 'classes'));
    }

    /**
     * Get students by campus and class (AJAX)
     */
    public function getStudents(Request $request)
    {
        $campus = $request->get('campus');
        $class = $request->get('class');
        
        if (!$campus || !$class) {
            return response()->json(['students' => []]);
        }
        
        $students = Student::where('campus', $campus)
            ->where('class', $class)
            ->whereNotNull('student_code')
            ->select('id', 'student_code', 'student_name')
            ->orderBy('student_code')
            ->get()
            ->map(function($student) {
                return [
                    'id' => $student->id,
                    'code' => $student->student_code,
                    'name' => $student->student_name
                ];
            });
        
        return response()->json(['students' => $students]);
    }

    /**
     * Search student by code (AJAX)
     */
    public function searchStudent(Request $request)
    {
        $code = $request->get('code');
        
        if (!$code || strlen($code) < 2) {
            return response()->json(['students' => []]);
        }
        
        $students = Student::where('student_code', 'like', "%{$code}%")
            ->orWhere('student_name', 'like', "%{$code}%")
            ->select('id', 'student_code', 'student_name', 'campus', 'class', 'section')
            ->limit(10)
            ->get()
            ->map(function($student) {
                return [
                    'id' => $student->id,
                    'code' => $student->student_code,
                    'name' => $student->student_name,
                    'campus' => $student->campus,
                    'class' => $student->class,
                    'section' => $student->section
                ];
            });
        
        return response()->json(['students' => $students]);
    }

    /**
     * Transfer student to another campus.
     */
    public function transfer(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'from_campus' => ['nullable', 'string', 'max:255'],
            'to_campus' => ['required', 'string', 'max:255'],
            'class' => ['required', 'string', 'max:255'],
            'student_code' => ['required', 'string', 'max:255'],
            'move_dues' => ['nullable', 'string', 'in:yes,no'],
            'move_payments' => ['nullable', 'string', 'in:yes,no'],
            'notify_parent' => ['nullable', 'string', 'in:yes,no'],
        ]);

        // Find student by ID or code
        if ($request->filled('student_id')) {
            $student = Student::find($request->student_id);
        } else {
            $student = Student::where('student_code', $validated['student_code'])->first();
        }

        if (!$student) {
            return redirect()
                ->route('student.transfer')
                ->with('error', 'Student not found with the provided student code.');
        }

        // Update campus
        $student->campus = $validated['to_campus'];
        
        // Update class if provided
        if ($request->filled('class')) {
            $student->class = $validated['class'];
        }

        $student->save();

        // TODO: Move dues if move_dues is yes
        // TODO: Move payments if move_payments is yes
        // TODO: Send notification if notify_parent is yes

        return redirect()
            ->route('student.transfer')
            ->with('success', "Student {$student->student_name} ({$student->student_code}) transferred successfully to {$validated['to_campus']}!");
    }
}

