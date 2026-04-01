<?php

namespace App\Http\Controllers;

use App\Models\ClassModel;
use App\Models\Campus;
use App\Models\GeneralSetting;
use App\Models\Section;
use App\Models\Student;
use App\Models\Subject;
use App\Models\StudentPayment;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ManageClassesController extends Controller
{
    /**
     * Display a listing of classes.
     */
    public function index(Request $request): View
    {
        $query = ClassModel::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(class_name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhere('numeric_no', 'like', "%{$search}%");
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $classes = $query->orderBy('numeric_no')->paginate($perPage)->withQueryString();
        
        // Load sections for each class (only sections that belong to existing classes)
        // Use case-insensitive matching to ensure accuracy
        foreach ($classes as $class) {
            $sectionsQuery = Section::whereRaw('LOWER(TRIM(COALESCE(class, ""))) = ?', [strtolower(trim($class->class_name))])
                ->whereNotNull('name')
                ->where('name', '!=', '');

            // Ensure sections are only from the same campus (if class campus is set)
            if (!empty($class->campus)) {
                $sectionsQuery->whereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [strtolower(trim($class->campus))]);
            }

            $sections = $sectionsQuery
                ->orderBy('name', 'asc')
                ->pluck('name')
                ->toArray();
            $class->sections = $sections;
        }
        
        // Get campuses for dropdown
        $campuses = Campus::orderBy('campus_name', 'asc')->get();
        
        // If no campuses found, get from classes
        if ($campuses->isEmpty()) {
            $campusesFromClasses = ClassModel::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();
            
            // Convert to collection of objects with campus_name property
            $campuses = collect();
            foreach ($campusesFromClasses as $campusName) {
                $campuses->push((object)['campus_name' => $campusName]);
            }
        }
        
        return view('classes.manage-classes', compact('classes', 'campuses'));
    }

    /**
     * Print classes list (dedicated print page)
     */
    public function print(Request $request): View
    {
        $query = ClassModel::query();

        // Search functionality (match index behavior)
        if ($request->filled('search')) {
            $search = trim((string) $request->get('search'));
            if ($search !== '') {
                $searchLower = strtolower($search);
                $query->where(function ($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(class_name) LIKE ?', ["%{$searchLower}%"])
                        ->orWhere('numeric_no', 'like', "%{$search}%");
                });
            }
        }

        $classes = $query->orderBy('numeric_no')->get();

        // Load sections for each class (same matching logic as index)
        foreach ($classes as $class) {
            $sectionsQuery = Section::whereRaw('LOWER(TRIM(COALESCE(class, ""))) = ?', [strtolower(trim($class->class_name))])
                ->whereNotNull('name')
                ->where('name', '!=', '');

            if (!empty($class->campus)) {
                $sectionsQuery->whereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [strtolower(trim($class->campus))]);
            }

            $class->sections = $sectionsQuery
                ->orderBy('name', 'asc')
                ->pluck('name')
                ->toArray();
        }

        $settings = GeneralSetting::getSettings();

        return view('classes.manage-classes-print', [
            'classes' => $classes,
            'settings' => $settings,
            'printedAt' => now()->format('d M Y, h:i A'),
        ]);
    }

    /**
     * Store a newly created class.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'class_name' => ['required', 'string', 'max:255'],
            'numeric_no' => ['required', 'integer', 'min:1'],
        ]);

        // Normalize class name and campus for comparison
        $className = strtolower(trim($validated['class_name']));
        $classCampus = $validated['campus'] ? strtolower(trim($validated['campus'])) : null;
        
        // Check if a class with the same name and campus already exists
        $existingClass = ClassModel::whereRaw('LOWER(TRIM(COALESCE(class_name, ""))) = ?', [$className]);
        
        if ($classCampus) {
            $existingClass->where(function($query) use ($classCampus) {
                $query->whereNull('campus')
                    ->orWhere('campus', '')
                    ->orWhereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [$classCampus]);
            });
        } else {
            $existingClass->where(function($query) {
                $query->whereNull('campus')
                    ->orWhere('campus', '');
            });
        }
        
        $existingClass = $existingClass->first();
        
        if ($existingClass) {
            $errorMessage = "Class '{$validated['class_name']}' already exists for campus '{$validated['campus']}'. Please use a different class name or campus.";
            return redirect()
                ->route('classes.manage-classes')
                ->with('error', $errorMessage)
                ->withInput();
        }
        
        // Before creating the class, delete any orphaned sections with this class name
        // Scope by campus to avoid removing sections from other campuses with same class
        $sectionsCleanupQuery = Section::whereRaw('LOWER(TRIM(COALESCE(class, ""))) = ?', [$className]);
        if ($classCampus) {
            $sectionsCleanupQuery->where(function($query) use ($classCampus) {
                $query->whereNull('campus')
                    ->orWhere('campus', '')
                    ->orWhereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [$classCampus]);
            });
        }
        $deletedSectionsCount = $sectionsCleanupQuery->delete();
        
        // Also delete any orphaned subjects with this class name (scoped by campus)
        $subjectsCleanupQuery = Subject::whereRaw('LOWER(TRIM(COALESCE(class, ""))) = ?', [$className]);
        if ($classCampus) {
            $subjectsCleanupQuery->where(function($query) use ($classCampus) {
                $query->whereNull('campus')
                    ->orWhere('campus', '')
                    ->orWhereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [$classCampus]);
            });
        }
        $deletedSubjectsCount = $subjectsCleanupQuery->delete();
        
        // Log cleanup for debugging
        if ($deletedSectionsCount > 0 || $deletedSubjectsCount > 0) {
            \Log::info('Cleaned up orphaned records before creating class', [
                'class_name' => $validated['class_name'],
                'deleted_sections_count' => $deletedSectionsCount,
                'deleted_subjects_count' => $deletedSubjectsCount,
            ]);
        }

        ClassModel::create($validated);

        return redirect()
            ->route('classes.manage-classes')
            ->with('success', 'Class created successfully!');
    }

    /**
     * Update the specified class.
     */
    public function update(Request $request, ClassModel $class_model): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'class_name' => ['required', 'string', 'max:255'],
            'numeric_no' => ['required', 'integer', 'min:1'],
        ]);

        // Normalize class name and campus for comparison
        $className = strtolower(trim($validated['class_name']));
        $classCampus = $validated['campus'] ? strtolower(trim($validated['campus'])) : null;
        
        // Check if another class with the same name and campus already exists (excluding current class)
        $existingClass = ClassModel::where('id', '!=', $class_model->id)
            ->whereRaw('LOWER(TRIM(COALESCE(class_name, ""))) = ?', [$className]);
        
        if ($classCampus) {
            $existingClass->where(function($query) use ($classCampus) {
                $query->whereNull('campus')
                    ->orWhere('campus', '')
                    ->orWhereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [$classCampus]);
            });
        } else {
            $existingClass->where(function($query) {
                $query->whereNull('campus')
                    ->orWhere('campus', '');
            });
        }
        
        $existingClass = $existingClass->first();
        
        if ($existingClass) {
            $errorMessage = "Class '{$validated['class_name']}' already exists for campus '{$validated['campus']}'. Please use a different class name or campus.";
            return redirect()
                ->route('classes.manage-classes')
                ->with('error', $errorMessage)
                ->withInput();
        }

        $class_model->update($validated);

        return redirect()
            ->route('classes.manage-classes')
            ->with('success', 'Class updated successfully!');
    }

    /**
     * Remove the specified class.
     */
    public function destroy(ClassModel $class_model): RedirectResponse
    {
        // Normalize class name for comparison (trim and lowercase)
        $className = strtolower(trim($class_model->class_name));
        $classCampus = $class_model->campus ? strtolower(trim($class_model->campus)) : null;
        
        // Check if there are any students in this class
        // Use exact matching: class name must match exactly (case-insensitive, trimmed)
        $studentsQuery = Student::whereNotNull('class')
            ->where('class', '!=', '')
            ->whereRaw('LOWER(TRIM(COALESCE(class, ""))) = ?', [$className]);
        
        // If class has a campus, match students from that campus OR students with no campus set
        // This handles cases where students might not have campus set but belong to the class
        if ($classCampus) {
            $studentsQuery->where(function($query) use ($classCampus) {
                $query->whereNull('campus')
                    ->orWhere('campus', '')
                    ->orWhereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [$classCampus]);
            });
        }
        
        // Get actual students to verify
        $actualStudents = $studentsQuery
            ->select('id', 'student_code', 'student_name', 'class', 'section', 'campus')
            ->get();
        
        // Final verification: ensure exact matches
        $validStudents = $actualStudents->filter(function($student) use ($className, $classCampus) {
            $studentClass = strtolower(trim($student->class ?? ''));
            $studentCampus = $student->campus ? strtolower(trim($student->campus)) : '';
            
            // Class must match exactly
            if ($studentClass !== $className || empty($studentClass)) {
                return false;
            }
            
            // If class has campus, student must have the same campus OR no campus set
            // (students without campus can belong to a class with campus)
            if ($classCampus) {
                // Allow if student has no campus OR campus matches
                if (!empty($studentCampus) && $studentCampus !== $classCampus) {
                    return false;
                }
            }
            
            return true;
        });
        
        $validStudentsCount = $validStudents->count();

        // If valid students found, block deletion and show error message
        if ($validStudentsCount > 0) {
            // Build detailed error message with student codes and full details
            $studentDetails = $validStudents->map(function($s) {
                return "{$s->student_code} ({$s->student_name}) - Campus: " . ($s->campus ?: 'Not Set');
            })->implode(', ');
            
            // Log for debugging
            \Log::info('Class deletion blocked - Valid students found', [
                'class_name' => $class_model->class_name,
                'class_campus' => $class_model->campus,
                'normalized_class_name' => $className,
                'normalized_campus' => $classCampus,
                'students_count' => $validStudentsCount,
                'students' => $validStudents->map(function($s) {
                    return [
                        'id' => $s->id,
                        'code' => $s->student_code,
                        'name' => $s->student_name,
                        'class' => $s->class,
                        'section' => $s->section,
                        'campus' => $s->campus,
                    ];
                })->toArray(),
            ]);
            
            $errorMessage = "Cannot delete class '{$class_model->class_name}' because it has {$validStudentsCount} student(s) enrolled. Please transfer all students to another class first.";
            
            return redirect()
                ->route('classes.manage-classes')
                ->with('error', $errorMessage);
        }
        
        // Clear teacher field from all sections of this class before deleting
        // Scope by campus to avoid touching sections from other campuses
        $sectionsQuery = Section::whereRaw('LOWER(TRIM(COALESCE(class, ""))) = ?', [$className]);
        if ($classCampus) {
            $sectionsQuery->where(function($query) use ($classCampus) {
                $query->whereNull('campus')
                    ->orWhere('campus', '')
                    ->orWhereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [$classCampus]);
            });
        }
        $sectionsQuery->update(['teacher' => null]);
        
        // Delete all sections associated with this class (scoped by campus)
        $deletedSectionsCount = $sectionsQuery->delete();
        
        // Delete all subjects associated with this class (scoped by campus)
        $subjectsQuery = Subject::whereRaw('LOWER(TRIM(COALESCE(class, ""))) = ?', [$className]);
        if ($classCampus) {
            $subjectsQuery->where(function($query) use ($classCampus) {
                $query->whereNull('campus')
                    ->orWhere('campus', '')
                    ->orWhereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [$classCampus]);
            });
        }
        $deletedSubjectsCount = $subjectsQuery->delete();
        
        // Log deletion for debugging
        \Log::info('Class deleted with associated records', [
            'class_name' => $class_model->class_name,
            'normalized_class_name' => $className,
            'deleted_sections_count' => $deletedSectionsCount,
            'deleted_subjects_count' => $deletedSubjectsCount,
        ]);

        $class_model->delete();

        return redirect()
            ->route('classes.manage-classes')
            ->with('success', 'Class deleted successfully!');
    }

    /**
     * Export classes to Excel, CSV, or PDF
     */
    public function export(Request $request, string $format)
    {
        $query = ClassModel::query();
        
        // Apply search filter if present
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('campus', 'like', "%{$search}%")
                  ->orWhere('class_name', 'like', "%{$search}%")
                  ->orWhere('numeric_no', 'like', "%{$search}%");
            });
        }
        
        $classes = $query->orderBy('numeric_no')->get();
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($classes);
            case 'csv':
                return $this->exportCSV($classes);
            case 'pdf':
                return $this->exportPDF($classes);
            default:
                return redirect()->route('classes.manage-classes')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($classes)
    {
        $filename = 'classes_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($classes) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['ID', 'Campus', 'Class Name', 'Numeric No', 'Created At']);
            
            foreach ($classes as $class) {
                fputcsv($file, [
                    $class->id,
                    $class->campus,
                    $class->class_name,
                    $class->numeric_no,
                    $class->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to CSV
     */
    private function exportCSV($classes)
    {
        $filename = 'classes_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($classes) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['ID', 'Campus', 'Class Name', 'Numeric No', 'Created At']);
            
            foreach ($classes as $class) {
                fputcsv($file, [
                    $class->id,
                    $class->campus,
                    $class->class_name,
                    $class->numeric_no,
                    $class->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($classes)
    {
        $html = view('classes.manage-classes-pdf', compact('classes'))->render();
        
        return response($html)
            ->header('Content-Type', 'text/html');
    }

    /**
     * Verify credentials before passout action.
     */
    public function verifyPassout(Request $request, ClassModel $class_model): JsonResponse
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        $email = $request->input('email');
        $password = $request->input('password');

        // Try admin guard first
        $admin = \App\Models\AdminRole::where('email', $email)->first();
        
        if ($admin && Hash::check($password, $admin->password)) {
            return response()->json([
                'success' => true,
                'message' => 'Verification successful.',
            ]);
        }

        // Try accountant guard
        $accountant = \App\Models\Accountant::where('email', $email)->first();
        
        if ($accountant && Hash::check($password, $accountant->password)) {
            return response()->json([
                'success' => true,
                'message' => 'Verification successful.',
            ]);
        }

        return response()->json([
            'success' => false,
            'message' => 'Invalid email or password. Please try again.',
        ], 401);
    }

    /**
     * Passout all students from a class.
     */
    public function passout(Request $request, ClassModel $class_model)
    {
        // Check if user is authenticated (admin or accountant)
        if (!Auth::guard('admin')->check() && !Auth::guard('accountant')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Authentication required. Please login.',
            ], 401);
        }

        // Normalize class name and campus for comparison
        $className = strtolower(trim($class_model->class_name));
        $classCampus = $class_model->campus ? strtolower(trim($class_model->campus)) : null;
        
        // Find all students in this class
        $studentsQuery = Student::whereNotNull('class')
            ->where('class', '!=', '')
            ->whereRaw('LOWER(TRIM(COALESCE(class, ""))) = ?', [$className]);
        
        // If class has a campus, match students from that campus OR students with no campus set
        if ($classCampus) {
            $studentsQuery->where(function($query) use ($classCampus) {
                $query->whereNull('campus')
                    ->orWhere('campus', '')
                    ->orWhereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [$classCampus]);
            });
        }
        
        $students = $studentsQuery->get();
        
        if ($students->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No students found in this class to passout.',
            ], 404);
        }
        
        // Update all students' class to "Passout" and store original class
        $updatedCount = 0;
        foreach ($students as $student) {
            // Store original class and section before passout
            if ($student->class && strtolower(trim($student->class)) !== 'passout') {
                $student->previous_class = $student->class;
            }
            if ($student->section) {
                $student->previous_section = $student->section;
            }
            $student->class = 'Passout';
            $student->section = null; // Clear section when passing out
            $student->save();
            $updatedCount++;
        }
        
        return response()->json([
            'success' => true,
            'message' => "Successfully passed out {$updatedCount} student(s) from class '{$class_model->class_name}'.",
            'count' => $updatedCount,
        ]);
    }

    /**
     * Transfer students from one campus to another.
     */
    public function transfer(Request $request, ClassModel $class_model): JsonResponse
    {
        $request->validate([
            'from_campus' => 'required|string',
            'to_campus' => 'required|string',
            'class' => 'nullable|string',
            'section' => 'nullable|string',
            'move_dues' => 'nullable|boolean',
            'move_payments' => 'nullable|boolean',
            'notify_admin' => 'nullable|boolean',
        ]);

        $fromCampus = trim($request->input('from_campus'));
        $toCampus = trim($request->input('to_campus'));
        $normalizedToCampus = $this->resolveCampusName($toCampus); // Normalize campus name
        $newClass = $request->input('class') ? trim($request->input('class')) : null;
        $newSection = $request->input('section') ? trim($request->input('section')) : null;
        $moveDues = $request->input('move_dues') == '1' || $request->boolean('move_dues', false);
        $movePayments = $request->input('move_payments') == '1' || $request->boolean('move_payments', false);
        $notifyAdmin = $request->input('notify_admin') == '1' || $request->boolean('notify_admin', false);

        if (strtolower($fromCampus) === strtolower($normalizedToCampus)) {
            return response()->json([
                'success' => false,
                'message' => 'From Campus and To Campus cannot be the same.',
            ], 400);
        }

        // Find all students from the source campus (transfer all students from campus)
        $studentsQuery = Student::whereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [strtolower(trim($fromCampus))]);
        
        // Exclude passout students
        $passoutClasses = ['passout', 'pass out', 'passed out', 'passedout', 'graduated', 'graduate', 'alumni'];
        $studentsQuery->where(function($q) use ($passoutClasses) {
            $q->whereNull('class')
              ->orWhere('class', '')
              ->orWhere(function($subQ) use ($passoutClasses) {
                  $subQ->whereRaw("LOWER(TRIM(COALESCE(class, ''))) NOT IN ('" . implode("', '", array_map('strtolower', $passoutClasses)) . "')");
              });
        });
        
        $students = $studentsQuery->get();
        
        if ($students->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No students found in the source campus to transfer.',
            ], 404);
        }

        $transferredCount = 0;
        $updatedStudents = [];
        $oldStudentCodes = []; // Store old codes for payment updates
        $newStudentCodes = []; // Store new codes mapping (old_code => new_code)

        foreach ($students as $student) {
            $oldCode = $student->student_code;
            $oldStudentCodes[] = $oldCode;
            
            // Update campus (use normalized campus name)
            $student->campus = $normalizedToCampus;
            
            // Update class if provided
            if ($newClass) {
                $student->class = $newClass;
            }
            
            // Update section if provided
            if ($newSection !== null) {
                $student->section = $newSection;
            }
            
            // Generate new student code based on new campus (only if campus actually changed)
            if (strtolower(trim($fromCampus)) !== strtolower(trim($normalizedToCampus))) {
                $newCode = $this->generateNextStudentCode($normalizedToCampus);
                $student->student_code = $newCode;
                $newStudentCodes[$oldCode] = $newCode; // Map old code to new code
            }
            
            $student->save();
            $transferredCount++;
            $updatedStudents[] = $student->id;
        }

        // Update student_payments with new student codes if campus changed
        if (!empty($newStudentCodes)) {
            // When student codes change (campus changed), update payments conditionally based on move_dues and move_payments
            foreach ($newStudentCodes as $oldCode => $newCode) {
                // Always update student_code (required for data integrity)
                // But conditionally update campus based on move_dues and move_payments
                
                // Update student_code for all records
                DB::table('student_payments')
                    ->where('student_code', $oldCode)
                    ->update(['student_code' => $newCode]);
                
                // Update campus for dues (Generated fees) only if move_dues is true
                if ($moveDues) {
                    DB::table('student_payments')
                        ->where('student_code', $newCode)
                        ->where('method', 'Generated')
                        ->update(['campus' => $normalizedToCampus]);
                }
                
                // Update campus for payments (non-Generated) only if move_payments is true
                if ($movePayments) {
                    DB::table('student_payments')
                        ->where('student_code', $newCode)
                        ->where('method', '!=', 'Generated')
                        ->update(['campus' => $normalizedToCampus]);
                }
            }
        } else {
            // If no code change (same campus transfer), just update campus in payments if requested
            $studentCodes = $students->pluck('student_code')->filter()->toArray();
            
            // Move dues if requested
            if ($moveDues && !empty($studentCodes)) {
                // Update payment records (dues are unpaid fees, which are in student_payments with method='Generated')
                DB::table('student_payments')
                    ->whereIn('student_code', $studentCodes)
                    ->whereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [strtolower(trim($fromCampus))])
                    ->where('method', 'Generated')
                    ->update(['campus' => $normalizedToCampus]);
            }

            // Move payments if requested
            if ($movePayments && !empty($studentCodes)) {
                // Update payment records (actual payments)
                DB::table('student_payments')
                    ->whereIn('student_code', $studentCodes)
                    ->whereRaw('LOWER(TRIM(COALESCE(campus, ""))) = ?', [strtolower(trim($fromCampus))])
                    ->where('method', '!=', 'Generated')
                    ->update(['campus' => $normalizedToCampus]);
            }
        }

        // Notify admin if requested
        if ($notifyAdmin) {
            // Log the transfer action for admin notification
            Log::info('Student Transfer Completed', [
                'from_campus' => $fromCampus,
                'to_campus' => $normalizedToCampus,
                'transferred_count' => $transferredCount,
                'move_dues' => $moveDues,
                'move_payments' => $movePayments,
                'initiated_by' => Auth::guard('admin')->check() ? Auth::guard('admin')->user()->name : (Auth::guard('accountant')->check() ? Auth::guard('accountant')->user()->name : 'System'),
            ]);
        }

        return response()->json([
            'success' => true,
            'message' => "Successfully transferred {$transferredCount} student(s) from '{$fromCampus}' to '{$normalizedToCampus}'.",
            'count' => $transferredCount,
        ]);
    }

    /**
     * Get classes by campus (AJAX endpoint)
     */
    public function getClassesByCampus(Request $request): JsonResponse
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
     * Get sections by class (AJAX endpoint)
     */
    public function getSectionsByClass(Request $request): JsonResponse
    {
        $class = $request->get('class');
        $campus = $request->get('campus');

        if (!$class) {
            return response()->json(['sections' => []]);
        }

        $sectionsQuery = Section::whereNotNull('name')
            ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
        
        if ($campus) {
            $sectionsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
        }
        
        $sections = $sectionsQuery->distinct()->pluck('name')->sort()->values();

        if ($sections->isEmpty()) {
            $subjectsQuery = Subject::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))])
                ->whereNotNull('section');
            if ($campus) {
                $subjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $sections = $subjectsQuery->distinct()->pluck('section')->sort()->values();
        }

        return response()->json(['sections' => $sections]);
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

    /**
     * Resolve campus code prefix (e.g., ST1, ST2) from campus name
     */
    private function resolveCampusCodePrefix(?string $campus): string
    {
        $campus = trim((string) $campus);
        if ($campus !== '') {
            $campusRecord = Campus::whereRaw('LOWER(TRIM(campus_name)) = ?', [strtolower($campus)])->first();
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
            $usedCampusNumbers = [];
            
            // Check from existing campuses with code_prefix
            $campusesWithPrefix = Campus::whereNotNull('code_prefix')
                ->where('code_prefix', 'like', 'ST%')
                ->get();
            
            foreach ($campusesWithPrefix as $campusWithPrefix) {
                if (preg_match('/^ST(\d+)$/i', $campusWithPrefix->code_prefix, $matches)) {
                    $campusNum = (int) $matches[1];
                    $usedCampusNumbers[] = $campusNum;
                }
            }
            
            // Also check from student codes to find any used campus numbers
            $studentCodes = Student::whereNotNull('student_code')
                ->where('student_code', 'like', 'ST%-%')
                ->pluck('student_code')
                ->toArray();
            
            // Also check from payment codes
            $paymentCodes = StudentPayment::whereNotNull('student_code')
                ->where('student_code', 'like', 'ST%-%')
                ->pluck('student_code')
                ->toArray();
            
            $allCodes = array_unique(array_merge($studentCodes, $paymentCodes));
            
            foreach ($allCodes as $code) {
                if (preg_match('/^ST(\d+)-(\d+)$/i', $code, $matches)) {
                    $campusNum = (int) $matches[1];
                    if (!in_array($campusNum, $usedCampusNumbers)) {
                        $usedCampusNumbers[] = $campusNum;
                    }
                }
            }
            
            // Find next sequential campus number
            $nextCampusNumber = 1;
            if (!empty($usedCampusNumbers)) {
                $maxCampusNumber = max($usedCampusNumbers);
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

    /**
     * Resolve campus name from Campus table to ensure consistency
     */
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

