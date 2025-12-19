<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OnlineClass;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentOnlineClassController extends Controller
{
    /**
     * List online classes for logged-in student
     * Returns all online classes assigned to the student's class (all sections)
     *
     * GET /api/student/online-classes
     * Optional filters: date, date_from, date_to, type (upcoming/past), per_page
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $student = $request->user();

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'token' => null,
                ], 404);
            }

            if (!$student->class) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student information incomplete. Cannot fetch online classes.',
                    'token' => null,
                ], 400);
            }

            // Build query for student's class (all sections - jitne bhi classes assign kiye hain wo saare show honge)
            $query = OnlineClass::whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($student->class))]);

            // Filter by campus if student has campus
            if ($student->campus) {
                $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($student->campus))]);
            }

            // Date filters
            if ($request->filled('date')) {
                $query->whereDate('start_date', $request->date);
            }
            if ($request->filled('date_from')) {
                $query->whereDate('start_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('start_date', '<=', $request->date_to);
            }

            // Filter by upcoming/past classes
            if ($request->filled('type')) {
                $today = now()->format('Y-m-d');
                if ($request->type === 'upcoming') {
                    $query->whereDate('start_date', '>=', $today);
                } elseif ($request->type === 'past') {
                    $query->whereDate('start_date', '<', $today);
                }
            }

            // Pagination
            $perPage = $request->get('per_page', 30);
            $perPage = in_array((int)$perPage, [10, 25, 30, 50, 100], true) ? (int)$perPage : 30;

            // Order by date and time
            $classes = $query->orderBy('start_date', 'desc')
                ->orderBy('start_time', 'asc')
                ->paginate($perPage);

            // Format response data
            $data = $classes->map(function (OnlineClass $class) {
                $startDate = $class->start_date;
                $isUpcoming = $startDate && $startDate->format('Y-m-d') >= now()->format('Y-m-d');
                
                return [
                    'id' => $class->id,
                    'campus' => $class->campus,
                    'class' => $class->class,
                    'section' => $class->section,
                    'class_topic' => $class->class_topic,
                    'start_date' => $startDate ? $startDate->format('Y-m-d') : null,
                    'start_date_formatted' => $startDate ? $startDate->format('d M Y') : null,
                    'start_time' => $class->start_time,
                    'timing' => $class->timing,
                    'password' => $class->password,
                    'link' => $class->link,
                    'created_by' => $class->created_by,
                    'is_upcoming' => $isUpcoming,
                    'created_at' => $class->created_at ? $class->created_at->format('Y-m-d H:i:s') : null,
                    'created_at_formatted' => $class->created_at ? $class->created_at->format('d M Y, h:i A') : null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Online classes retrieved successfully',
                'data' => [
                    'student' => [
                        'id' => $student->id,
                        'student_name' => $student->student_name,
                        'student_code' => $student->student_code,
                        'class' => $student->class,
                        'section' => $student->section,
                        'campus' => $student->campus,
                    ],
                    'classes' => $data,
                    'pagination' => [
                        'current_page' => $classes->currentPage(),
                        'last_page' => $classes->lastPage(),
                        'per_page' => $classes->perPage(),
                        'total' => $classes->total(),
                        'from' => $classes->firstItem(),
                        'to' => $classes->lastItem(),
                    ],
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving online classes: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }
}

