<?php

namespace App\Http\Controllers;

use App\Models\Student;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;

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
            'section' => ['nullable', 'string', 'max:255'],
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
        $oldClass = $student->class;
        
        // Update campus
        $student->campus = $normalizedToCampus;
        
        // Update class if provided
        if ($request->filled('class')) {
            $newClass = trim((string) $validated['class']);
            $student->class = $newClass;
            
            // Update section if provided
            if ($request->filled('section') && !empty(trim((string) $validated['section']))) {
                $student->section = trim((string) $validated['section']);
            } else {
                // If class changed and no section provided, clear section (since sections are class-specific)
                // This prevents students from appearing in Behavior Recording with wrong class/section combination
                if (strtolower(trim($oldClass ?? '')) !== strtolower(trim($newClass))) {
                    $student->section = null;
                }
            }
        }

        // If campus changed, generate new campus-wise student code
        $oldStudentCode = $student->student_code;
        if (!empty($normalizedToCampus) && trim((string) $fromCampus) !== trim((string) $normalizedToCampus)) {
            $newStudentCode = $this->generateNextStudentCode($normalizedToCampus);
            $student->student_code = $newStudentCode;
        }

        // Ensure campus is saved correctly
        $student->save();
        
        // Verify the save was successful
        $student->refresh();
        
        // Log for debugging
        \Log::info('Student Transfer Completed', [
            'student_id' => $student->id,
            'student_code' => $student->student_code,
            'student_name' => $student->student_name,
            'old_campus' => $fromCampus,
            'new_campus' => $student->campus,
            'normalized_to_campus' => $normalizedToCampus,
            'old_class' => $oldClass,
            'new_class' => $student->class,
            'section' => $student->section,
            'parent_account_id' => $student->parent_account_id,
            'father_name' => $student->father_name,
        ]);

        // Update student_payments based on move_dues and move_payments settings
        $moveDues = $request->input('move_dues', 'no') === 'yes';
        $movePayments = $request->input('move_payments', 'no') === 'yes';
        
        // Check if campus changed
        $campusChanged = !empty($normalizedToCampus) && trim((string) $fromCampus) !== trim((string) $normalizedToCampus);
        $codeChanged = $oldStudentCode !== $student->student_code;
        
        if ($campusChanged && $codeChanged) {
            // Campus changed and student code changed - update based on move_dues and move_payments
            if ($moveDues && $movePayments) {
                // Move both dues and payments
                DB::table('student_payments')
                    ->where('student_code', $oldStudentCode)
                    ->update([
                        'student_code' => $student->student_code,
                        'campus' => $normalizedToCampus
                    ]);
            } elseif ($moveDues) {
                // Move only dues (Generated fees)
                DB::table('student_payments')
                    ->where('student_code', $oldStudentCode)
                    ->where('method', 'Generated')
                    ->update([
                        'student_code' => $student->student_code,
                        'campus' => $normalizedToCampus
                    ]);
            } elseif ($movePayments) {
                // Move only payments (non-Generated)
                DB::table('student_payments')
                    ->where('student_code', $oldStudentCode)
                    ->where('method', '!=', 'Generated')
                    ->update([
                        'student_code' => $student->student_code,
                        'campus' => $normalizedToCampus
                    ]);
            }
            // If both are "no", don't update any payment records
        } elseif ($campusChanged) {
            // Campus changed but student code didn't change - update campus only if requested
            if ($moveDues) {
                DB::table('student_payments')
                    ->where('student_code', $student->student_code)
                    ->where('method', 'Generated')
                    ->update(['campus' => $normalizedToCampus]);
            }
            
            if ($movePayments) {
                DB::table('student_payments')
                    ->where('student_code', $student->student_code)
                    ->where('method', '!=', 'Generated')
                    ->update(['campus' => $normalizedToCampus]);
            }
        }
        // If campus didn't change, don't update payment records

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

            // Fallback: if campus name contains digits, use ST + digits
            if (preg_match('/(\d+)/', $campus, $matches)) {
                $prefix = 'ST' . $matches[1];
                // Save it to campus record for future use
                if ($campusRecord) {
                    $campusRecord->code_prefix = $prefix;
                    $campusRecord->save();
                }
                return strtoupper($prefix);
            }

            // If no code_prefix and no digits in name, find next available campus number
            // IMPORTANT: Check both existing campuses AND student/payment codes to avoid reusing deleted campus numbers
            $usedCampusNumbers = [];
            
            // Check from existing campuses with code_prefix (only active campuses, not deleted)
            $campusesWithPrefix = \App\Models\Campus::whereNotNull('code_prefix')
                ->where('code_prefix', 'like', 'ST%')
                ->get();
            
            foreach ($campusesWithPrefix as $campusWithPrefix) {
                if (preg_match('/^ST(\d+)$/i', $campusWithPrefix->code_prefix, $matches)) {
                    $campusNum = (int) $matches[1];
                    $usedCampusNumbers[] = $campusNum;
                }
            }
            
            // Also check from student codes to find any used campus numbers (even if campus is deleted)
            $studentCodes = \App\Models\Student::whereNotNull('student_code')
                ->where('student_code', 'like', 'ST%-%')
                ->pluck('student_code')
                ->toArray();
            
            // Also check from payment codes (covers deleted students/campuses)
            $paymentCodes = \App\Models\StudentPayment::whereNotNull('student_code')
                ->where('student_code', 'like', 'ST%-%')
                ->pluck('student_code')
                ->toArray();
            
            $allCodes = array_unique(array_merge($studentCodes, $paymentCodes));
            
            foreach ($allCodes as $code) {
                // Match pattern ST1-001, ST2-001, etc. to extract campus number
                if (preg_match('/^ST(\d+)-(\d+)$/i', $code, $matches)) {
                    $campusNum = (int) $matches[1];
                    if (!in_array($campusNum, $usedCampusNumbers)) {
                        $usedCampusNumbers[] = $campusNum;
                    }
                }
            }
            
            // Find next sequential campus number (always use max + 1, never reuse deleted numbers)
            $nextCampusNumber = 1;
            if (!empty($usedCampusNumbers)) {
                $maxCampusNumber = max($usedCampusNumbers);
                // Always use next sequential number after max (no gaps, no reuse)
                $nextCampusNumber = $maxCampusNumber + 1;
            }
            $prefix = 'ST' . $nextCampusNumber;
            
            // Save it to campus record for future use
            if ($campusRecord) {
                $campusRecord->code_prefix = $prefix;
                $campusRecord->save();
            }
            
            return strtoupper($prefix);
        }

        // If no campus provided, return ST (shouldn't happen in normal flow)
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

