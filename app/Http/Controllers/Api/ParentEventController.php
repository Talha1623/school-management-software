<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class ParentEventController extends Controller
{
    /**
     * Get Academic Calendar Events List
     * Parents can view all academic calendar events (read-only)
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

            $query = Event::query();

            // Filter by year
            if ($request->filled('year')) {
                $query->whereYear('event_date', $request->year);
            }

            // Filter by month (1-12)
            if ($request->filled('month')) {
                $query->whereMonth('event_date', $request->month);
            }

            // Filter by event type
            if ($request->filled('event_type')) {
                $query->where('event_type', $request->event_type);
            }

            // Filter by date range
            if ($request->filled('start_date')) {
                $query->whereDate('event_date', '>=', $request->start_date);
            }

            if ($request->filled('end_date')) {
                $query->whereDate('event_date', '<=', $request->end_date);
            }

            // Search by title or details
            if ($request->filled('search')) {
                $search = trim($request->search);
                if (!empty($search)) {
                    $searchLower = strtolower($search);
                    $query->where(function($q) use ($searchLower) {
                        $q->whereRaw('LOWER(event_title) LIKE ?', ["%{$searchLower}%"])
                          ->orWhereRaw('LOWER(event_details) LIKE ?', ["%{$searchLower}%"]);
                    });
                }
            }

            // Pagination
            $perPage = $request->get('per_page', 30);
            $perPage = in_array($perPage, [10, 25, 30, 50, 100]) ? $perPage : 30;

            $events = $query->orderBy('event_date', 'asc')
                ->orderBy('id', 'asc')
                ->paginate($perPage);

            // Format events data
            $eventsData = $events->map(function($event) {
                return [
                    'id' => $event->id,
                    'event_title' => $event->event_title,
                    'event_details' => $event->event_details ?? null,
                    'event_type' => $event->event_type ?? null,
                    'event_date' => $event->event_date->format('Y-m-d'),
                    'event_date_formatted' => $event->event_date->format('d M Y'),
                    'event_date_formatted_full' => $event->event_date->format('l, d F Y'),
                    'day_name' => $event->event_date->format('l'),
                    'day_short' => $event->event_date->format('D'),
                    'month' => (int) $event->event_date->format('n'),
                    'month_name' => $event->event_date->format('F'),
                    'year' => (int) $event->event_date->format('Y'),
                    'created_at' => $event->created_at->format('Y-m-d H:i:s'),
                    'created_at_formatted' => $event->created_at->format('d M Y, h:i A'),
                    'updated_at' => $event->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Academic calendar events retrieved successfully',
                'data' => [
                    'parent_id' => $parent->id,
                    'events' => $eventsData,
                    'pagination' => [
                        'current_page' => $events->currentPage(),
                        'last_page' => $events->lastPage(),
                        'per_page' => $events->perPage(),
                        'total' => $events->total(),
                        'from' => $events->firstItem(),
                        'to' => $events->lastItem(),
                    ],
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving events: ' . $e->getMessage(),
                'token' => null,
            ], 200);
        }
    }

    /**
     * Get Events by Month and Year
     * 
     * @param Request $request
     * @param int $month
     * @param int $year
     * @return JsonResponse
     */
    public function getEventsByMonthYear(Request $request, int $month, int $year): JsonResponse
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

            // Validate month (1-12)
            if ($month < 1 || $month > 12) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid month. Month must be between 1 and 12.',
                    'token' => $request->bearerToken(),
                ], 422);
            }

            // Validate year (reasonable range)
            if ($year < 2000 || $year > 2100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid year. Year must be between 2000 and 2100.',
                    'token' => $request->bearerToken(),
                ], 422);
            }

            // Get events for the specified month and year
            $events = Event::whereYear('event_date', $year)
                ->whereMonth('event_date', $month)
                ->orderBy('event_date', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            // Format events data
            $eventsData = $events->map(function($event) {
                return [
                    'id' => $event->id,
                    'event_title' => $event->event_title,
                    'event_details' => $event->event_details ?? null,
                    'event_type' => $event->event_type ?? null,
                    'event_date' => $event->event_date->format('Y-m-d'),
                    'event_date_formatted' => $event->event_date->format('d M Y'),
                    'event_date_formatted_full' => $event->event_date->format('l, d F Y'),
                    'day_name' => $event->event_date->format('l'),
                    'day_short' => $event->event_date->format('D'),
                    'day_number' => (int) $event->event_date->format('j'),
                    'created_at' => $event->created_at->format('Y-m-d H:i:s'),
                    'updated_at' => $event->updated_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Events retrieved successfully',
                'data' => [
                    'month' => $month,
                    'year' => $year,
                    'month_name' => date('F', mktime(0, 0, 0, $month, 1, $year)),
                    'month_name_short' => date('M', mktime(0, 0, 0, $month, 1, $year)),
                    'total_events' => $events->count(),
                    'events' => $eventsData->values()->toArray(),
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving events: ' . $e->getMessage(),
                'token' => null,
            ], 200);
        }
    }

    /**
     * Get Single Event by ID
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

            $event = Event::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Event retrieved successfully',
                'data' => [
                    'event' => [
                        'id' => $event->id,
                        'event_title' => $event->event_title,
                        'event_details' => $event->event_details ?? null,
                        'event_type' => $event->event_type ?? null,
                        'event_date' => $event->event_date->format('Y-m-d'),
                        'event_date_formatted' => $event->event_date->format('d M Y'),
                        'event_date_formatted_full' => $event->event_date->format('l, d F Y'),
                        'day_name' => $event->event_date->format('l'),
                        'day_short' => $event->event_date->format('D'),
                        'month' => (int) $event->event_date->format('n'),
                        'month_name' => $event->event_date->format('F'),
                        'year' => (int) $event->event_date->format('Y'),
                        'created_at' => $event->created_at->format('Y-m-d H:i:s'),
                        'created_at_formatted' => $event->created_at->format('d M Y, h:i A'),
                        'updated_at' => $event->updated_at->format('Y-m-d H:i:s'),
                    ],
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found',
                'token' => null,
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving event: ' . $e->getMessage(),
                'token' => null,
            ], 200);
        }
    }

    /**
     * Get Calendar View (Events grouped by month for a year)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function calendarView(Request $request): JsonResponse
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

            $year = $request->get('year', date('Y'));

            // Validate year
            if ($year < 2000 || $year > 2100) {
                return response()->json([
                    'success' => false,
                    'message' => 'Invalid year. Year must be between 2000 and 2100.',
                    'token' => $request->bearerToken(),
                ], 422);
            }

            // Get all events for the year
            $events = Event::whereYear('event_date', $year)
                ->orderBy('event_date', 'asc')
                ->orderBy('id', 'asc')
                ->get();

            // Group events by month
            $eventsByMonth = [];
            foreach ($events as $event) {
                $month = (int)$event->event_date->format('n'); // 1-12
                if (!isset($eventsByMonth[$month])) {
                    $eventsByMonth[$month] = [];
                }
                $eventsByMonth[$month][] = [
                    'id' => $event->id,
                    'event_title' => $event->event_title,
                    'event_details' => $event->event_details ?? null,
                    'event_type' => $event->event_type ?? null,
                    'event_date' => $event->event_date->format('Y-m-d'),
                    'event_date_formatted' => $event->event_date->format('d M Y'),
                    'event_date_formatted_full' => $event->event_date->format('l, d F Y'),
                    'day_name' => $event->event_date->format('l'),
                    'day_number' => (int) $event->event_date->format('j'),
                ];
            }

            // Calculate total events
            $totalEvents = $events->count();

            return response()->json([
                'success' => true,
                'message' => 'Academic calendar view retrieved successfully',
                'data' => [
                    'year' => (int) $year,
                    'total_events' => $totalEvents,
                    'events_by_month' => $eventsByMonth,
                ],
                'token' => $request->user()->currentAccessToken()->token ?? null,
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving calendar view: ' . $e->getMessage(),
                'token' => null,
            ], 200);
        }
    }
}

