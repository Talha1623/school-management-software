<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Student;
use App\Models\Campus;
use App\Models\ClassModel;
use App\Models\Section;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TeacherStudentController extends Controller
{
    /**
     * Get Students List API
     * Filter by Campus, Class, Section (from request body)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $query = Student::query();
            
            // Filter by Campus (case-insensitive) - from body
            if ($request->filled('campus')) {
                $campus = trim($request->campus);
                $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($campus)]);
            }
            
            // Filter by Class (case-insensitive, exact match) - from body
            if ($request->filled('class')) {
                $class = trim($request->class);
                $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($class)]);
            }
            
            // Filter by Section (case-insensitive) - from body
            if ($request->filled('section')) {
                $section = trim($request->section);
                $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($section)]);
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
            
            return response()->json([
                'success' => true,
                'message' => 'Students list retrieved successfully',
                'data' => [
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
            // Get Campuses
            $campuses = Campus::orderBy('campus_name', 'asc')->pluck('campus_name');
            if ($campuses->isEmpty()) {
                $campuses = Student::whereNotNull('campus')
                    ->distinct()
                    ->pluck('campus')
                    ->sort()
                    ->values();
            }
            
            // Get Classes
            $classes = ClassModel::whereNotNull('class_name')
                ->distinct()
                ->pluck('class_name')
                ->sort()
                ->values();
            if ($classes->isEmpty()) {
                $classes = Student::whereNotNull('class')
                    ->distinct()
                    ->pluck('class')
                    ->sort()
                    ->values();
            }
            
            // Get Sections (filtered by class if provided)
            $sections = collect();
            if ($request->filled('class')) {
                $sections = Section::where('class', $request->class)
                    ->whereNotNull('name')
                    ->distinct()
                    ->pluck('name')
                    ->sort()
                    ->values();
                if ($sections->isEmpty()) {
                    $sections = Student::where('class', $request->class)
                        ->whereNotNull('section')
                        ->distinct()
                        ->pluck('section')
                        ->sort()
                        ->values();
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

