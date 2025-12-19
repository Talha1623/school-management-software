<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\OnlineClass;
use App\Models\Student;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParentOnlineClassController extends Controller
{
    /**
     * List online classes for a specific student (or by class/section).
     *
     * GET /api/parent/online-classes?student_id=123
     * Optional: date, date_from, date_to
     */
    public function index(Request $request): JsonResponse
    {
        $parent = $request->user();

        $query = OnlineClass::query();

        // If student_id provided, filter by that student's class/section
        if ($request->filled('student_id')) {
            $student = Student::where('id', $request->student_id)
                ->where('parent_account_id', $parent->id)
                ->first();

            if (!$student) {
                return response()->json([
                    'success' => false,
                    'message' => 'Student not found for this parent.',
                ], 404);
            }

            if (!empty($student->class)) {
                $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($student->class))]);
            }
            if (!empty($student->section)) {
                $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($student->section))]);
            }
        } else {
            // Fallback: allow filtering directly by class/section (if needed)
            if ($request->filled('class')) {
                $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower(trim($request->class))]);
            }
            if ($request->filled('section')) {
                $query->whereRaw('LOWER(TRIM(section)) = ?', [strtolower(trim($request->section))]);
            }
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

        // Default: show upcoming + recent classes (ordered by date/time)
        $perPage = $request->get('per_page', 10);
        $perPage = in_array((int)$perPage, [10, 25, 50, 100], true) ? (int)$perPage : 10;

        $classes = $query->orderBy('start_date', 'desc')
            ->orderBy('start_time', 'asc')
            ->paginate($perPage);

        $data = $classes->map(function (OnlineClass $class) {
            return [
                'id' => $class->id,
                'campus' => $class->campus,
                'class' => $class->class,
                'section' => $class->section,
                'class_topic' => $class->class_topic,
                'start_date' => $class->start_date?->format('Y-m-d'),
                'start_time' => $class->start_time,
                'timing' => $class->timing,
                'password' => $class->password,
                'created_by' => $class->created_by,
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Online classes loaded successfully.',
            'data' => [
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
        ], 200);
    }
}