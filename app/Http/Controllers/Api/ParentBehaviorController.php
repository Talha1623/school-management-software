<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\BehaviorRecord;
use App\Models\ParentAccount;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Carbon\Carbon;

class ParentBehaviorController extends Controller
{
    /**
     * Behavior summary for a single student (current year vs last year + type-wise)
     *
     * GET /api/parent/behavior/summary?student_id=123
     */
    public function summary(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Ensure the authenticated user is a ParentAccount
        if (!$user instanceof ParentAccount) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. This endpoint is only for parent accounts.',
                'data' => null,
            ], 403);
        }
        
        $parent = $user;

        // Basic validation
        $request->validate([
            'student_id' => ['required', 'integer'],
        ]);

        // Ensure the requested student belongs to this parent
        $student = Student::where('id', $request->student_id)
            ->where('parent_account_id', $parent->id)
            ->first();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found for this parent.',
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
                    'class' => $student->class,
                    'section' => $student->section,
                ],
                'current_year_points' => $currentYearPoints,
                'last_year_points' => $lastYearPoints,
                // Frontend/mobile can use this for % pie chart
                'summary' => $behaviorSummary,
            ],
        ], 200);
    }

    /**
     * Behavior records list for parent (all kids or single student)
     *
     * Examples:
     * - GET /api/parent/behavior/records            -> all kids, all time
     * - GET /api/parent/behavior/records?student_id=123 -> single student, all time
     * - GET /api/parent/behavior/records?student_id=123&date=2025-12-15 -> single student, specific date
     * - GET /api/parent/behavior/records?date_from=2025-12-01&date_to=2025-12-31
     */
    public function records(Request $request): JsonResponse
    {
        $user = $request->user();
        
        // Ensure the authenticated user is a ParentAccount
        if (!$user instanceof ParentAccount) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized. This endpoint is only for parent accounts.',
                'token' => null,
            ], 403);
        }
        
        $parent = $user;

        // Optional filter: specific student_id (must belong to this parent)
        if ($request->filled('student_id')) {
            $studentId = (int) $request->student_id;
            
            // Verify the student belongs to this parent
            $student = Student::where('id', $studentId)
                ->where('parent_account_id', $parent->id)
                ->first();
            
            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'You are not allowed to view this student\'s behavior.',
                    'token' => null,
                ], 403);
            }
        }

        // All student IDs for this parent
        $studentIds = $parent->students()->pluck('id');

        if ($studentIds->isEmpty()) {
            return response()->json([
                'success' => true,
                'message' => 'No students connected to this parent.',
                'data' => [
                    'records' => [],
                    'pagination' => [
                        'current_page' => 1,
                        'last_page' => 1,
                        'per_page' => 10,
                        'total' => 0,
                        'from' => null,
                        'to' => null,
                    ],
                ],
            ], 200);
        }

        $query = BehaviorRecord::query();

        // Restrict to only this parent's students
        $query->whereIn('student_id', $studentIds->toArray());

        // Apply student_id filter if provided
        if ($request->filled('student_id')) {
            $query->where('student_id', (int) $request->student_id);
        }

        // Optional filter: type
        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }

        // Filter by date
        if ($request->filled('date')) {
            // Validate date format
            try {
                $behaviorDate = Carbon::parse($request->date);
                $query->whereDate('date', $behaviorDate->format('Y-m-d'));
            } catch (\Exception $e) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid date format. Please use Y-m-d format (e.g., 2024-01-15)',
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

        // Pagination (same style as other APIs)
        $perPage = $request->get('per_page', 10);
        $perPage = in_array((int) $perPage, [10, 25, 50, 100], true) ? (int) $perPage : 10;

        $records = $query->orderBy('date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        // Format response
        $recordsData = $records->map(function (BehaviorRecord $record) {
            return [
                'id' => $record->id,
                'student_id' => $record->student_id,
                'student_name' => $record->student_name,
                'type' => $record->type,
                'points' => $record->points, // -2, -1, 0, +1, +2 (teacher ne jo save kiya)
                'class' => $record->class,
                'section' => $record->section,
                'campus' => $record->campus,
                'date' => $record->date ? $record->date->format('Y-m-d') : null,
                'description' => $record->description, // e.g. "+2 Points" / "-1 Points"
                'recorded_by' => $record->recorded_by,
                'created_at' => $record->created_at ? $record->created_at->format('Y-m-d H:i:s') : null,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Behavior records loaded successfully.',
            'data' => $recordsData,
            'token' => $request->user()->currentAccessToken()->token ?? null,
        ], 200);
    }
}


