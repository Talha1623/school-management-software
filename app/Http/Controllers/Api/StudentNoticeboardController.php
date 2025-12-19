<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Noticeboard;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class StudentNoticeboardController extends Controller
{
    /**
     * Get Noticeboard List for Logged-in Student
     * Students can view notices that are marked for mobile_app or all public notices
     * Filtered by student's campus
     * 
     * GET /api/student/noticeboard/list
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

            // Query notices - show notices where:
            // 1. show_on = 'Yes' (public notices)
            // 2. show_on contains 'mobile_app' (mobile app specific)
            // 3. Or show_on is null/empty (legacy notices)
            $query = Noticeboard::where(function($q) {
                $q->where('show_on', 'Yes')
                  ->orWhereRaw("FIND_IN_SET('mobile_app', show_on) > 0")
                  ->orWhereNull('show_on')
                  ->orWhere('show_on', '');
            });

            // Filter by student's campus (if student has campus)
            if (!empty($student->campus)) {
                $query->where(function($q) use ($student) {
                    $q->whereNull('campus')
                      ->orWhere('campus', '')
                      ->orWhereRaw('LOWER(TRIM(campus)) = LOWER(?)', [trim($student->campus)]);
                });
            } else {
                // If student has no campus, show notices with null/empty campus
                $query->where(function($q) {
                    $q->whereNull('campus')
                      ->orWhere('campus', '');
                });
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

            // Filter by campus (optional - override student's campus)
            if ($request->filled('campus')) {
                $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($request->campus))]);
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
                // Get image URL
                $imageUrl = null;
                if ($noticeboard->image) {
                    $imageUrl = asset('storage/' . $noticeboard->image);
                    // Convert to full URL if needed
                    if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                        $imageUrl = url($imageUrl);
                    }
                }

                return [
                    'id' => $noticeboard->id,
                    'campus' => $noticeboard->campus ?? null,
                    'title' => $noticeboard->title,
                    'notice' => $noticeboard->notice ?? null,
                    'date' => $noticeboard->date->format('Y-m-d'),
                    'date_formatted' => $noticeboard->date->format('d M Y'),
                    'date_formatted_full' => $noticeboard->date->format('l, d F Y'),
                    'image' => $imageUrl,
                    'show_on' => $noticeboard->show_on ?? 'No',
                    'created_at' => $noticeboard->created_at->format('Y-m-d H:i:s'),
                    'created_at_formatted' => $noticeboard->created_at->format('d M Y, h:i A'),
                    'updated_at' => $noticeboard->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'School notices retrieved successfully',
                'data' => [
                    'student' => [
                        'id' => $student->id,
                        'name' => $student->student_name,
                        'student_code' => $student->student_code,
                        'campus' => $student->campus ?? null,
                    ],
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
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving notices: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Get Single Noticeboard
     * 
     * GET /api/student/noticeboard/{id}
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function show(Request $request, int $id): JsonResponse
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

            // Find noticeboard that student can access
            $query = Noticeboard::where(function($q) {
                $q->where('show_on', 'Yes')
                  ->orWhereRaw("FIND_IN_SET('mobile_app', show_on) > 0")
                  ->orWhereNull('show_on')
                  ->orWhere('show_on', '');
            });

            // Filter by student's campus
            if (!empty($student->campus)) {
                $query->where(function($q) use ($student) {
                    $q->whereNull('campus')
                      ->orWhere('campus', '')
                      ->orWhereRaw('LOWER(TRIM(campus)) = LOWER(?)', [trim($student->campus)]);
                });
            } else {
                $query->where(function($q) {
                    $q->whereNull('campus')
                      ->orWhere('campus', '');
                });
            }

            $noticeboard = $query->findOrFail($id);

            // Get image URL
            $imageUrl = null;
            if ($noticeboard->image) {
                $imageUrl = asset('storage/' . $noticeboard->image);
                if (!filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                    $imageUrl = url($imageUrl);
                }
            }

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
                        'date_formatted_full' => $noticeboard->date->format('l, d F Y'),
                        'image' => $imageUrl,
                        'show_on' => $noticeboard->show_on ?? 'No',
                        'created_at' => $noticeboard->created_at->format('Y-m-d H:i:s'),
                        'created_at_formatted' => $noticeboard->created_at->format('d M Y, h:i A'),
                        'updated_at' => $noticeboard->updated_at->format('Y-m-d H:i:s'),
                    ],
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Noticeboard not found or you do not have access to it.',
                'token' => null,
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving noticeboard: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }

    /**
     * Get Filter Options for Noticeboard
     * 
     * GET /api/student/noticeboard/filter-options
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getFilterOptions(Request $request): JsonResponse
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

            // Get available campuses from notices that student can access
            $query = Noticeboard::where(function($q) {
                $q->where('show_on', 'Yes')
                  ->orWhereRaw("FIND_IN_SET('mobile_app', show_on) > 0")
                  ->orWhereNull('show_on')
                  ->orWhere('show_on', '');
            });

            // Filter by student's campus
            if (!empty($student->campus)) {
                $query->where(function($q) use ($student) {
                    $q->whereNull('campus')
                      ->orWhere('campus', '')
                      ->orWhereRaw('LOWER(TRIM(campus)) = LOWER(?)', [trim($student->campus)]);
                });
            }

            $campuses = $query->whereNotNull('campus')
                ->where('campus', '!=', '')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();

            return response()->json([
                'success' => true,
                'message' => 'Filter options retrieved successfully',
                'data' => [
                    'campuses' => $campuses,
                    'show_on_options' => ['Yes', 'No'],
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving filter options: ' . $e->getMessage(),
                'token' => null,
            ], 500);
        }
    }
}

