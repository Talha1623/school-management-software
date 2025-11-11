<?php

namespace App\Http\Controllers;

use App\Models\Noticeboard;
use App\Models\ClassModel;
use App\Models\Section;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\Storage;

class NoticeboardController extends Controller
{
    /**
     * Display a listing of noticeboards.
     */
    public function index(Request $request): View
    {
        $query = Noticeboard::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(title) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(notice) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $noticeboards = $query->orderBy('date', 'desc')->paginate($perPage)->withQueryString();

        // Get campuses for dropdown
        $campusesFromClasses = ClassModel::whereNotNull('campus')->distinct()->pluck('campus');
        $campusesFromSections = Section::whereNotNull('campus')->distinct()->pluck('campus');
        $campuses = $campusesFromClasses->merge($campusesFromSections)->unique()->sort()->values();
        
        if ($campuses->isEmpty()) {
            $campuses = collect(['Main Campus', 'Branch Campus 1', 'Branch Campus 2']);
        }

        return view('school.noticeboard', compact('noticeboards', 'campuses'));
    }

    /**
     * Store a newly created noticeboard.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'notice' => ['nullable', 'string'],
            'date' => ['required', 'date'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'show_on' => ['nullable', 'string'],
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            $imagePath = $request->file('image')->store('noticeboards', 'public');
            $validated['image'] = $imagePath;
        }

        // Handle show_on checkboxes
        $showOn = [];
        if ($request->has('show_on_uploads')) {
            $showOn[] = 'uploads';
        }
        if ($request->has('show_on_website')) {
            $showOn[] = 'website';
        }
        if ($request->has('show_on_mobile_app')) {
            $showOn[] = 'mobile_app';
        }
        $validated['show_on'] = implode(',', $showOn);

        Noticeboard::create($validated);

        return redirect()
            ->route('school.noticeboard')
            ->with('success', 'Noticeboard created successfully!');
    }

    /**
     * Update the specified noticeboard.
     */
    public function update(Request $request, Noticeboard $noticeboard): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['nullable', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'notice' => ['nullable', 'string'],
            'date' => ['required', 'date'],
            'image' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif', 'max:2048'],
            'show_on' => ['nullable', 'string'],
        ]);

        // Handle image upload
        if ($request->hasFile('image')) {
            // Delete old image if exists
            if ($noticeboard->image && Storage::disk('public')->exists($noticeboard->image)) {
                Storage::disk('public')->delete($noticeboard->image);
            }
            $imagePath = $request->file('image')->store('noticeboards', 'public');
            $validated['image'] = $imagePath;
        }

        // Handle show_on checkboxes
        $showOn = [];
        if ($request->has('show_on_uploads')) {
            $showOn[] = 'uploads';
        }
        if ($request->has('show_on_website')) {
            $showOn[] = 'website';
        }
        if ($request->has('show_on_mobile_app')) {
            $showOn[] = 'mobile_app';
        }
        $validated['show_on'] = implode(',', $showOn);

        $noticeboard->update($validated);

        return redirect()
            ->route('school.noticeboard')
            ->with('success', 'Noticeboard updated successfully!');
    }

    /**
     * Remove the specified noticeboard.
     */
    public function destroy(Noticeboard $noticeboard): RedirectResponse
    {
        // Delete image if exists
        if ($noticeboard->image && Storage::disk('public')->exists($noticeboard->image)) {
            Storage::disk('public')->delete($noticeboard->image);
        }

        $noticeboard->delete();

        return redirect()
            ->route('school.noticeboard')
            ->with('success', 'Noticeboard deleted successfully!');
    }

    /**
     * Export noticeboards to Excel, CSV, or PDF
     */
    public function export(Request $request, string $format)
    {
        $query = Noticeboard::query();
        
        // Apply search filter if present
        if ($request->has('search') && $request->search) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(title) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(notice) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $noticeboards = $query->orderBy('date', 'desc')->get();
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($noticeboards);
            case 'pdf':
                return $this->exportPDF($noticeboards);
            default:
                return redirect()->route('school.noticeboard')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($noticeboards)
    {
        $filename = 'noticeboards_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($noticeboards) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fputs($file, "\xEF\xBB\xBF");
            
            // Headers
            fputcsv($file, ['#', 'Campus', 'Title', 'Notice', 'Date', 'Show On']);
            
            // Data
            foreach ($noticeboards as $index => $noticeboard) {
                fputcsv($file, [
                    $index + 1,
                    $noticeboard->campus ?? 'N/A',
                    $noticeboard->title,
                    $noticeboard->notice ? (strlen($noticeboard->notice) > 50 ? substr($noticeboard->notice, 0, 50) . '...' : $noticeboard->notice) : 'N/A',
                    $noticeboard->date->format('d M Y'),
                    $noticeboard->show_on ?? 'N/A',
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($noticeboards)
    {
        $html = view('school.noticeboard-pdf', compact('noticeboards'))->render();
        
        // Simple PDF generation (you can use DomPDF or similar package)
        return response($html)
            ->header('Content-Type', 'text/html');
    }
}

