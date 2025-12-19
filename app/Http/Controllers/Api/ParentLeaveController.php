<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Leave;
use App\Models\Student;
use App\Models\ParentAccount;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ParentLeaveController extends Controller
{
    /**
     * Get Students List for Leave Request
     * Returns all students connected to this parent
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getStudents(Request $request): JsonResponse
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
            $students = $parent->students()->get();

            if ($students->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No students found for this parent',
                    'data' => [
                        'students' => [],
                    ],
                    'token' => $request->user()->currentAccessToken()->token ?? null,
                ], 200);
            }

            // Format students data
            $studentsData = $students->map(function($student) {
                return [
                    'id' => $student->id,
                    'student_name' => $student->student_name,
                    'student_code' => $student->student_code ?? null,
                    'class' => $student->class ?? null,
                    'section' => $student->section ?? null,
                    'campus' => $student->campus ?? null,
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Students retrieved successfully',
                'data' => [
                    'students' => $studentsData,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving students: ' . $e->getMessage(),
                'token' => null,
            ], 200);
        }
    }

    /**
     * Create Leave Request for Student
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
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

            // Validate request
            $validated = $request->validate([
                'student_id' => ['required', 'exists:students,id'],
                'leave_reason' => ['required', 'string', 'max:255'],
                'from_date' => ['required', 'date'],
                'to_date' => ['required', 'date', 'after_or_equal:from_date'],
            ]);

            // Verify student belongs to this parent
            $student = $parent->students()->findOrFail($validated['student_id']);

            // Create leave request
            $leave = Leave::create([
                'staff_id' => null, // Student leave, so staff_id is null
                'student_id' => $validated['student_id'],
                'leave_reason' => $validated['leave_reason'],
                'from_date' => $validated['from_date'],
                'to_date' => $validated['to_date'],
                'status' => 'Pending',
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
                        'to_date' => $leave->to_date->format('Y-m-d'),
                        'status' => $leave->status,
                        'created_at' => $leave->created_at->format('Y-m-d H:i:s'),
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
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Student not found or does not belong to this parent',
                'token' => null,
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating leave request: ' . $e->getMessage(),
                'token' => null,
            ], 200);
        }
    }

    /**
     * Get Leave Requests List
     * Returns all leave requests for parent's students
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
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

            // Get parent's students IDs
            $studentIds = $parent->students()->pluck('id');

            if ($studentIds->isEmpty()) {
                return response()->json([
                    'success' => true,
                    'message' => 'No students found',
                    'data' => [
                        'leaves' => [],
                        'pagination' => null,
                    ],
                    'token' => $request->user()->currentAccessToken()->token ?? null,
                ], 200);
            }

            // Build query
            $query = Leave::whereIn('student_id', $studentIds)
                ->with('student');

            // Filter by student_id (optional)
            if ($request->filled('student_id')) {
                $studentId = $request->student_id;
                // Verify student belongs to parent
                if ($studentIds->contains($studentId)) {
                    $query->where('student_id', $studentId);
                }
            }

            // Filter by status (optional)
            if ($request->filled('status')) {
                $query->where('status', $request->status);
            }

            // Filter by date range (optional)
            if ($request->filled('from_date')) {
                $query->whereDate('from_date', '>=', $request->from_date);
            }
            if ($request->filled('to_date')) {
                $query->whereDate('to_date', '<=', $request->to_date);
            }

            // Pagination
            $perPage = $request->get('per_page', 30);
            $perPage = in_array($perPage, [10, 25, 30, 50, 100]) ? $perPage : 30;

            $leaves = $query->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->paginate($perPage);

            // Format leaves data
            $leavesData = $leaves->map(function($leave) {
                return [
                    'id' => $leave->id,
                    'student_id' => $leave->student_id,
                    'student_name' => $leave->student->student_name ?? null,
                    'student_code' => $leave->student->student_code ?? null,
                    'class' => $leave->student->class ?? null,
                    'section' => $leave->student->section ?? null,
                    'leave_reason' => $leave->leave_reason,
                    'from_date' => $leave->from_date->format('Y-m-d'),
                    'from_date_formatted' => $leave->from_date->format('d M Y'),
                    'to_date' => $leave->to_date->format('Y-m-d'),
                    'to_date_formatted' => $leave->to_date->format('d M Y'),
                    'status' => $leave->status,
                    'remarks' => $leave->remarks ?? null,
                    'created_at' => $leave->created_at->format('Y-m-d H:i:s'),
                    'created_at_formatted' => $leave->created_at->format('d M Y, h:i A'),
                    'updated_at' => $leave->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Leave requests retrieved successfully',
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
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving leave requests: ' . $e->getMessage(),
                'token' => null,
            ], 200);
        }
    }

    /**
     * Get Leave Request by ID
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
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

            // Get parent's students IDs
            $studentIds = $parent->students()->pluck('id');

            // Get leave request
            $leave = Leave::with('student')
                ->whereIn('student_id', $studentIds)
                ->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Leave request retrieved successfully',
                'data' => [
                    'leave' => [
                        'id' => $leave->id,
                        'student_id' => $leave->student_id,
                        'student_name' => $leave->student->student_name ?? null,
                        'student_code' => $leave->student->student_code ?? null,
                        'class' => $leave->student->class ?? null,
                        'section' => $leave->student->section ?? null,
                        'leave_reason' => $leave->leave_reason,
                        'from_date' => $leave->from_date->format('Y-m-d'),
                        'from_date_formatted' => $leave->from_date->format('d M Y'),
                        'to_date' => $leave->to_date->format('Y-m-d'),
                        'to_date_formatted' => $leave->to_date->format('d M Y'),
                        'status' => $leave->status,
                        'remarks' => $leave->remarks ?? null,
                        'created_at' => $leave->created_at->format('Y-m-d H:i:s'),
                        'created_at_formatted' => $leave->created_at->format('d M Y, h:i A'),
                        'updated_at' => $leave->updated_at->format('Y-m-d H:i:s'),
                    ],
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Leave request not found or does not belong to your students',
                'token' => null,
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving leave request: ' . $e->getMessage(),
                'token' => null,
            ], 200);
        }
    }

    /**
     * Delete Leave Request by ID
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function delete(Request $request, int $id): JsonResponse
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

            // Get parent's students IDs
            $studentIds = $parent->students()->pluck('id');

            if ($studentIds->isEmpty()) {
                return response()->json([
                    'success' => false,
                    'message' => 'No students found for this parent',
                    'token' => null,
                ], 404);
            }

            // Get leave request and verify it belongs to parent's student
            $leave = Leave::whereIn('student_id', $studentIds)
                ->findOrFail($id);

            // Store leave info before deletion for response
            $leaveInfo = [
                'id' => $leave->id,
                'student_id' => $leave->student_id,
                'leave_reason' => $leave->leave_reason,
                'from_date' => $leave->from_date->format('Y-m-d'),
                'to_date' => $leave->to_date->format('Y-m-d'),
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

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Leave request not found or does not belong to your students',
                'token' => null,
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting leave request: ' . $e->getMessage(),
                'token' => null,
            ], 200);
        }
    }
}

