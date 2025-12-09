<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use App\Models\Subject;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TeacherStudentController extends Controller
{
    /**
     * Get Students List API
     * Filter by Campus, Class, Section (from URL query parameters or request body)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();
            $query = Student::query();
            
            // Get class and section from URL query parameters or request body
            $className = $request->query('class') ?? $request->input('class');
            $sectionName = $request->query('section') ?? $request->input('section');
            
            // Filter by teacher's assigned classes if teacher
            if ($teacher && strtolower(trim($teacher->designation ?? '')) === 'teacher') {
                // Get teacher's assigned subjects
                $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                    ->get();
                
                // Get teacher's assigned sections
                $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                    ->get();
                
                // Get unique classes from both sources
                $assignedClasses = $assignedSubjects->pluck('class')
                    ->merge($assignedSections->pluck('class'))
                    ->map(function($class) {
                        return trim($class);
                    })
                    ->filter(function($class) {
                        return !empty($class);
                    })
                    ->unique()
                    ->values();
                
                // Filter by teacher's campus
                if ($teacher->campus) {
                    $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($teacher->campus))]);
                }
                
                // If class is provided in URL/request, verify it's assigned to teacher
                if ($className) {
                    $className = trim($className);
                    $isClassAssigned = $assignedClasses->contains(function($class) use ($className) {
                        return strtolower(trim($class)) === strtolower($className);
                    });
                    
                    if (!$isClassAssigned && $assignedClasses->isNotEmpty()) {
                        return response()->json([
                            'success' => false,
                            'message' => 'You are not assigned to this class.',
                            'token' => null,
                        ], 403);
                    }
                }
                
                // Filter by assigned classes ONLY (if class not specified in URL)
                if (!$className && $assignedClasses->isNotEmpty()) {
                    $query->where(function($q) use ($assignedClasses) {
                        foreach ($assignedClasses as $class) {
                            $q->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($class))]);
                        }
                    });
                } else if (!$className && $assignedClasses->isEmpty()) {
                    // If no classes assigned, return empty result
                    $query->whereRaw('1 = 0');
                }
            }
            
            // Filter by Campus (case-insensitive) - from URL or body (only if not already filtered by teacher)
            $campus = $request->query('campus') ?? $request->input('campus');
            if ($campus && (!$teacher || strtolower(trim($teacher->designation ?? '')) !== 'teacher')) {
                $campus = trim($campus);
                $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)]);
            }
            
            // Filter by Class (case-insensitive, exact match) - from URL query params or body
            if ($className) {
                $className = trim($className);
                $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($className)]);
            }
            
            // Filter by Section (case-insensitive) - from URL query params or body
            if ($sectionName) {
                $sectionName = trim($sectionName);
                $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($sectionName)]);
            }
            
            // Search functionality - from body
            if ($request->filled('search')) {
                $search = trim($request->search);
                if (!empty($search)) {
                    $searchLower = strtolower($search);
                    $query->where(function($q) use ($search, $searchLower) {
                        $q->whereRaw('LOWER(student_name) LIKE ?', ["%{$searchLower}%"])
                          ->orWhere('student_code', 'like', "%{$search}%")
                          ->orWhereRaw('LOWER(class) LIKE ?', ["%{$searchLower}%"])
                          ->orWhereRaw('LOWER(section) LIKE ?', ["%{$searchLower}%"]);
                    });
                }
            }
            
            // Pagination
            $perPage = $request->get('per_page', 10);
            $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
            
            $students = $query->latest('admission_date')->paginate($perPage);
            
            // Format students data
            $studentsData = $students->map(function($student) {
                return [
                    'id' => $student->id,
                    'student_code' => $student->student_code,
                    'student_name' => $student->student_name,
                    'class' => $student->class,
                    'section' => $student->section,
                    'campus' => $student->campus,
                    'gender' => $student->gender,
                    'admission_date' => $student->admission_date?->format('Y-m-d'),
                    'photo' => $student->photo ? asset('storage/' . $student->photo) : null,
                ];
            });
            
            $message = 'Students list retrieved successfully';
            if ($className) {
                $message .= ' for class: ' . $className;
                if ($sectionName) {
                    $message .= ', section: ' . $sectionName;
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => $message,
                'data' => [
                    'filters' => [
                        'class' => $className ?? null,
                        'section' => $sectionName ?? null,
                    ],
                    'students' => $studentsData,
                    'pagination' => [
                        'current_page' => $students->currentPage(),
                        'last_page' => $students->lastPage(),
                        'per_page' => $students->perPage(),
                        'total' => $students->total(),
                        'from' => $students->firstItem(),
                        'to' => $students->lastItem(),
                    ],
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving students list',
                'token' => null,
            ], 200);
        }
    }

    /**
     * Get Filter Options (Campuses, Classes, Sections)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getFilterOptions(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();
            
            // Get Campuses - filter by teacher's campus if teacher
            $campuses = collect();
            if ($teacher && strtolower(trim($teacher->designation ?? '')) === 'teacher' && $teacher->campus) {
                $campuses = collect([$teacher->campus]);
            } else {
                $campuses = Campus::orderBy('campus_name', 'asc')->pluck('campus_name');
                if ($campuses->isEmpty()) {
                    $campuses = Student::whereNotNull('campus')
                        ->distinct()
                        ->pluck('campus')
                        ->sort()
                        ->values();
                }
            }
            
            // Get Classes - filter by teacher's assigned classes if teacher
            $classes = collect();
            if ($teacher && strtolower(trim($teacher->designation ?? '')) === 'teacher') {
                // Get classes from teacher's assigned subjects
                $teacherName = strtolower(trim($teacher->name ?? ''));
                $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                    ->get();
                
                // Get classes from teacher's assigned sections
                $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [$teacherName])
                    ->get();
                
                // Merge classes from both sources
                $classes = $assignedSubjects->pluck('class')
                    ->merge($assignedSections->pluck('class'))
                    ->map(function($class) {
                        return trim($class); // Trim whitespace
                    })
                    ->filter(function($class) {
                        return !empty($class); // Remove empty values
                    })
                    ->unique()
                    ->sort()
                    ->values();
                
                // If no classes found from subjects/sections, also check from ClassModel for teacher's campus
                if ($classes->isEmpty() && $teacher->campus) {
                    $classesFromModel = ClassModel::whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($teacher->campus))])
                        ->whereNotNull('class_name')
                        ->distinct()
                        ->pluck('class_name')
                        ->map(function($class) {
                            return trim($class);
                        })
                        ->filter()
                        ->sort()
                        ->values();
                    
                    if ($classesFromModel->isNotEmpty()) {
                        $classes = $classesFromModel;
                    }
                }
            } else {
                $classes = ClassModel::whereNotNull('class_name')
                    ->distinct()
                    ->pluck('class_name')
                    ->map(function($class) {
                        return trim($class);
                    })
                    ->filter()
                    ->sort()
                    ->values();
                if ($classes->isEmpty()) {
                    $classes = Student::whereNotNull('class')
                        ->distinct()
                        ->pluck('class')
                        ->map(function($class) {
                            return trim($class);
                        })
                        ->filter()
                        ->sort()
                        ->values();
                }
            }
            
            // Get Sections (filtered by class if provided) - filter by teacher's assigned subjects if teacher
            $sections = collect();
            if ($request->filled('class')) {
                if ($teacher && strtolower(trim($teacher->designation ?? '')) === 'teacher') {
                    // Get sections from teacher's assigned subjects for this class
                    $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                        ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($request->class))])
                        ->get();
                    
                    // Get sections from teacher's assigned sections for this class
                    $assignedSections = Section::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower(trim($teacher->name ?? ''))])
                        ->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($request->class))])
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
                    $sections = Section::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($request->class))])
                        ->whereNotNull('name')
                        ->distinct()
                        ->pluck('name')
                        ->sort()
                        ->values();
                    if ($sections->isEmpty()) {
                        $sections = Student::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($request->class))])
                            ->whereNotNull('section')
                            ->distinct()
                            ->pluck('section')
                            ->sort()
                            ->values();
                    }
                }
            }
            
            return response()->json([
                'success' => true,
                'message' => 'Filter options retrieved successfully',
                'data' => [
                    'campuses' => $campuses->values(),
                    'classes' => $classes->values(),
                    'sections' => $sections->values(),
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving filter options',
                'token' => null,
            ], 200);
        }
    }
}

