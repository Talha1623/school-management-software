<?php

namespace App\Http\Controllers;

use App\Models\Task;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TaskManagementController extends Controller
{
    /**
     * Display a listing of tasks.
     */
    public function index(Request $request): View
    {
        $query = Task::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(task_title) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(description) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(type) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(assign_to) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $tasks = $query->latest()->paginate($perPage)->withQueryString();
        
        // Summary statistics
        $totalTasks = Task::count();
        $pendingTasks = Task::where(function($q) {
            $q->whereNull('assign_to')->orWhere('assign_to', '');
        })->count();
        $activeTasks = Task::whereNotNull('assign_to')->where('assign_to', '!=', '')->count();
        $completedTasks = 0; // Can be updated when status field is added
        
        return view('task-management', compact('tasks', 'totalTasks', 'pendingTasks', 'activeTasks', 'completedTasks'));
    }

    /**
     * Store a newly created task.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'task_title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['nullable', 'string', 'max:255'],
            'assign_to' => ['nullable', 'string', 'max:255'],
        ]);

        Task::create($validated);

        return redirect()
            ->route('task-management')
            ->with('success', 'Task created successfully!');
    }

    /**
     * Show the specified task for editing.
     */
    public function show(Task $task)
    {
        return response()->json($task);
    }

    /**
     * Update the specified task.
     */
    public function update(Request $request, Task $task): RedirectResponse
    {
        $validated = $request->validate([
            'task_title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'type' => ['nullable', 'string', 'max:255'],
            'assign_to' => ['nullable', 'string', 'max:255'],
        ]);

        $task->update($validated);

        return redirect()
            ->route('task-management')
            ->with('success', 'Task updated successfully!');
    }

    /**
     * Remove the specified task.
     */
    public function destroy(Task $task): RedirectResponse
    {
        $task->delete();

        return redirect()
            ->route('task-management')
            ->with('success', 'Task deleted successfully!');
    }

    /**
     * Delete all tasks.
     */
    public function deleteAll(): RedirectResponse
    {
        Task::truncate();

        return redirect()
            ->route('task-management')
            ->with('success', 'All tasks deleted successfully!');
    }

    /**
     * Export tasks data.
     */
    public function export(Request $request, $format)
    {
        $query = Task::query();
        
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(task_title) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(description) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(type) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(assign_to) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }

        $tasks = $query->latest()->get();

        switch ($format) {
            case 'excel':
                return redirect()->back()->with('info', 'Excel export will be implemented');
                
            case 'csv':
                $filename = 'tasks_' . date('Y-m-d_His') . '.csv';
                $headers = [
                    'Content-Type' => 'text/csv',
                    'Content-Disposition' => "attachment; filename=\"$filename\"",
                ];
                
                $callback = function() use ($tasks) {
                    $file = fopen('php://output', 'w');
                    
                    // Add headers
                    fputcsv($file, [
                        'Task Title', 'Description', 'Type', 'Assign To', 'Created At'
                    ]);
                    
                    // Add data
                    foreach ($tasks as $task) {
                        fputcsv($file, [
                            $task->task_title ?? '',
                            $task->description ?? '',
                            $task->type ?? '',
                            $task->assign_to ?? '',
                            $task->created_at ? $task->created_at->format('Y-m-d H:i:s') : '',
                        ]);
                    }
                    
                    fclose($file);
                };
                
                return response()->stream($callback, 200, $headers);
                
            case 'pdf':
                $html = view('task-management-pdf', compact('tasks'))->render();
                return response($html)
                    ->header('Content-Type', 'text/html');
                
            default:
                return redirect()->back()->with('error', 'Invalid export format');
        }
    }
}

