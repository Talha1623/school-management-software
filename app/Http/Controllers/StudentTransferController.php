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

        // Get classes from ClassModel (initial load)
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

        if (!$campus) {
            return response()->json(['students' => []]);
        }

        $studentsQuery = Student::whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))])
            ->whereNotNull('student_code');

        if ($class) {
            $studentsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
        }

        $students = $studentsQuery
            ->select('id', 'student_code', 'student_name', 'class')
            ->orderBy('student_code')
            ->get()
            ->map(function ($student) {
                return [
                    'id' => $student->id,
                    'code' => $student->student_code,
                    'name' => $student->student_name,
                    'class' => $student->class,
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
        $campus = $request->get('campus');
        $class = $request->get('class');
        
        if (!$code || strlen($code) < 2) {
            return response()->json(['students' => []]);
        }
        
        $studentsQuery = Student::query()
            ->where(function ($query) use ($code) {
                $query->where('student_code', 'like', "%{$code}%")
                    ->orWhere('student_name', 'like', "%{$code}%");
            });

        if ($campus) {
            $studentsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }

        if ($class) {
            $studentsQuery->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
        }

        $students = $studentsQuery
            ->select('id', 'student_code', 'student_name', 'campus', 'class', 'section')
            ->limit(10)
            ->get()
            ->map(function ($student) {
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
     * Get classes by campus (AJAX)
     */
    public function getClassesByCampus(Request $request)
    {
        $campus = $request->get('campus');

        if (!$campus) {
            return response()->json(['classes' => []]);
        }

        $classes = ClassModel::whereNotNull('class_name')
            ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))])
            ->distinct()
            ->pluck('class_name')
            ->sort()
            ->values();

        if ($classes->isEmpty()) {
            $classes = Student::whereNotNull('class')
                ->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))])
                ->distinct()
                ->pluck('class')
                ->sort()
                ->values();
        }

        return response()->json(['classes' => $classes]);
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
            'student_code' => ['nullable', 'string', 'max:255'],
            'student_id' => ['required', 'integer', 'exists:students,id'],
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

        if ($request->filled('from_campus')) {
            $fromCampus = trim((string) $request->from_campus);
            if ($fromCampus !== '' && strtolower(trim((string) $student->campus)) !== strtolower($fromCampus)) {
                return redirect()
                    ->route('student.transfer')
                    ->with('error', 'Selected student does not belong to the chosen From Campus.');
            }
        }

        $fromCampus = $student->campus;
        $normalizedToCampus = $this->resolveCampusName($validated['to_campus']);
        // Update campus
        $student->campus = $normalizedToCampus;
        
        // Update class if provided
        if ($request->filled('class')) {
            $student->class = trim((string) $validated['class']);
        }

        // If campus changed, generate new campus-wise student code
        if (!empty($normalizedToCampus) && trim((string) $fromCampus) !== trim((string) $normalizedToCampus)) {
            $student->student_code = $this->generateNextStudentCode($normalizedToCampus);
        }

        $student->save();

        // TODO: Move dues if move_dues is yes
        // TODO: Move payments if move_payments is yes
        // TODO: Send notification if notify_parent is yes

        return redirect()
            ->route('student.transfer')
            ->with('success', "Student {$student->student_name} ({$student->student_code}) transferred successfully to {$validated['to_campus']}!");
    }

    /**
     * Generate next student code per campus (e.g., ST1-001, ST2-001)
     */
    private function generateNextStudentCode(?string $campus): string
    {
        $prefix = $this->resolveCampusCodePrefix($campus);

        $students = Student::where('student_code', 'like', $prefix . '-%')
            ->whereNotNull('student_code')
            ->pluck('student_code')
            ->toArray();

        if (empty($students)) {
            return $prefix . '-001';
        }

        $maxNumber = 0;
        foreach ($students as $code) {
            if (preg_match('/^' . preg_quote($prefix, '/') . '-(\d+)$/i', $code, $matches)) {
                $number = (int) $matches[1];
                $maxNumber = max($maxNumber, $number);
            }
        }

        return $prefix . '-' . str_pad($maxNumber + 1, 3, '0', STR_PAD_LEFT);
    }

    private function resolveCampusCodePrefix(?string $campus): string
    {
        $campus = trim((string) $campus);
        if ($campus !== '') {
            $campusRecord = \App\Models\Campus::whereRaw('LOWER(TRIM(campus_name)) = ?', [strtolower($campus)])->first();
            if ($campusRecord && !empty($campusRecord->code_prefix)) {
                return strtoupper(trim($campusRecord->code_prefix));
            }

            if (preg_match('/(\d+)/', $campus, $matches)) {
                return 'ST' . $matches[1];
            }
        }

        return 'ST';
    }

    private function resolveCampusName(?string $campus): string
    {
        $campus = trim((string) $campus);
        if ($campus === '') {
            return $campus;
        }

        $record = Campus::whereRaw('LOWER(TRIM(campus_name)) = ?', [strtolower($campus)])->first();
        return $record ? ($record->campus_name ?? $campus) : $campus;
    }
}

