<?php

namespace App\Http\Controllers;

use App\Models\ManagementExpense;
use App\Models\ExpenseCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ManagementExpenseController extends Controller
{
    /**
     * Display a listing of management expenses.
     */
    public function index(Request $request): View
    {
        $query = ManagementExpense::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(category) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(title) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(description) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(method) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $expenses = $query->orderBy('date', 'desc')->paginate($perPage)->withQueryString();
        
        // Get expense categories for dropdown
        $categories = ExpenseCategory::orderBy('category_name')->get();
        
        return view('expense-management.add', compact('expenses', 'categories'));
    }

    /**
     * Store a newly created management expense.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'method' => ['required', 'string', 'max:255'],
            'invoice_receipt' => ['nullable', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'notify_admin' => ['nullable', 'boolean'],
        ]);

        $validated['notify_admin'] = $request->has('notify_admin') ? true : false;

        ManagementExpense::create($validated);

        return redirect()
            ->route('expense-management.add')
            ->with('success', 'Management expense created successfully!');
    }

    /**
     * Update the specified management expense.
     */
    public function update(Request $request, ManagementExpense $managementExpense): RedirectResponse
    {
        $validated = $request->validate([
            'campus' => ['required', 'string', 'max:255'],
            'category' => ['required', 'string', 'max:255'],
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string'],
            'amount' => ['required', 'numeric', 'min:0'],
            'method' => ['required', 'string', 'max:255'],
            'invoice_receipt' => ['nullable', 'string', 'max:255'],
            'date' => ['required', 'date'],
            'notify_admin' => ['nullable', 'boolean'],
        ]);

        $validated['notify_admin'] = $request->has('notify_admin') ? true : false;

        $managementExpense->update($validated);

        return redirect()
            ->route('expense-management.add')
            ->with('success', 'Management expense updated successfully!');
    }

    /**
     * Remove the specified management expense.
     */
    public function destroy(ManagementExpense $managementExpense): RedirectResponse
    {
        $managementExpense->delete();

        return redirect()
            ->route('expense-management.add')
            ->with('success', 'Management expense deleted successfully!');
    }

    /**
     * Export management expenses to Excel, CSV, or PDF
     */
    public function export(Request $request, string $format)
    {
        $query = ManagementExpense::query();
        
        // Apply search filter if present
        if ($request->has('search') && $request->search) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(campus) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(category) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(title) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(description) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(method) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $expenses = $query->orderBy('date', 'desc')->get();
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($expenses);
            case 'csv':
                return $this->exportCSV($expenses);
            case 'pdf':
                return $this->exportPDF($expenses);
            default:
                return redirect()->route('expense-management.add')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($expenses)
    {
        $filename = 'management_expenses_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($expenses) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['ID', 'Campus', 'Category', 'Title', 'Description', 'Amount', 'Method', 'Invoice/Receipt', 'Date', 'Notify Admin', 'Created At']);
            
            foreach ($expenses as $expense) {
                fputcsv($file, [
                    $expense->id,
                    $expense->campus,
                    $expense->category,
                    $expense->title,
                    $expense->description ?? '',
                    $expense->amount,
                    $expense->method,
                    $expense->invoice_receipt ?? '',
                    $expense->date->format('Y-m-d'),
                    $expense->notify_admin ? 'Yes' : 'No',
                    $expense->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to CSV
     */
    private function exportCSV($expenses)
    {
        $filename = 'management_expenses_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($expenses) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['ID', 'Campus', 'Category', 'Title', 'Description', 'Amount', 'Method', 'Invoice/Receipt', 'Date', 'Notify Admin', 'Created At']);
            
            foreach ($expenses as $expense) {
                fputcsv($file, [
                    $expense->id,
                    $expense->campus,
                    $expense->category,
                    $expense->title,
                    $expense->description ?? '',
                    $expense->amount,
                    $expense->method,
                    $expense->invoice_receipt ?? '',
                    $expense->date->format('Y-m-d'),
                    $expense->notify_admin ? 'Yes' : 'No',
                    $expense->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($expenses)
    {
        $html = view('expense-management.add-pdf', compact('expenses'))->render();
        
        return response($html)
            ->header('Content-Type', 'text/html');
    }
}

