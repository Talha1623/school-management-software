<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BehaviorRecord;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class StudentBehaviorController extends Controller
{
    /**
     * Behavior summary for logged-in student (current year vs last year + type-wise)
     *
     * GET /api/student/behavior/summary
     */
    public function summary(Request $request): JsonResponse
    {
        try {
            $student = $request->user();

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'data' => null,
                ], 404);
            }

            // Get all behavior records for this student
            $records = BehaviorRecord::where('student_id', $student->id)
                ->orderBy('date', 'desc')
                ->get();

            $currentYear = Carbon::now()->year;
            $lastYear = $currentYear - 1;

            $currentYearRecords = $records->filter(function ($record) use ($currentYear) {
                return Carbon::parse($record->date)->year == $currentYear;
            });

            $lastYearRecords = $records->filter(function ($record) use ($lastYear) {
                return Carbon::parse($record->date)->year == $lastYear;
            });

            $currentYearPoints = $currentYearRecords->sum('points');
            $lastYearPoints = $lastYearRecords->sum('points');

            // Group by type (e.g. "daily behavior") for summary
            $behaviorSummary = $records->groupBy('type')->map(function ($group, $type) {
                return [
                    'type' => $type,
                    'points' => $group->sum('points'),
                    'count' => $group->count(),
                ];
            })->values();

            // Calculate percentage share per type (for pie chart %)
            $totalPoints = max($behaviorSummary->sum('points'), 1); // avoid division by zero

            $behaviorSummary = $behaviorSummary->map(function ($row) use ($totalPoints) {
                $row['percentage'] = round(($row['points'] / $totalPoints) * 100, 1);
                return $row;
            })->values();

            return response()->json([
                'success' => true,
                'message' => 'Behavior summary loaded successfully.',
                'data' => [
                    'student' => [
                        'id' => $student->id,
                        'name' => $student->student_name,
                        'student_code' => $student->student_code,
                        'class' => $student->class,
                        'section' => $student->section,
                        'campus' => $student->campus,
                    ],
                    'current_year_points' => $currentYearPoints,
                    'last_year_points' => $lastYearPoints,
                    'total_points' => $records->sum('points'),
                    'total_records' => $records->count(),
                    // Frontend/mobile can use this for % pie chart
                    'summary' => $behaviorSummary,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving behavior summary: ' . $e->getMessage(),
                'data' => null,
                'token' => null,
            ], 500);
        }
    }

    /**
     * Behavior records list for logged-in student
     *
     * Examples:
     * - GET /api/student/behavior/records            -> all records
     * - GET /api/student/behavior/records?student_id=7 -> all records for student_id 7
     * - GET /api/student/behavior/records?student_id=7&date=2025-12-15 -> records for student_id 7 on specific date
     * - GET /api/student/behavior/records?date=2025-12-15 -> records for authenticated student on specific date
     * - GET /api/student/behavior/records?date_from=2025-12-01&date_to=2025-12-31 -> date range
     * - GET /api/student/behavior/records?type=Positive -> filter by type
     */
    public function records(Request $request): JsonResponse
    {
        try {
            $student = $request->user();
            $requestedStudentId = $request->filled('student_id') ? (int) $request->student_id : null;

            // Web-like behavior: if no token, allow by student_id.
            if (!$student && !$requestedStudentId) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated. Please provide token or student_id.',
                    'data' => null,
                    'token' => null,
                ], 401);
            }

            if ($student) {
                // Authenticated student can only see own records
                if ($requestedStudentId && $requestedStudentId !== (int) $student->id) {
                    return response()->json([
                        'success' => false,
                        'message' => 'You are not allowed to view this student\'s behavior.',
                        'data' => null,
                        'token' => null,
                    ], 403);
                }
                $targetStudent = $student;
            } else {
                // Unauthenticated access must include valid student_id
                $targetStudent = Student::find($requestedStudentId);
                if (!$targetStudent) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Student not found.',
                        'data' => null,
                        'token' => null,
                    ], 404);
                }
            }

            $query = BehaviorRecord::where('student_id', $targetStudent->id);

            // Optional filter: type
            if ($request->filled('type')) {
                $query->where('type', $request->type);
            }

            // Filter by date (same logic as parent API)
            if ($request->filled('date')) {
                // Validate date format
                try {
                    $behaviorDate = Carbon::parse($request->date);
                    $query->whereDate('date', $behaviorDate->format('Y-m-d'));
                } catch (\Exception $e) {
                    return response()->json([
                        'success' => false,
                        'message' => 'Invalid date format. Please use Y-m-d format (e.g., 2024-01-15)',
                        'data' => null,
                        'token' => null,
                    ], 400);
                }
            }

            // Optional filter: date range
            if ($request->filled('date_from')) {
                $query->whereDate('date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('date', '<=', $request->date_to);
            }

            // Optional filter: points range
            if ($request->filled('points_min')) {
                $query->where('points', '>=', $request->points_min);
            }
            if ($request->filled('points_max')) {
                $query->where('points', '<=', $request->points_max);
            }

            // Pagination (same style as other APIs)
            $perPage = $request->get('per_page', 10);
            $perPage = in_array((int) $perPage, [10, 25, 50, 100], true) ? (int) $perPage : 10;

            $records = $query->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            // Format response (same format as parent API)
            $recordsData = $records->map(function (BehaviorRecord $record) use ($targetStudent) {
                return [
                    'id' => $record->id,
                    'student_id' => (string) $record->student_id,
                    'student_name' => $record->student_name ?? $targetStudent->student_name,
                    'type' => $record->type,
                    'category' => $record->category ?? '',
                    'points' => $record->points, // -2, -1, 0, +1, +2 (teacher ne jo save kiya)
                    'points_display' => ((int) $record->points > 0 ? '+' : '') . (string) ((int) $record->points),
                    'class' => $record->class,
                    'section' => $record->section,
                    'campus' => $record->campus,
                    'date' => $record->date ? $record->date->format('Y-m-d') : null,
                    'date_formatted' => $record->date ? $record->date->format('d-m-Y') : null,
                    'description' => $record->description, // e.g. "+2 Points" / "-1 Points"
                    'recorded_by' => $record->recorded_by,
                    'created_at' => $record->created_at ? $record->created_at->format('Y-m-d H:i:s') : null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Behavior records loaded successfully.',
                'data' => [
                    'student' => [
                        'id' => $targetStudent->id,
                        'name' => $targetStudent->student_name,
                        'student_code' => $targetStudent->student_code,
                        'class' => $targetStudent->class,
                        'section' => $targetStudent->section,
                        'campus' => $targetStudent->campus,
                    ],
                    'records' => $recordsData->values()->all(),
                    'pagination' => [
                        'current_page' => $records->currentPage(),
                        'last_page' => $records->lastPage(),
                        'per_page' => $records->perPage(),
                        'total' => $records->total(),
                        'from' => $records->firstItem(),
                        'to' => $records->lastItem(),
                    ],
                ],
                'token' => $request->user()?->currentAccessToken()?->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving behavior records: ' . $e->getMessage(),
                'data' => null,
                'token' => null,
            ], 500);
        }
    }

    /**
     * Get today's behavior record for logged-in student
     *
     * GET /api/student/behavior/today
     */
    public function today(Request $request): JsonResponse
    {
        try {
            $student = $request->user();

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'data' => null,
                ], 404);
            }

            $today = Carbon::today();

            $record = BehaviorRecord::where('student_id', $student->id)
                ->whereDate('date', $today)
                ->orderBy('created_at', 'desc')
                ->first();

            if (!$record) {
                return response()->json([
                    'success' => true,
                    'message' => 'No behavior record found for today.',
                    'data' => [
                        'has_record' => false,
                        'record' => null,
                    ],
                    'token' => $request->user()->currentAccessToken()->token ?? null,
                ], 200);
            }

            return response()->json([
                'success' => true,
                'message' => 'Today\'s behavior record loaded successfully.',
                'data' => [
                    'has_record' => true,
                    'record' => [
                        'id' => $record->id,
                        'type' => $record->type,
                        'points' => $record->points,
                        'class' => $record->class,
                        'section' => $record->section,
                        'campus' => $record->campus,
                        'date' => $record->date ? $record->date->format('Y-m-d') : null,
                        'date_formatted' => $record->date ? $record->date->format('d M Y') : null,
                        'description' => $record->description,
                        'recorded_by' => $record->recorded_by,
                        'created_at' => $record->created_at ? $record->created_at->format('Y-m-d H:i:s') : null,
                    ],
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving today\'s behavior: ' . $e->getMessage(),
                'data' => null,
                'token' => null,
            ], 500);
        }
    }
}

