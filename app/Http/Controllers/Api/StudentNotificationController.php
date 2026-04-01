<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\StudentNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class StudentNotificationController extends Controller
{
    /**
     * List notifications for logged-in student (in-app notifications).
     * GET /api/student/notifications
     */
    public function index(Request $request): JsonResponse
    {
        $student = $request->user();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'token' => null,
            ], 404);
        }

        $perPage = (int) $request->get('per_page', 30);
        $perPage = in_array($perPage, [10, 25, 30, 50, 100], true) ? $perPage : 30;

        $query = StudentNotification::where('student_id', $student->id);

        if ($request->filled('unread_only') && (string) $request->unread_only === '1') {
            $query->whereNull('read_at');
        }

        $items = $query
            ->orderByRaw('CASE WHEN read_at IS NULL THEN 0 ELSE 1 END')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'message' => 'Notifications retrieved successfully',
            'data' => [
                'notifications' => $items->getCollection()->map(function (StudentNotification $n) {
                    return [
                        'id' => $n->id,
                        'title' => $n->title,
                        'message' => $n->message,
                        'data' => $n->data,
                        'read_at' => $n->read_at ? $n->read_at->format('Y-m-d H:i:s') : null,
                        'created_at' => $n->created_at ? $n->created_at->format('Y-m-d H:i:s') : null,
                    ];
                })->values(),
                'pagination' => [
                    'current_page' => $items->currentPage(),
                    'last_page' => $items->lastPage(),
                    'per_page' => $items->perPage(),
                    'total' => $items->total(),
                    'from' => $items->firstItem(),
                    'to' => $items->lastItem(),
                ],
            ],
            'token' => $request->user()->currentAccessToken()->token ?? null,
        ], 200);
    }

    /**
     * Mark notification as read.
     * POST /api/student/notifications/{id}/read
     */
    public function markRead(Request $request, int $id): JsonResponse
    {
        $student = $request->user();

        if (!$student) {
            return response()->json([
                'success' => false,
                'message' => 'User not found',
                'token' => null,
            ], 404);
        }

        $notification = StudentNotification::where('student_id', $student->id)->find($id);

        if (!$notification) {
            return response()->json([
                'success' => false,
                'message' => 'Notification not found',
                'token' => null,
            ], 404);
        }

        if (!$notification->read_at) {
            $notification->read_at = now();
            $notification->save();
        }

        return response()->json([
            'success' => true,
            'message' => 'Notification marked as read',
            'data' => [
                'id' => $notification->id,
                'read_at' => $notification->read_at ? $notification->read_at->format('Y-m-d H:i:s') : null,
            ],
            'token' => $request->user()->currentAccessToken()->token ?? null,
        ], 200);
    }
}

