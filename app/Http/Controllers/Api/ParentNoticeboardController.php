<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Noticeboard;
use App\Models\ParentAccount;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ParentNoticeboardController extends Controller
{
    /**
     * Get Noticeboard List for Parents
     * Parents can view notices that are marked for mobile_app or all public notices
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

            // Get parent's students' campuses
            $parentCampuses = $parent->students()
                ->whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->toArray();

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

            // Filter by parent's students' campuses (if any)
            if (!empty($parentCampuses)) {
                $query->where(function($q) use ($parentCampuses) {
                    $q->whereNull('campus')
                      ->orWhereIn('campus', $parentCampuses);
                });
            } else {
                // If parent has no students with campus, show all notices
                // Or show notices with null campus
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

            // Filter by campus (optional)
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
                    'parent_id' => $parent->id,
                    'parent_campuses' => $parentCampuses,
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
            ], 200);
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
            $parent = $request->user();
            
            if (!$parent) {
                return response()->json([
                    'success' => false,
                    'message' => 'User not found',
                    'token' => null,
                ], 404);
            }

            // Get parent's students' campuses
            $parentCampuses = $parent->students()
                ->whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->toArray();

            // Find noticeboard that parent can access
            $query = Noticeboard::where(function($q) {
                $q->where('show_on', 'Yes')
                  ->orWhereRaw("FIND_IN_SET('mobile_app', show_on) > 0")
                  ->orWhereNull('show_on')
                  ->orWhere('show_on', '');
            });

            // Filter by parent's campuses
            if (!empty($parentCampuses)) {
                $query->where(function($q) use ($parentCampuses) {
                    $q->whereNull('campus')
                      ->orWhereIn('campus', $parentCampuses);
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
                'message' => 'Notice retrieved successfully',
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
                'message' => 'Notice not found or you do not have access to view it',
                'token' => null,
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving notice: ' . $e->getMessage(),
                'token' => null,
            ], 200);
        }
    }

    /**
     * Get Filter Options (Campuses)
     * Returns campuses based on parent's students
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function getFilterOptions(Request $request): JsonResponse
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

            // Get campuses from parent's students
            $campuses = $parent->students()
                ->whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();

            // Also get campuses from all notices (for reference)
            $allNoticeCampuses = Noticeboard::whereNotNull('campus')
                ->distinct()
                ->pluck('campus')
                ->sort()
                ->values();

            return response()->json([
                'success' => true,
                'message' => 'Filter options retrieved successfully',
                'data' => [
                    'campuses' => $campuses,
                    'all_campuses' => $allNoticeCampuses,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving filter options: ' . $e->getMessage(),
                'token' => null,
            ], 200);
        }
    }
}

