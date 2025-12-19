<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StudentLeaveController extends Controller
{
    /**
     * Create Leave Request for Logged-in Student
     * 
     * POST /api/student/leave/create
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
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

            // Validate request
            $validated = $request->validate([
                'leave_reason' => ['required', 'string', 'max:255'],
                'from_date' => ['required', 'date'],
                'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            ], [
                'leave_reason.required' => 'Leave reason is required.',
                'leave_reason.max' => 'Leave reason must not exceed 255 characters.',
                'from_date.required' => 'From date is required.',
                'from_date.date' => 'From date must be a valid date.',
                'to_date.required' => 'To date is required.',
                'to_date.date' => 'To date must be a valid date.',
                'to_date.after_or_equal' => 'To date must be equal to or after from date.',
            ]);

            // Create leave request for logged-in student
            $leave = Leave::create([
                'staff_id' => null, // Student leave, so staff_id is null
                'student_id' => $student->id,
                'leave_reason' => $validated['leave_reason'],
                'from_date' => $validated['from_date'],
                'to_date' => $validated['to_date'],
                'status' => 'Pending', // Default status - Super Admin will approve/reject
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Leave request submitted successfully. It will be reviewed by the admin.',
                'data' => [
                    'leave' => [
                        'id' => $leave->id,
                        'student_id' => $leave->student_id,
                        'student_name' => $student->student_name,
                        'student_code' => $student->student_code,
                        'leave_reason' => $leave->leave_reason,
                        'from_date' => $leave->from_date->format('Y-m-d'),
                        'from_date_formatted' => $leave->from_date->format('d M Y'),
                        'to_date' => $leave->to_date->format('Y-m-d'),
                        'to_date_formatted' => $leave->to_date->format('d M Y'),
                        'status' => $leave->status,
                        'remarks' => $leave->remarks,
                        'created_at' => $leave->created_at->format('Y-m-d H:i:s'),
                        'created_at_formatted' => $leave->created_at->format('d M Y, h:i A'),
                    ],
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
                'token' => null,
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating leave request: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Get Leave Requests List for Logged-in Student
     * 
     * GET /api/student/leave/list
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
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

            // Build query for logged-in student's leaves
            $query = Leave::where('student_id', $student->id);

            // Optional filter: status
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Optional filter: date range
            if ($request->filled('date_from')) {
                $query->whereDate('from_date', '>=', $request->date_from);
            }
            if ($request->filled('date_to')) {
                $query->whereDate('to_date', '<=', $request->date_to);
            }

            // Pagination
            $perPage = $request->get('per_page', 10);
            $perPage = in_array((int) $perPage, [10, 25, 50, 100], true) ? (int) $perPage : 10;

            $leaves = $query->orderBy('created_at', 'desc')
                ->paginate($perPage);

            // Format response
            $leavesData = $leaves->map(function (Leave $leave) {
                return [
                    'id' => $leave->id,
                    'leave_reason' => $leave->leave_reason,
                    'from_date' => $leave->from_date ? $leave->from_date->format('Y-m-d') : null,
                    'from_date_formatted' => $leave->from_date ? $leave->from_date->format('d M Y') : null,
                    'to_date' => $leave->to_date ? $leave->to_date->format('Y-m-d') : null,
                    'to_date_formatted' => $leave->to_date ? $leave->to_date->format('d M Y') : null,
                    'status' => $leave->status,
                    'remarks' => $leave->remarks,
                    'created_at' => $leave->created_at ? $leave->created_at->format('Y-m-d H:i:s') : null,
                    'created_at_formatted' => $leave->created_at ? $leave->created_at->format('d M Y, h:i A') : null,
                    'updated_at' => $leave->updated_at ? $leave->updated_at->format('Y-m-d H:i:s') : null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Leave requests retrieved successfully.',
                'data' => [
                    'student' => [
                        'id' => $student->id,
                        'name' => $student->student_name,
                        'student_code' => $student->student_code,
                    ],
                    'leaves' => $leavesData,
                    'pagination' => [
                        'current_page' => $leaves->currentPage(),
                        'last_page' => $leaves->lastPage(),
                        'per_page' => $leaves->perPage(),
                        'total' => $leaves->total(),
                        'from' => $leaves->firstItem(),
                        'to' => $leaves->lastItem(),
                    ],
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving leave requests: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Get Single Leave Request by ID
     * 
     * GET /api/student/leave/{id}
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, $id): JsonResponse
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

            // Get leave request - ensure it belongs to logged-in student
            $leave = Leave::where('id', $id)
                ->where('student_id', $student->id)
                ->first();

            if (!$leave) {
                return response()->json([
                    'success' => false,
                    'message' => 'Leave request not found or you do not have access to it.',
                    'token' => null,
                ], 404);
            }

            return response()->json([
                'success' => true,
                'message' => 'Leave request retrieved successfully.',
                'data' => [
                    'leave' => [
                        'id' => $leave->id,
                        'student_id' => $leave->student_id,
                        'student_name' => $student->student_name,
                        'student_code' => $student->student_code,
                        'leave_reason' => $leave->leave_reason,
                        'from_date' => $leave->from_date ? $leave->from_date->format('Y-m-d') : null,
                        'from_date_formatted' => $leave->from_date ? $leave->from_date->format('d M Y') : null,
                        'to_date' => $leave->to_date ? $leave->to_date->format('Y-m-d') : null,
                        'to_date_formatted' => $leave->to_date ? $leave->to_date->format('d M Y') : null,
                        'status' => $leave->status,
                        'remarks' => $leave->remarks,
                        'created_at' => $leave->created_at ? $leave->created_at->format('Y-m-d H:i:s') : null,
                        'created_at_formatted' => $leave->created_at ? $leave->created_at->format('d M Y, h:i A') : null,
                        'updated_at' => $leave->updated_at ? $leave->updated_at->format('Y-m-d H:i:s') : null,
                    ],
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving leave request: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Cancel Leave Request (Only if status is Pending)
     * 
     * POST /api/student/leave/{id}/cancel
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function cancel(Request $request, $id): JsonResponse
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

            // Get leave request - ensure it belongs to logged-in student
            $leave = Leave::where('id', $id)
                ->where('student_id', $student->id)
                ->first();

            if (!$leave) {
                return response()->json([
                    'success' => false,
                    'message' => 'Leave request not found or you do not have access to it.',
                    'token' => null,
                ], 404);
            }

            // Check if leave can be cancelled (only if status is Pending)
            if ($leave->status !== 'Pending') {
                return response()->json([
                    'success' => false,
                    'message' => 'You can only cancel leave requests with Pending status. Current status: ' . $leave->status,
                    'token' => null,
                ], 400);
            }

            // Update status to Cancelled
            $leave->status = 'Cancelled';
            $leave->remarks = ($leave->remarks ? $leave->remarks . ' ' : '') . 'Cancelled by student.';
            $leave->save();

            return response()->json([
                'success' => true,
                'message' => 'Leave request cancelled successfully.',
                'data' => [
                    'leave' => [
                        'id' => $leave->id,
                        'status' => $leave->status,
                        'remarks' => $leave->remarks,
                    ],
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while cancelling leave request: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Delete Leave Request
     * Student can delete their own leave request
     * 
     * DELETE /api/student/leave/{id}
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function delete(Request $request, $id): JsonResponse
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

            // Get leave request - ensure it belongs to logged-in student
            $leave = Leave::where('id', $id)
                ->where('student_id', $student->id)
                ->first();

            if (!$leave) {
                return response()->json([
                    'success' => false,
                    'message' => 'Leave request not found or you do not have access to it.',
                    'token' => null,
                ], 404);
            }

            // Store leave info before deletion for response
            $leaveInfo = [
                'id' => $leave->id,
                'student_id' => $leave->student_id,
                'student_name' => $student->student_name,
                'student_code' => $student->student_code,
                'leave_reason' => $leave->leave_reason,
                'from_date' => $leave->from_date ? $leave->from_date->format('Y-m-d') : null,
                'to_date' => $leave->to_date ? $leave->to_date->format('Y-m-d') : null,
                'status' => $leave->status,
            ];

            // Delete the leave request
            $leave->delete();

            return response()->json([
                'success' => true,
                'message' => 'Leave request deleted successfully',
                'data' => [
                    'deleted_leave' => $leaveInfo,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting leave request: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }
}

