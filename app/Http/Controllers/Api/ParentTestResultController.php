<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentMark;
use App\Models\Test;
use App\Models\Exam;
use App\Models\ParentAccount;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParentTestResultController extends Controller
{
    /**
     * Get Test Results for Parent's Student
     * Returns: subject_name (test_name), session, subject, total, pass, obtained
     * Only returns results where test_type is NOT "Exam"
     * 
     * GET /api/parent/test-results?student_id=3
     * Optional: test_name, subject, session
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getTestResults(Request $request): JsonResponse
    {
        return $this->getResults($request, 'Test results', false);
    }

    /**
     * Get Exam Results for Parent's Student
     * Returns: subject_name (test_name), session, subject, total, pass, obtained
     * Only returns results where test_type IS "Exam"
     * 
     * GET /api/parent/exam-results?student_id=3
     * Optional: test_name, subject, session
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getExamResults(Request $request): JsonResponse
    {
        return $this->getResults($request, 'Exam results', true);
    }

    /**
     * Common method to get test/exam results
     * 
     * @param Request $request
     * @param string $messageType
     * @param bool $isExam If true, only return exam results. If false, only return test results (not exams)
     * @return JsonResponse
     */
    private function getResults(Request $request, string $messageType = 'Test results', bool $isExam = false): JsonResponse
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

            // Validate required student_id parameter
            if (!$request->filled('student_id')) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student ID is required',
                    'token' => null,
                ], 400);
            }

            $studentId = (int) $request->student_id;

            // Verify student belongs to this parent
            $student = $parent->students()->find($studentId);
            
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found or does not belong to this parent',
                    'token' => null,
                ], 404);
            }
         
            // Get student details for matching tests
            $studentClass = trim($student->class ?? '');
            $studentSection = trim($student->section ?? '');
            $studentCampus = trim($student->campus ?? '');

            // Normalize class for flexible matching
            $normalizedStudentClass = str_replace(' ', '', strtolower($studentClass));
            $studentClassLower = strtolower($studentClass);
            $classWords = array_filter(explode(' ', $studentClassLower), function($word) {
                return strlen(trim($word)) > 0;
            });
            $primaryClassWord = !empty($classWords) ? trim(reset($classWords)) : $studentClassLower;

            // FIRST: Get all marks from StudentMark table for this student (primary source)
            // This ensures marks entered via Marks Entry are always shown
            // Don't filter by class/section here - get ALL marks for the student
            // We'll filter later if needed, but marks should show regardless of class/section match
            $marksQuery = StudentMark::where('student_id', $studentId);

            // Filter by test_name if provided
            if ($request->filled('test_name')) {
                $marksQuery->whereRaw('LOWER(TRIM(test_name)) = ?', [strtolower(trim($request->test_name))]);
            }

            // Filter by subject if provided
            if ($request->filled('subject')) {
                $marksQuery->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower(trim($request->subject))]);
            }

            // Get all marks for this student (without class/section filter)
            // This ensures marks entered via API or web are always shown
            $marks = $marksQuery->orderBy('test_name', 'asc')
                ->orderBy('subject', 'asc')
                ->get();

            // Debug: Log marks count for troubleshooting
            \Log::info('Parent Test Results - Marks found from StudentMark table', [
                'student_id' => $studentId,
                'count' => $marks->count(),
                'request_filters' => [
                    'test_name' => $request->test_name,
                    'subject' => $request->subject,
                ],
                'marks' => $marks->map(function($m) {
                    return [
                        'id' => $m->id,
                        'test_name' => $m->test_name,
                        'subject' => $m->subject,
                        'campus' => $m->campus,
                        'class' => $m->class,
                        'section' => $m->section,
                        'marks_obtained' => $m->marks_obtained,
                        'total_marks' => $m->total_marks,
                        'passing_marks' => $m->passing_marks,
                    ];
                })->toArray()
            ]);

            // Get unique test names from marks (CRITICAL: Include marks test names for Exam table check)
            $testNamesFromMarks = $marks->pluck('test_name')->unique()->filter()->values();

            // SECOND: Get all announced tests from Test table that match student's class/section
            // This ensures tests announced but not yet having marks are also shown
            $testsQuery = Test::query();
            
            // Match by class (flexible matching like timetable)
            if ($studentClass) {
                $testsQuery->where(function($q) use ($studentClassLower, $normalizedStudentClass, $primaryClassWord) {
                    $q->whereRaw('LOWER(TRIM(for_class)) = ?', [$studentClassLower])
                      ->orWhereRaw('LOWER(REPLACE(for_class, " ", "")) = ?', [$normalizedStudentClass])
                      ->orWhereRaw('LOWER(TRIM(for_class)) = ?', [$primaryClassWord])
                      ->orWhereRaw('LOWER(REPLACE(for_class, " ", "")) LIKE ?', ['%' . $primaryClassWord . '%'])
                      ->orWhereRaw('LOWER(TRIM(for_class)) LIKE ?', ['%' . $primaryClassWord . '%']);
                });
            }

            // Match by section if available
            if ($studentSection) {
                $testsQuery->where(function($q) use ($studentSection) {
                    $q->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($studentSection)])
                      ->orWhereNull('section')
                      ->orWhere('section', '');
                });
            }

            // Match by campus if available (optional - don't restrict too much)
            if ($studentCampus) {
                $testsQuery->where(function($q) use ($studentCampus) {
                    $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($studentCampus)])
                      ->orWhereNull('campus')
                      ->orWhere('campus', '');
                });
            }

            // Filter by test_name if provided
            if ($request->filled('test_name')) {
                $testsQuery->whereRaw('LOWER(TRIM(test_name)) = ?', [strtolower(trim($request->test_name))]);
            }

            // Filter by subject if provided
            if ($request->filled('subject')) {
                $testsQuery->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower(trim($request->subject))]);
            }

            // Get all matching tests
            $announcedTests = $testsQuery->get();

            // Debug: Log all tests in database for this class (for troubleshooting)
            $allTestsForClass = Test::whereRaw('LOWER(TRIM(for_class)) LIKE ?', ['%' . $studentClassLower . '%'])
                ->orWhereRaw('LOWER(REPLACE(for_class, " ", "")) LIKE ?', ['%' . $normalizedStudentClass . '%'])
                ->get();
            
            \Log::info('Parent Test Results - All tests in DB for similar class name', [
                'student_class' => $studentClass,
                'count' => $allTestsForClass->count(),
                'all_tests' => $allTestsForClass->map(function($t) {
                    return [
                        'id' => $t->id,
                        'test_name' => $t->test_name,
                        'for_class' => $t->for_class,
                        'section' => $t->section,
                        'campus' => $t->campus,
                        'subject' => $t->subject,
                        'test_type' => $t->test_type,
                    ];
                })->toArray()
            ]);

            // Debug: Log announced tests found
            \Log::info('Parent Test Results - Announced tests found (strict matching)', [
                'student_id' => $studentId,
                'student_class' => $studentClass,
                'student_section' => $studentSection,
                'student_campus' => $studentCampus,
                'count' => $announcedTests->count(),
                'tests' => $announcedTests->map(function($t) {
                    return [
                        'id' => $t->id,
                        'test_name' => $t->test_name,
                        'for_class' => $t->for_class,
                        'section' => $t->section,
                        'campus' => $t->campus,
                        'subject' => $t->subject,
                        'test_type' => $t->test_type,
                    ];
                })->toArray()
            ]);

            // FALLBACK: If no tests found with strict matching, try more relaxed matching
            // This helps catch tests that might have slight variations in class/section names
            if ($announcedTests->isEmpty() && $studentClass) {
                $fallbackQuery = Test::query();
                
                // More relaxed class matching - just check if class name contains student's class or vice versa
                $fallbackQuery->where(function($q) use ($studentClassLower, $normalizedStudentClass, $primaryClassWord) {
                    $q->whereRaw('LOWER(TRIM(for_class)) LIKE ?', ['%' . $studentClassLower . '%'])
                      ->orWhereRaw('LOWER(REPLACE(for_class, " ", "")) LIKE ?', ['%' . $normalizedStudentClass . '%'])
                      ->orWhereRaw('LOWER(TRIM(for_class)) LIKE ?', ['%' . $primaryClassWord . '%'])
                      ->orWhereRaw('? LIKE CONCAT("%", LOWER(TRIM(for_class)), "%")', [$studentClassLower])
                      ->orWhereRaw('? LIKE CONCAT("%", LOWER(REPLACE(for_class, " ", "")), "%")', [$normalizedStudentClass]);
                });

                // Section: match if same OR test has no section OR student section matches test section
                if ($studentSection) {
                    $fallbackQuery->where(function($q) use ($studentSection) {
                        $q->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($studentSection)])
                          ->orWhereNull('section')
                          ->orWhere('section', '')
                          ->orWhereRaw('LOWER(TRIM(section)) LIKE ?', ['%' . strtolower($studentSection) . '%']);
                    });
                }

                // Campus: match if same OR test has no campus OR student campus matches test campus
                if ($studentCampus) {
                    $fallbackQuery->where(function($q) use ($studentCampus) {
                        $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($studentCampus)])
                          ->orWhereNull('campus')
                          ->orWhere('campus', '')
                          ->orWhereRaw('LOWER(TRIM(campus)) LIKE ?', ['%' . strtolower($studentCampus) . '%']);
                    });
                }

                // Apply same filters as main query
                if ($request->filled('test_name')) {
                    $fallbackQuery->whereRaw('LOWER(TRIM(test_name)) = ?', [strtolower(trim($request->test_name))]);
                }
                if ($request->filled('subject')) {
                    $fallbackQuery->whereRaw('LOWER(TRIM(subject)) = ?', [strtolower(trim($request->subject))]);
                }

                $fallbackTests = $fallbackQuery->get();
                
                if ($fallbackTests->isNotEmpty()) {
                    \Log::info('Parent Test Results - Fallback query found tests', [
                        'count' => $fallbackTests->count(),
                        'tests' => $fallbackTests->map(function($t) {
                            return [
                                'id' => $t->id,
                                'test_name' => $t->test_name,
                                'for_class' => $t->for_class,
                                'section' => $t->section,
                                'campus' => $t->campus,
                                'subject' => $t->subject,
                                'test_type' => $t->test_type,
                            ];
                        })->toArray()
                    ]);
                    
                    // Merge fallback tests with announced tests
                    $announcedTests = $announcedTests->merge($fallbackTests)->unique('id')->values();
                }
            }

            // Combine test names from both sources (marks + announced tests)
            $testNames = $testNamesFromMarks->merge($announcedTests->pluck('test_name'))->unique()->filter()->values();

            // Build maps for sessions and test_type from announced tests
            $testSessions = [];
            $testTypes = [];
            $testSubjects = []; // Map test_name to subjects from Test table
            
            // Process announced tests from Test table
            foreach ($announcedTests as $test) {
                $key = strtolower(trim($test->test_name ?? ''));
                if ($key) {
                    $testSessions[$key] = $test->session;
                    $testTypes[$key] = $test->test_type;
                    // Store subject for this test
                    if ($test->subject) {
                        if (!isset($testSubjects[$key])) {
                            $testSubjects[$key] = [];
                        }
                        $testSubjects[$key][] = $test->subject;
                    }
                }
            }

            // Check Exam table - if test_name exists in exams table, it's an exam
            // IMPORTANT: Only check Exam table if we're looking for exam results
            // For test results, we should NOT include anything from Exam table
            if ($isExam && $testNames->isNotEmpty()) {
                $examsQuery = Exam::query();
                $examsQuery->where(function($q) use ($testNames) {
                    foreach ($testNames as $testName) {
                        $q->orWhereRaw('LOWER(TRIM(exam_name)) = ?', [strtolower(trim($testName))]);
                    }
                });
                
                $exams = $examsQuery->select('exam_name', 'session')->get();
                
                // Mark exams as "Exam" type and get their sessions
                foreach ($exams as $exam) {
                    $key = strtolower(trim($exam->exam_name));
                    // If exam exists in Exam table, it's definitely an exam
                    $testTypes[$key] = 'Exam';
                    // Set session from Exam table if not already set from Test table
                    if (!isset($testSessions[$key])) {
                        $testSessions[$key] = $exam->session;
                    }
                }
            } elseif (!$isExam && $testNames->isNotEmpty()) {
                // For test results: Check Exam table to EXCLUDE exams
                // If a test_name exists in Exam table, mark it as "Exam" so we can filter it out
                $examsQuery = Exam::query();
                $examsQuery->where(function($q) use ($testNames) {
                    foreach ($testNames as $testName) {
                        $q->orWhereRaw('LOWER(TRIM(exam_name)) = ?', [strtolower(trim($testName))]);
                    }
                });
                
                $exams = $examsQuery->select('exam_name', 'session')->get();
                
                // Mark exams as "Exam" type so they can be filtered out from test results
                foreach ($exams as $exam) {
                    $key = strtolower(trim($exam->exam_name));
                    $testTypes[$key] = 'Exam';
                }
            }

            // Also map original test names to sessions and types (from both sources)
            foreach ($testNames as $testName) {
                $key = strtolower(trim($testName));
                if (!isset($testSessions[$testName]) && isset($testSessions[$key])) {
                    $testSessions[$testName] = $testSessions[$key];
                }
                if (!isset($testTypes[$testName]) && isset($testTypes[$key])) {
                    $testTypes[$testName] = $testTypes[$key];
                }
            }

            // Debug: Log test types before filtering
            \Log::info('Parent Test Results - Test types mapping', [
                'test_types' => $testTypes,
                'is_exam' => $isExam,
            ]);

            // Filter announced tests by test type (Exam vs Test)
            if ($isExam) {
                // Only include tests where test_type is "Exam"
                $announcedTests = $announcedTests->filter(function ($test) use ($testTypes) {
                    $testNameKey = strtolower(trim($test->test_name ?? ''));
                    $testType = $testTypes[$testNameKey] ?? null;
                    return $testType !== null && strtolower(trim($testType)) === 'exam';
                })->values();
            } else {
                // Only include tests where test_type is NOT "Exam"
                $announcedTestsBeforeFilter = $announcedTests->count();
                $announcedTests = $announcedTests->filter(function ($test) use ($testTypes) {
                    $testNameKey = strtolower(trim($test->test_name ?? ''));
                    $testType = $testTypes[$testNameKey] ?? null;
                    // Include if test_type is null (not in Test/Exam tables) or NOT "Exam"
                    if ($testType === null) {
                        return true;
                    }
                    $isExamType = strtolower(trim($testType)) === 'exam';
                    
                    // Debug: Log filtered tests
                    if ($isExamType) {
                        \Log::info('Filtering out test (marked as Exam)', [
                            'test_name' => $test->test_name,
                            'test_type' => $testType,
                        ]);
                    }
                    
                    return !$isExamType;
                })->values();
                
                // Debug: Log filtering results
                \Log::info('Parent Test Results - After test_type filtering', [
                    'before_count' => $announcedTestsBeforeFilter,
                    'after_count' => $announcedTests->count(),
                    'filtered_tests' => $announcedTests->map(function($t) use ($testTypes) {
                        return [
                            'test_name' => $t->test_name,
                            'test_type' => $testTypes[strtolower(trim($t->test_name ?? ''))] ?? 'null',
                        ];
                    })->toArray()
                ]);
            }

            // Filter by session if provided
            if ($request->filled('session')) {
                $sessionFilter = $request->session;
                $announcedTests = $announcedTests->filter(function ($test) use ($testSessions, $sessionFilter) {
                    $testNameKey = strtolower(trim($test->test_name ?? ''));
                    $session = $testSessions[$testNameKey] ?? null;
                    return $session === $sessionFilter;
                })->values();
            }

            // IMPORTANT: Re-combine test names after filtering announced tests
            // This ensures marks test names are still included
            $testNames = $testNamesFromMarks->merge($announcedTests->pluck('test_name'))->unique()->filter()->values();

            // Build results: PRIORITIZE marks from StudentMark table
            // This ensures marks entered via Marks Entry are ALWAYS shown
            $results = collect();
            
            // FIRST: Process ALL marks from StudentMark table (primary source)
            // This is the most important - marks entered via Marks Entry must always show
            foreach ($marks as $mark) {
                $testNameKey = strtolower(trim($mark->test_name ?? ''));
                $subjectKey = strtolower(trim($mark->subject ?? ''));
                
                if ($testNameKey) { // Only process if test_name exists
                    $session = $testSessions[$testNameKey] ?? null;
                    $testType = $testTypes[$testNameKey] ?? null;
                    
                    // Debug: Log each mark being added
                    \Log::info('Adding mark to results', [
                        'test_name' => $mark->test_name,
                        'test_name_key' => $testNameKey,
                        'subject' => $mark->subject,
                        'test_type' => $testType,
                        'marks_obtained' => $mark->marks_obtained,
                    ]);
                    
                    $results->push([
                        'subject_name' => $mark->test_name ?? null,
                        'session' => $session,
                        'subject' => $mark->subject ?? null,
                        'test_type' => $testType,
                        'total' => $mark->total_marks ? (float) $mark->total_marks : null,
                        'pass' => $mark->passing_marks ? (float) $mark->passing_marks : null,
                        'obtained' => $mark->marks_obtained ? (float) $mark->marks_obtained : null,
                    ]);
                }
            }
            
            // Debug: Log results count before filtering
            \Log::info('Results before filtering', [
                'count' => $results->count(),
                'is_exam' => $isExam,
            ]);

            // SECOND: Add announced tests that don't have marks yet
            // Group announced tests by test_name and subject
            $testGroups = [];
            foreach ($announcedTests as $test) {
                $testNameKey = strtolower(trim($test->test_name ?? ''));
                $subjectKey = strtolower(trim($test->subject ?? ''));
                
                if ($testNameKey) {
                    if (!isset($testGroups[$testNameKey])) {
                        $testGroups[$testNameKey] = [];
                    }
                    if ($subjectKey && !in_array($subjectKey, $testGroups[$testNameKey])) {
                        $testGroups[$testNameKey][] = $subjectKey;
                    } elseif (!$subjectKey) {
                        $testGroups[$testNameKey][] = '';
                    }
                }
            }

            // Add announced tests that don't have marks
            foreach ($testGroups as $testNameKey => $subjects) {
                $testRecord = $announcedTests->first(function($t) use ($testNameKey) {
                    return strtolower(trim($t->test_name ?? '')) === $testNameKey;
                });
                $testName = $testRecord ? $testRecord->test_name : null;

                foreach ($subjects as $subjectKey) {
                    // Check if this test+subject already exists in results (has marks)
                    $exists = $results->first(function($result) use ($testNameKey, $subjectKey) {
                        $resultTestNameKey = strtolower(trim($result['subject_name'] ?? ''));
                        $resultSubjectKey = strtolower(trim($result['subject'] ?? ''));
                        $normalizedSubjectKey = $subjectKey ?: '';
                        $normalizedResultSubjectKey = $resultSubjectKey ?: '';
                        return $resultTestNameKey === $testNameKey && $normalizedResultSubjectKey === $normalizedSubjectKey;
                    });
                    
                    // Only add if it doesn't exist (no marks for this test+subject)
                    if (!$exists) {
                        $testRecord = $announcedTests->first(function($t) use ($testNameKey, $subjectKey) {
                            return strtolower(trim($t->test_name ?? '')) === $testNameKey 
                                && strtolower(trim($t->subject ?? '')) === ($subjectKey ?: '');
                        });
                        $subject = $testRecord ? $testRecord->subject : null;
                        $session = $testSessions[$testNameKey] ?? null;
                        $testType = $testTypes[$testNameKey] ?? null;
                        
                        $results->push([
                            'subject_name' => $testName,
                            'session' => $session,
                            'subject' => $subject,
                            'test_type' => $testType,
                            'total' => null,
                            'pass' => null,
                            'obtained' => null,
                        ]);
                    }
                }
            }

            // Filter results by test type (if needed - already filtered announced tests, but also filter marks-only results)
            if ($isExam) {
                $results = $results->filter(function ($result) use ($testTypes) {
                    $testNameKey = strtolower(trim($result['subject_name'] ?? ''));
                    $testType = $testTypes[$testNameKey] ?? null;
                    $include = $testType !== null && strtolower(trim($testType)) === 'exam';
                    
                    // Debug
                    if (!$include) {
                        \Log::info('Filtering out from exam results', [
                            'test_name' => $result['subject_name'],
                            'test_type' => $testType,
                        ]);
                    }
                    
                    return $include;
                })->values();
            } else {
                // IMPORTANT: For test results, marks from StudentMark should ALWAYS be shown
                // regardless of Exam table. Only filter announced tests, not actual marks.
                $results = $results->filter(function ($result) use ($testTypes) {
                    $testNameKey = strtolower(trim($result['subject_name'] ?? ''));
                    $testType = $testTypes[$testNameKey] ?? null;
                    
                    // Check if this result has actual marks data (from StudentMark table)
                    // If it has obtained, total, or pass marks, it's from Marks Entry and should ALWAYS be shown
                    $hasActualMarks = !is_null($result['obtained']) || !is_null($result['total']) || !is_null($result['pass']);
                    
                    // If this result has actual marks from StudentMark, ALWAYS include it
                    // (marks entered via Marks Entry should never be filtered out, even if test is in Exam table)
                    if ($hasActualMarks) {
                        \Log::info('Including mark from StudentMark (always show marks)', [
                            'test_name' => $result['subject_name'],
                            'subject' => $result['subject'],
                            'test_type' => $testType,
                            'obtained' => $result['obtained'],
                            'total' => $result['total'],
                        ]);
                        return true;
                    }
                    
                    // For announced tests without marks, filter by test_type
                    // Include if test_type is null or NOT "Exam"
                    $include = false;
                    if ($testType === null) {
                        $include = true; // Include tests without test_type
                    } else {
                        $include = strtolower(trim($testType)) !== 'exam';
                    }
                    
                    // Debug
                    if (!$include) {
                        \Log::info('Filtering out announced test from test results', [
                            'test_name' => $result['subject_name'],
                            'test_type' => $testType,
                            'reason' => 'is exam',
                        ]);
                    }
                    
                    return $include;
                })->values();
            }
            
            // Debug: Log results count after filtering
            \Log::info('Results after filtering', [
                'count' => $results->count(),
                'is_exam' => $isExam,
            ]);

            // Sort results
            $results = $results->sortBy(function($result) {
                return ($result['subject_name'] ?? '') . '|' . ($result['subject'] ?? '');
            })->values();

            return response()->json([
                'success' => true,
                'message' => $messageType . ' retrieved successfully',
                'data' => [
                    'student' => [
                        'id' => $student->id,
                        'student_name' => $student->student_name,
                        'student_code' => $student->student_code,
                        'class' => $student->class,
                        'section' => $student->section,
                    ],
                    'results' => $results,
                    'total_records' => $results->count(),
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving results: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }
}