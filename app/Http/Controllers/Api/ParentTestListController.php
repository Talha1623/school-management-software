<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Test;
use App\Models\Student;
use App\Models\StudentMark;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class ParentTestListController extends Controller
{
    /**
     * Get Test List for Parent's Students
     * Returns all tests for parent's students
     * 
     * GET /api/parent/tests
     * Optional filters: student_id, date, date_from, date_to, type (upcoming/past), per_page
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getTests(Request $request): JsonResponse
    {
        try {
            $parent = $request->user();

            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'token' => null,
                ], 404);
            }

            // Get parent's students
            $students = $parent->students;

            if ($students->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No students found for this parent',
                    'token' => null,
                ], 404);
            }

            // Filter by specific student if provided
            $studentId = $request->filled('student_id') ? (int) $request->student_id : null;
            
            if ($studentId) {
                $student = $students->find($studentId);
                if (!$student) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Student not found or does not belong to this parent',
                        'token' => null,
                    ], 404);
                }
                $students = collect([$student]);
            }

            // Get student IDs
            $studentIds = $students->pluck('id')->toArray();

            // Get all unique classes from parent's students
            $studentClasses = $students->pluck('class')->filter()->unique()->map(function($class) {
                return trim($class);
            })->values();

            if ($studentClasses->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Students do not have class information',
                    'token' => null,
                ], 400);
            }

            // Get tests from Tests table
            $testsQuery = Test::where(function($q) use ($studentClasses) {
                foreach ($studentClasses as $class) {
                    $q->orWhereRaw('LOWER(TRIM(for_class)) = ?', [strtolower($class)]);
                }
            });

            // Filter by campus if all students have same campus
            $studentCampuses = $students->pluck('campus')->filter()->unique()->values();
            if ($studentCampuses->count() === 1 && $studentCampuses->first()) {
                $testsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($studentCampuses->first()))]);
            }

            // Get tests from Tests table
            $testsFromTable = $testsQuery->get();

            // Get tests from StudentMarks table (where marks have been entered)
            $marksQuery = StudentMark::whereIn('student_id', $studentIds);
            
            // Filter by campus if all students have same campus
            if ($studentCampuses->count() === 1 && $studentCampuses->first()) {
                $marksQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($studentCampuses->first()))]);
            }

            // Get unique tests from StudentMarks
            $marksTests = $marksQuery->select('test_name', 'campus', 'class', 'section', 'subject')
                ->distinct()
                ->get();

            // Merge tests from both sources
            $allTests = collect();
            
            // Add tests from Tests table
            foreach ($testsFromTable as $test) {
                $allTests->push([
                    'source' => 'test_table',
                    'id' => $test->id,
                    'test_name' => $test->test_name,
                    'subject' => $test->subject,
                    'test_type' => $test->test_type,
                    'description' => $test->description,
                    'date' => $test->date,
                    'session' => $test->session,
                    'campus' => $test->campus,
                    'class' => $test->for_class,
                    'section' => $test->section,
                    'result_status' => (bool) $test->result_status,
                ]);
            }

            // Add tests from StudentMarks (if not already in Tests table)
            foreach ($marksTests as $markTest) {
                // Check if this test already exists in Tests table
                $exists = $testsFromTable->first(function($test) use ($markTest) {
                    return strtolower(trim($test->test_name)) === strtolower(trim($markTest->test_name)) &&
                           strtolower(trim($test->for_class ?? '')) === strtolower(trim($markTest->class ?? ''));
                });

                if (!$exists) {
                    // Try to find test in Tests table by test_name to get date and session
                    $testFromTable = Test::whereRaw('LOWER(TRIM(test_name)) = ?', [strtolower(trim($markTest->test_name))])
                        ->whereRaw('LOWER(TRIM(for_class)) = ?', [strtolower(trim($markTest->class ?? ''))])
                        ->first();

                    $allTests->push([
                        'source' => 'student_marks',
                        'id' => $testFromTable ? $testFromTable->id : null,
                        'test_name' => $markTest->test_name,
                        'subject' => $markTest->subject,
                        'test_type' => $testFromTable ? $testFromTable->test_type : null,
                        'description' => $testFromTable ? $testFromTable->description : null,
                        'date' => $testFromTable ? $testFromTable->date : null,
                        'session' => $testFromTable ? $testFromTable->session : null,
                        'campus' => $markTest->campus,
                        'class' => $markTest->class,
                        'section' => $markTest->section,
                        'result_status' => $testFromTable ? (bool) $testFromTable->result_status : true, // If marks exist, result is declared
                    ]);
                }
            }

            // Remove duplicates based on test_name + class combination
            $uniqueTests = $allTests->unique(function ($test) {
                return strtolower(trim($test['test_name'] ?? '')) . '|' . strtolower(trim($test['class'] ?? ''));
            })->values();

            // Apply date filters
            if ($request->filled('date')) {
                try {
                    $testDate = \Carbon\Carbon::parse($request->date)->format('Y-m-d');
                    $uniqueTests = $uniqueTests->filter(function($test) use ($testDate) {
                        return $test['date'] && $test['date']->format('Y-m-d') === $testDate;
                    })->values();
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid date format. Please use Y-m-d format (e.g., 2024-12-20)',
                        'token' => null,
                    ], 400);
                }
            }

            if ($request->filled('date_from')) {
                $uniqueTests = $uniqueTests->filter(function($test) use ($request) {
                    return $test['date'] && $test['date']->format('Y-m-d') >= $request->date_from;
                })->values();
            }

            if ($request->filled('date_to')) {
                $uniqueTests = $uniqueTests->filter(function($test) use ($request) {
                    return $test['date'] && $test['date']->format('Y-m-d') <= $request->date_to;
                })->values();
            }

            // Filter by upcoming/past tests
            if ($request->filled('type')) {
                $today = now()->format('Y-m-d');
                if ($request->type === 'upcoming') {
                    $uniqueTests = $uniqueTests->filter(function($test) use ($today) {
                        return $test['date'] && $test['date']->format('Y-m-d') >= $today;
                    })->values();
                } elseif ($request->type === 'past') {
                    $uniqueTests = $uniqueTests->filter(function($test) use ($today) {
                        return $test['date'] && $test['date']->format('Y-m-d') < $today;
                    })->values();
                }
            }

            // Sort by date (desc) then test_name (asc)
            $uniqueTests = $uniqueTests->sortByDesc(function($test) {
                return $test['date'] ? $test['date']->format('Y-m-d') : '0000-00-00';
            })->sortBy(function($test) {
                return $test['test_name'] ?? '';
            })->values();

            // Pagination
            $perPage = $request->get('per_page', 30);
            $perPage = in_array((int)$perPage, [10, 25, 30, 50, 100], true) ? (int)$perPage : 30;
            
            $currentPage = $request->get('page', 1);
            $offset = ($currentPage - 1) * $perPage;
            $paginatedTests = $uniqueTests->slice($offset, $perPage);
            $total = $uniqueTests->count();
            $lastPage = ceil($total / $perPage);

            // Format test data
            $testsData = $paginatedTests->map(function ($test) {
                $testDate = $test['date'];
                $isUpcoming = $testDate && $testDate->format('Y-m-d') >= now()->format('Y-m-d');
                
                return [
                    'id' => $test['id'],
                    'test_name' => $test['test_name'],
                    'subject' => $test['subject'],
                    'test_type' => $test['test_type'],
                    'description' => $test['description'],
                    'date' => $testDate ? $testDate->format('Y-m-d') : null,
                    'date_formatted' => $testDate ? $testDate->format('d M Y') : null,
                    'session' => $test['session'],
                    'campus' => $test['campus'],
                    'class' => $test['class'],
                    'section' => $test['section'],
                    'is_upcoming' => $isUpcoming,
                    'result_status' => $test['result_status'],
                ];
            });

            // Format students data
            $studentsData = $students->map(function ($student) {
                return [
                    'id' => $student->id,
                    'student_name' => $student->student_name,
                    'student_code' => $student->student_code,
                    'class' => $student->class,
                    'section' => $student->section,
                    'campus' => $student->campus,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Tests retrieved successfully',
                'data' => [
                    'students' => $studentsData,
                    'tests' => $testsData,
                    'pagination' => [
                        'current_page' => (int) $currentPage,
                        'last_page' => $lastPage,
                        'per_page' => $perPage,
                        'total' => $total,
                        'from' => $total > 0 ? $offset + 1 : 0,
                        'to' => min($offset + $perPage, $total),
                    ],
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving tests: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }
}

