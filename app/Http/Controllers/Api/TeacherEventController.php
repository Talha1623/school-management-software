<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class TeacherEventController extends Controller
{
    /**
     * Create New Event
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function create(Request $request): JsonResponse
    {
        try {
            // Get logged-in teacher
            $teacher = $request->user();
            
            // Validate that user is a teacher
            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can create events.',
                ], 403);
            }

            $validated = $request->validate([
                'event_title' => ['required', 'string', 'max:255'],
                'event_details' => ['nullable', 'string'],
                'event_type' => ['nullable', 'string', 'max:255'],
                'event_date' => ['required', 'date'],
            ]);

            // Create event
            $event = Event::create($validated);

            return response()->json([
                'success' => true,
                'message' => 'Event created successfully',
                'data' => [
                    'event' => [
                        'id' => $event->id,
                        'event_title' => $event->event_title,
                        'event_details' => $event->event_details,
                        'event_type' => $event->event_type,
                        'event_date' => $event->event_date->format('Y-m-d'),
                        'created_at' => $event->created_at->format('Y-m-d H:i:s'),
                    ],
                ],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while creating event: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Events List
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function list(Request $request): JsonResponse
    {
        try {
            // Get logged-in teacher
            $teacher = $request->user();
            
            // Validate that user is a teacher
            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can view events.',
                ], 403);
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

            $events = $query->orderBy('event_date', 'desc')->paginate($perPage);

            // Format events data
            $eventsData = $events->map(function($event) {
                return [
                    'id' => $event->id,
                    'event_title' => $event->event_title,
                    'event_details' => $event->event_details,
                    'event_type' => $event->event_type,
                    'event_date' => $event->event_date->format('Y-m-d'),
                    'created_at' => $event->created_at->format('Y-m-d H:i:s'),
                ];
            });

            return response()->json([
                'success' => true,
                'message' => 'Events retrieved successfully',
                'data' => [
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
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving events: ' . $e->getMessage(),
            ], 500);
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
            // Get logged-in teacher
            $teacher = $request->user();
            
            // Validate that user is a teacher
            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can view events.',
                    'token' => null,
                ], 403);
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
                    'event_details' => $event->event_details,
                    'event_type' => $event->event_type,
                    'event_date' => $event->event_date->format('Y-m-d'),
                    'event_date_formatted' => $event->event_date->format('d M Y'),
                    'day_name' => $event->event_date->format('l'),
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
                    'total_events' => $events->count(),
                    'events' => $eventsData->values()->toArray(),
                ],
                'token' => $request->bearerToken(),
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving events: ' . $e->getMessage(),
                'error' => config('app.debug') ? $e->getMessage() : null,
                'token' => $request->bearerToken(),
            ], 500);
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
            // Get logged-in teacher
            $teacher = $request->user();
            
            // Validate that user is a teacher
            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can view events.',
                ], 403);
            }

            $event = Event::findOrFail($id);

            return response()->json([
                'success' => true,
                'message' => 'Event retrieved successfully',
                'data' => [
                    'event' => [
                        'id' => $event->id,
                        'event_title' => $event->event_title,
                        'event_details' => $event->event_details,
                        'event_type' => $event->event_type,
                        'event_date' => $event->event_date->format('Y-m-d'),
                        'created_at' => $event->created_at->format('Y-m-d H:i:s'),
                        'updated_at' => $event->updated_at->format('Y-m-d H:i:s'),
                    ],
                ],
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving event: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update Event
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function update(Request $request, int $id): JsonResponse
    {
        try {
            // Get logged-in teacher
            $teacher = $request->user();
            
            // Validate that user is a teacher
            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can update events.',
                ], 403);
            }

            $event = Event::findOrFail($id);

            $validated = $request->validate([
                'event_title' => ['required', 'string', 'max:255'],
                'event_details' => ['nullable', 'string'],
                'event_type' => ['nullable', 'string', 'max:255'],
                'event_date' => ['required', 'date'],
            ]);

            $event->update($validated);

            return response()->json([
                'success' => true,
                'message' => 'Event updated successfully',
                'data' => [
                    'event' => [
                        'id' => $event->id,
                        'event_title' => $event->event_title,
                        'event_details' => $event->event_details,
                        'event_type' => $event->event_type,
                        'event_date' => $event->event_date->format('Y-m-d'),
                        'updated_at' => $event->updated_at->format('Y-m-d H:i:s'),
                    ],
                ],
            ], 200);

        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while updating event: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Delete Event
     * 
     * @param Request $request
     * @param int $id
     * @return JsonResponse
     */
    public function delete(Request $request, int $id): JsonResponse
    {
        try {
            // Get logged-in teacher
            $teacher = $request->user();
            
            // Validate that user is a teacher
            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can delete events.',
                ], 403);
            }

            $event = Event::findOrFail($id);
            $event->delete();

            return response()->json([
                'success' => true,
                'message' => 'Event deleted successfully',
            ], 200);

        } catch (\Illuminate\Database\Eloquent\ModelNotFoundException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Event not found',
            ], 404);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while deleting event: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get Calendar View (Events grouped by month)
     * 
     * @param Request $request
     * @return JsonResponse
     */
    public function calendarView(Request $request): JsonResponse
    {
        try {
            // Get logged-in teacher
            $teacher = $request->user();
            
            // Validate that user is a teacher
            if (!$teacher || !$teacher->isTeacher()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Access denied. Only teachers can view calendar.',
                ], 403);
            }

            $year = $request->get('year', date('Y'));

            // Get all events for the year
            $events = Event::whereYear('event_date', $year)
                ->orderBy('event_date')
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
                    'event_details' => $event->event_details,
                    'event_type' => $event->event_type,
                    'event_date' => $event->event_date->format('Y-m-d'),
                ];
            }

            // Calculate total events
            $totalEvents = $events->count();

            return response()->json([
                'success' => true,
                'message' => 'Calendar view retrieved successfully',
                'data' => [
                    'year' => $year,
                    'total_events' => $totalEvents,
                    'events_by_month' => $eventsByMonth,
                ],
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'An error occurred while retrieving calendar view: ' . $e->getMessage(),
            ], 500);
        }
    }
}

