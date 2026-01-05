<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TeacherLeaveController extends Controller
{
    /**
     * Create Leave Application
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can create leave applications.',
                    'token' => null,
                ], 403);
            }

            // Validate request
            $validated = $request->validate([
                'leave_reason' => ['required', 'string', 'max:255'],
                'from_date' => ['required', 'date', 'date_format:Y-m-d'],
                'to_date' => ['required', 'date', 'date_format:Y-m-d', 'after_or_equal:from_date'],
            ], [
                'leave_reason.required' => 'Leave reason is required.',
                'leave_reason.string' => 'Leave reason must be a string.',
                'leave_reason.max' => 'Leave reason must not exceed 255 characters.',
                'from_date.required' => 'From date is required.',
                'from_date.date' => 'From date must be a valid date.',
                'from_date.date_format' => 'From date must be in YYYY-MM-DD format.',
                'to_date.required' => 'To date is required.',
                'to_date.date' => 'To date must be a valid date.',
                'to_date.date_format' => 'To date must be in YYYY-MM-DD format.',
                'to_date.after_or_equal' => 'To date must be equal to or after from date.',
            ]);

            // Create leave application
            $leave = Leave::create([
                'staff_id' => $teacher->id,
                'leave_reason' => $validated['leave_reason'],
                'from_date' => $validated['from_date'],
                'to_date' => $validated['to_date'],
                'status' => 'Pending',
                'remarks' => null,
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Leave application created successfully.',
                'data' => [
                    'leave' => [
                        'id' => $leave->id,
                        'staff_id' => $leave->staff_id,
                        'staff_name' => $teacher->name,
                        'leave_reason' => $leave->leave_reason,
                        'from_date' => $leave->from_date->format('Y-m-d'),
                        'to_date' => $leave->to_date->format('Y-m-d'),
                        'status' => $leave->status,
                        'created_at' => $leave->created_at->format('Y-m-d H:i:s'),
                    ],
                ],
                'token' => $request->bearerToken(),
            ], 201);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed.',
                'errors' => $e->errors(),
                'token' => $request->bearerToken(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating leave application.',
                'error' => config('app.debug') ? $e->getMessage() : null,
                'token' => $request->bearerToken(),
            ], 500);
        }
    }

    /**
     * Get Leave Applications List
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access leave applications.',
                    'token' => null,
                ], 403);
            }

            $query = Leave::where('staff_id', $teacher->id)
                ->orderBy('created_at', 'desc');

            // Filter by status if provided
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            $perPage = $request->get('per_page', 10);
            $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;

            $leaves = $query->paginate($perPage);

            $leavesData = $leaves->map(function ($leave) {
                return [
                    'id' => $leave->id,
                    'leave_reason' => $leave->leave_reason,
                    'from_date' => $leave->from_date->format('Y-m-d'),
                    'to_date' => $leave->to_date->format('Y-m-d'),
                    'status' => $leave->status,
                    'remarks' => $leave->remarks,
                    'created_at' => $leave->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $leave->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Leave applications retrieved successfully.',
                'data' => [
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
                'token' => $request->bearerToken(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving leave applications.',
                'error' => config('app.debug') ? $e->getMessage() : null,
                'token' => $request->bearerToken(),
            ], 500);
        }
    }

    /**
     * Cancel Leave Application
     * Only pending leaves can be cancelled
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function cancel(Request $request, int $id): JsonResponse
    {
        try {
            $teacher = $request->user();

            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can cancel leave applications.',
                    'token' => null,
                ], 403);
            }

            // Find the leave application
            $leave = Leave::where('staff_id', $teacher->id)
                ->where('id', $id)
                ->first();

            if (!$leave) {
                return response()->json([
                    'success' => false,
                    'message' => 'Leave application not found.',
                    'token' => $request->bearerToken(),
                ], 404);
            }

            // Check if leave is already cancelled
            if (strtolower(trim($leave->status)) === 'cancelled') {
                return response()->json([
                    'success' => false,
                    'message' => 'Leave application is already cancelled.',
                    'token' => $request->bearerToken(),
                ], 400);
            }

            // Check if leave is already approved or rejected
            if (strtolower(trim($leave->status)) === 'approved') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel an approved leave application.',
                    'token' => $request->bearerToken(),
                ], 400);
            }

            if (strtolower(trim($leave->status)) === 'rejected') {
                return response()->json([
                    'success' => false,
                    'message' => 'Cannot cancel a rejected leave application.',
                    'token' => $request->bearerToken(),
                ], 400);
            }

            // Cancel the leave (only pending leaves can be cancelled)
            $leave->update([
                'status' => 'Cancelled',
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Leave application cancelled successfully.',
                'data' => [
                    'leave' => [
                        'id' => $leave->id,
                        'leave_reason' => $leave->leave_reason,
                        'from_date' => $leave->from_date->format('Y-m-d'),
                        'to_date' => $leave->to_date->format('Y-m-d'),
                        'status' => $leave->status,
                        'updated_at' => $leave->updated_at->format('Y-m-d H:i:s'),
                    ],
                ],
                'token' => $request->bearerToken(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while cancelling leave application.',
                'error' => config('app.debug') ? $e->getMessage() : null,
                'token' => $request->bearerToken(),
            ], 500);
        }
    }
}

