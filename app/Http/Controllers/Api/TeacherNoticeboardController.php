<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Noticeboard;
use App\Models\Campus;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TeacherNoticeboardController extends Controller
{
    /**
     * Get Noticeboard List
     * Teachers can view all notices (read-only)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();
            
            if (!$teacher || strtolower(trim($teacher->designation ?? '')) !== 'teacher') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can view noticeboard.',
                ], 403);
            }

            // Only show notices where show_on = 'Yes' to staff
            $query = Noticeboard::where('show_on', 'Yes');

            // Filter by campus
            if ($request->filled('campus')) {
                $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($request->campus))]);
            }

            // Search functionality
            if ($request->filled('search')) {
                $search = trim($request->search);
                if (!empty($search)) {
                    $searchLower = strtolower($search);
                    $query->where(function($q) use ($searchLower) {
                        $q->whereRaw('LOWER(title) LIKE ?', ["%{$searchLower}%"])
                          ->orWhereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"])
                          ->orWhereRaw('LOWER(notice) LIKE ?', ["%{$searchLower}%"]);
                    });
                }
            }

            // Filter by date range
            if ($request->filled('start_date')) {
                $query->whereDate('date', '>=', $request->start_date);
            }

            if ($request->filled('end_date')) {
                $query->whereDate('date', '<=', $request->end_date);
            }

            // Pagination
            $perPage = $request->get('per_page', 30);
            $perPage = in_array($perPage, [10, 25, 30, 50, 100]) ? $perPage : 30;

            $noticeboards = $query->orderBy('date', 'desc')
                ->orderBy('created_at', 'desc')
                ->paginate($perPage);

            // Format noticeboards data
            $noticeboardsData = $noticeboards->map(function($noticeboard) {
                return [
                    'id' => $noticeboard->id,
                    'campus' => $noticeboard->campus ?? null,
                    'title' => $noticeboard->title,
                    'notice' => $noticeboard->notice ?? null,
                    'date' => $noticeboard->date->format('Y-m-d'),
                    'date_formatted' => $noticeboard->date->format('d M Y'),
                    'image' => $noticeboard->image ? asset('storage/' . $noticeboard->image) : null,
                    'show_on' => $noticeboard->show_on ?? 'No',
                    'created_at' => $noticeboard->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $noticeboard->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Noticeboard list retrieved successfully',
                'data' => [
                    'noticeboards' => $noticeboardsData,
                    'pagination' => [
                        'current_page' => $noticeboards->currentPage(),
                        'last_page' => $noticeboards->lastPage(),
                        'per_page' => $noticeboards->perPage(),
                        'total' => $noticeboards->total(),
                        'from' => $noticeboards->firstItem(),
                        'to' => $noticeboards->lastItem(),
                    ],
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving noticeboard: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Single Noticeboard
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
    {
        try {
            $teacher = $request->user();
            
            if (!$teacher || strtolower(trim($teacher->designation ?? '')) !== 'teacher') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can view noticeboard.',
                ], 403);
            }

            $noticeboard = Noticeboard::where('show_on', 'Yes')->findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Noticeboard retrieved successfully',
                'data' => [
                    'noticeboard' => [
                        'id' => $noticeboard->id,
                        'campus' => $noticeboard->campus ?? null,
                        'title' => $noticeboard->title,
                        'notice' => $noticeboard->notice ?? null,
                        'date' => $noticeboard->date->format('Y-m-d'),
                        'date_formatted' => $noticeboard->date->format('d M Y'),
                        'image' => $noticeboard->image ? asset('storage/' . $noticeboard->image) : null,
                        'show_on' => $noticeboard->show_on ?? 'No',
                        'created_at' => $noticeboard->created_at->format('Y-m-d H:i:s'),
                        'updated_at' => $noticeboard->updated_at->format('Y-m-d H:i:s'),
                    ],
                ],
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Noticeboard not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving noticeboard: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Filter Options (Campuses)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getFilterOptions(Request $request): JsonResponse
    {
        try {
            $teacher = $request->user();
            
            if (!$teacher || strtolower(trim($teacher->designation ?? '')) !== 'teacher') {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can access noticeboard.',
                ], 403);
            }

            // Get campuses from noticeboards
            $campuses = Noticeboard::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();

            // If no campuses from noticeboards, get from Campus model
            if ($campuses->isEmpty()) {
                $campuses = Campus::orderBy('campus_name', 'asc')
                    ->pluck('campus_name')
                    ->values();
            }

            return response()->json([
                'success' => true,
                'message' => 'Filter options retrieved successfully',
                'data' => [
                    'campuses' => $campuses,
                    'show_on_options' => ['Yes', 'No'],
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving filter options: ' . $e->getMessage(),
            ], 500);
        }
    }
}

