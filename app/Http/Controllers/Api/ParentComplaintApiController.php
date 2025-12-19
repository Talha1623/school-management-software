<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\ParentComplaint;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ParentComplaintApiController extends Controller
{
    /**
     * Submit a new complaint from parent app.
     *
     * POST /api/parent/complaints
     * Body: { "subject": "Transport", "message": "Bus late again" }
     */
    public function store(Request $request): JsonResponse
    {
        $parent = $request->user(); // ParentAccount via Sanctum

        $validated = $request->validate([
            'subject' => ['nullable', 'string', 'max:255'],
            'message' => ['required', 'string'],
        ]);

        $complaint = ParentComplaint::create([
            'parent_account_id' => $parent->id,
            'student_id' => null,
            'parent_name' => $parent->name,
            'email' => $parent->email,
            'phone' => $parent->phone ?? null,
            'subject' => $validated['subject'] ?? 'Parent Complain',
            'complain' => $validated['message'],
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Your complaint has been submitted successfully.',
            'data' => [
                'id' => $complaint->id,
            ],
        ], 201);
    }

    /**
     * List complaints of logged-in parent (optional filters).
     *
     * GET /api/parent/complaints
     * GET /api/parent/complaints?status=pending
     */
    public function index(Request $request): JsonResponse
    {
        $parent = $request->user();

        $query = ParentComplaint::where('parent_account_id', $parent->id);

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $perPage = $request->get('per_page', 10);
        $perPage = in_array((int) $perPage, [10, 25, 50, 100], true) ? (int) $perPage : 10;

        $complaints = $query->latest()->paginate($perPage);

        $data = $complaints->map(function (ParentComplaint $complaint) {
            return [
                'id' => $complaint->id,
                'subject' => $complaint->subject,
                'message' => $complaint->complain,
                'status' => $complaint->status,
                'created_at' => $complaint->created_at?->format('Y-m-d H:i:s'),
            ];
        });

        return response()->json([
            'success' => true,
            'message' => 'Complaints loaded successfully.',
            'data' => [
                'complaints' => $data,
                'pagination' => [
                    'current_page' => $complaints->currentPage(),
                    'last_page' => $complaints->lastPage(),
                    'per_page' => $complaints->perPage(),
                    'total' => $complaints->total(),
                    'from' => $complaints->firstItem(),
                    'to' => $complaints->lastItem(),
                ],
            ],
            'token' => $request->user()->currentAccessToken()->token ?? null,
        ], 200);
    }

    /**
     * Delete Complaint
     * Parent can delete their own complaint
     * 
     * DELETE /api/parent/complaints/{id}
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function delete(Request $request, $id): JsonResponse
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

            // Get complaint - ensure it belongs to logged-in parent
            $complaint = ParentComplaint::where('id', $id)
                ->where('parent_account_id', $parent->id)
                ->first();

            if (!$complaint) {
                return response()->json([
                    'success' => false,
                    'message' => 'Complaint not found or you do not have access to it.',
                    'token' => null,
                ], 404);
            }

            // Store complaint info before deletion for response
            $complaintInfo = [
                'id' => $complaint->id,
                'subject' => $complaint->subject,
                'message' => $complaint->complain,
                'status' => $complaint->status,
                'created_at' => $complaint->created_at ? $complaint->created_at->format('Y-m-d H:i:s') : null,
            ];

            // Delete the complaint
            $complaint->delete();

            return response()->json([
                'success' => true,
                'message' => 'Complaint deleted successfully',
                'data' => [
                    'deleted_complaint' => $complaintInfo,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting complaint: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }
}


