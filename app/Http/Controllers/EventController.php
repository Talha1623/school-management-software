<?php

namespace App\Http\Controllers;

use App\Models\Event;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class EventController extends Controller
{
    /**
     * Display a listing of events.
     */
    public function index(Request $request): View
    {
        $query = Event::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(event_title) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(event_details) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(event_type) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $events = $query->orderBy('event_date', 'desc')->paginate($perPage)->withQueryString();
        
        // Determine which view to return based on the route
        $viewName = 'events.manage';
        if ($request->routeIs('academic-calendar.manage-events')) {
            $viewName = 'academic-calendar.manage-events';
        }
        
        return view($viewName, compact('events'));
    }

    /**
     * Store a newly created event.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'event_title' => ['required', 'string', 'max:255'],
            'event_details' => ['nullable', 'string'],
            'event_type' => ['nullable', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
        ]);

        Event::create($validated);

        // Redirect based on where the request came from
        $redirectRoute = $request->has('from_calendar') ? 'academic-calendar.manage-events' : 'events.manage';
        
        return redirect()
            ->route($redirectRoute)
            ->with('success', 'Event created successfully!');
    }

    /**
     * Show the specified event for editing.
     */
    public function show(Event $event)
    {
        return response()->json($event);
    }

    /**
     * Update the specified event.
     */
    public function update(Request $request, Event $event): RedirectResponse
    {
        $validated = $request->validate([
            'event_title' => ['required', 'string', 'max:255'],
            'event_details' => ['nullable', 'string'],
            'event_type' => ['nullable', 'string', 'max:255'],
            'event_date' => ['required', 'date'],
        ]);

        $event->update($validated);

        // Redirect based on where the request came from
        $redirectRoute = $request->has('from_calendar') ? 'academic-calendar.manage-events' : 'events.manage';
        
        return redirect()
            ->route($redirectRoute)
            ->with('success', 'Event updated successfully!');
    }

    /**
     * Remove the specified event.
     */
    public function destroy(Event $event): RedirectResponse
    {
        $event->delete();

        // Redirect based on where the request came from
        $redirectRoute = request()->has('from_calendar') ? 'academic-calendar.manage-events' : 'events.manage';
        
        return redirect()
            ->route($redirectRoute)
            ->with('success', 'Event deleted successfully!');
    }

    /**
     * Export events to Excel, CSV, or PDF
     */
    public function export(Request $request, string $format)
    {
        $query = Event::query();
        
        // Apply search filter if present
        if ($request->has('search') && $request->search) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(event_title) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(event_details) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(event_type) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $events = $query->orderBy('event_date', 'desc')->get();
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($events);
            case 'csv':
                return $this->exportCSV($events);
            case 'pdf':
                return $this->exportPDF($events);
            default:
                return redirect()->route('events.manage')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($events)
    {
        $filename = 'events_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($events) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['ID', 'Event Title', 'Event Details', 'Event Type', 'Event Date', 'Created At']);
            
            foreach ($events as $event) {
                fputcsv($file, [
                    $event->id,
                    $event->event_title,
                    $event->event_details ?? '',
                    $event->event_type ?? '',
                    $event->event_date->format('Y-m-d'),
                    $event->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to CSV
     */
    private function exportCSV($events)
    {
        $filename = 'events_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($events) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['ID', 'Event Title', 'Event Details', 'Event Type', 'Event Date', 'Created At']);
            
            foreach ($events as $event) {
                fputcsv($file, [
                    $event->id,
                    $event->event_title,
                    $event->event_details ?? '',
                    $event->event_type ?? '',
                    $event->event_date->format('Y-m-d'),
                    $event->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($events)
    {
        $html = view('events.manage-pdf', compact('events'))->render();
        
        return response($html)
            ->header('Content-Type', 'text/html');
    }

    /**
     * Display calendar view with events grouped by month
     */
    public function calendarView(Request $request): View
    {
        $year = $request->get('year', date('Y'));
        
        // Get all events for the year
        $events = Event::whereYear('event_date', $year)
            ->orderBy('event_date')
            ->get();
        
        // Group events by month
        $eventsByMonth = [];
        foreach ($events as $event) {
            $month = $event->event_date->format('n'); // 1-12
            if (!isset($eventsByMonth[$month])) {
                $eventsByMonth[$month] = [];
            }
            $eventsByMonth[$month][] = $event;
        }
        
        return view('academic-calendar.view', compact('eventsByMonth', 'year'));
    }
}

