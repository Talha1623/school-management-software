<?php

namespace App\Http\Controllers;

use App\Models\ParentAccount;
use App\Models\Student;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ParentAccountController extends Controller
{
    /**
     * Display a listing of parent accounts.
     */
    public function index(Request $request): View
    {
        $query = ParentAccount::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(email) LIKE ?', ["%{$searchLower}%"])
                      ->orWhere('phone', 'like', "%{$search}%")
                      ->orWhere('whatsapp', 'like', "%{$search}%")
                      ->orWhere('id_card_number', 'like', "%{$search}%")
                      ->orWhereRaw('LOWER(profession) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(address) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $parents = $query->latest()->paginate($perPage)->withQueryString();
        
        return view('parent.manage-access', compact('parents'));
    }

    /**
     * Store a newly created parent account.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:parent_accounts,email'],
            'password' => ['required', 'string', 'min:6'],
            'phone' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'id_card_number' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'profession' => ['nullable', 'string', 'max:255'],
        ]);

        // Password will be automatically hashed by ParentAccount model's setPasswordAttribute
        ParentAccount::create($validated);

        return redirect()
            ->route('parent.manage-access')
            ->with('success', 'Parent account created successfully!');
    }

    /**
     * Update the specified parent account.
     */
    public function update(Request $request, ParentAccount $parent_account): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:parent_accounts,email,' . $parent_account->id],
            'password' => ['nullable', 'string', 'min:6'],
            'phone' => ['nullable', 'string', 'max:20'],
            'whatsapp' => ['nullable', 'string', 'max:20'],
            'id_card_number' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'profession' => ['nullable', 'string', 'max:255'],
        ]);

        // Password will be automatically hashed by ParentAccount model's setPasswordAttribute
        // If password is empty, remove it from update data
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $parent_account->update($validated);

        return redirect()
            ->route('parent.manage-access')
            ->with('success', 'Parent account updated successfully!');
    }

    /**
     * Remove the specified parent account.
     */
    public function destroy(ParentAccount $parent_account): RedirectResponse
    {
        $parent_account->delete();

        return redirect()
            ->route('parent.manage-access')
            ->with('success', 'Parent account deleted successfully!');
    }

    /**
     * Reset password for parent account.
     */
    public function resetPassword(Request $request, ParentAccount $parent_account): RedirectResponse
    {
        $validated = $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        // Password will be automatically hashed by ParentAccount model's setPasswordAttribute
        $parent_account->password = $validated['password'];
        $parent_account->save();

        return redirect()
            ->route('parent.manage-access')
            ->with('success', 'Password reset successfully for ' . $parent_account->name . '!');
    }

    /**
     * Connect student to parent account.
     */
    public function connectStudent(Request $request, ParentAccount $parent_account): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
        ]);

        $student = Student::findOrFail($validated['student_id']);

        // Update student's father information to match parent account
        $student->father_name = $parent_account->name;
        $student->father_email = $parent_account->email;
        $student->father_phone = $parent_account->phone;
        if ($parent_account->id_card_number) {
            $student->father_id_card = $parent_account->id_card_number;
        }
        // Link student to parent account
        $student->parent_account_id = $parent_account->id;
        $student->save();

        return redirect()
            ->route('parent.manage-access')
            ->with('success', 'Student ' . $student->student_name . ' connected successfully to ' . $parent_account->name . '!');
    }

    /**
     * Disconnect student from parent account.
     */
    public function disconnectStudent(Request $request, ParentAccount $parent_account): RedirectResponse
    {
        $validated = $request->validate([
            'student_id' => ['required', 'exists:students,id'],
        ]);

        $student = Student::findOrFail($validated['student_id']);

        // Verify student is connected to this parent
        if ($student->parent_account_id != $parent_account->id) {
            return redirect()
                ->route('parent.manage-access')
                ->with('error', 'Student is not connected to this parent account.');
        }

        // Disconnect student
        $student->parent_account_id = null;
        $student->save();

        return redirect()
            ->route('parent.manage-access')
            ->with('success', 'Student ' . $student->student_name . ' disconnected successfully from ' . $parent_account->name . '!');
    }

    /**
     * Get all students for connection (AJAX).
     */
    public function getAllStudentsForConnect(Request $request)
    {
        $parentId = $request->get('parent_id');
        
        $students = Student::select('id', 'student_code', 'student_name', 'gr_number', 'class', 'section', 'father_name', 'father_email', 'father_phone', 'parent_account_id')
            ->orderBy('student_name')
            ->get()
            ->map(function($student) {
                return [
                    'id' => $student->id,
                    'code' => $student->student_code ?? 'N/A',
                    'name' => $student->student_name,
                    'gr_number' => $student->gr_number ?? 'N/A',
                    'class' => $student->class ?? 'N/A',
                    'section' => $student->section ?? 'N/A',
                    'father_name' => $student->father_name ?? 'N/A',
                    'father_email' => $student->father_email ?? 'N/A',
                    'parent_account_id' => $student->parent_account_id,
                ];
            });
        
        // Get connected students for this parent
        $connectedStudents = [];
        if ($parentId) {
            $connectedStudents = Student::where('parent_account_id', $parentId)
                ->select('id', 'student_code', 'student_name', 'class', 'section')
                ->get()
                ->map(function($student) {
                    return [
                        'id' => $student->id,
                        'code' => $student->student_code ?? 'N/A',
                        'name' => $student->student_name,
                        'class' => $student->class ?? 'N/A',
                        'section' => $student->section ?? 'N/A',
                    ];
                });
        }
        
        return response()->json([
            'students' => $students,
            'connected_students' => $connectedStudents
        ]);
    }

    /**
     * Delete all parent accounts.
     * 
     * Note: Before truncating, we need to handle foreign key constraints.
     * The students table has a foreign key reference to parent_accounts.
     * We'll temporarily disable foreign key checks, truncate, then re-enable them.
     */
    public function deleteAll(Request $request): RedirectResponse
    {
        try {
            // Start database transaction
            DB::beginTransaction();

            // First, set parent_account_id to null in students table
            // This removes the foreign key constraint issue
            Student::whereNotNull('parent_account_id')->update(['parent_account_id' => null]);

            // Also handle parent_complaints table if it has foreign key
            DB::table('parent_complaints')
                ->whereNotNull('parent_account_id')
                ->update(['parent_account_id' => null]);

            // Temporarily disable foreign key checks to allow truncate
            DB::statement('SET FOREIGN_KEY_CHECKS=0;');

            // Now truncate parent_accounts table (safe after disabling foreign key checks)
            ParentAccount::truncate();

            // Re-enable foreign key checks
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');

            // Commit transaction
            DB::commit();

            return redirect()
                ->route('parent.manage-access')
                ->with('success', 'All parent accounts deleted successfully!');
        } catch (\Exception $e) {
            // Rollback transaction on error
            DB::rollBack();

            // Make sure to re-enable foreign key checks even on error
            try {
                DB::statement('SET FOREIGN_KEY_CHECKS=1;');
            } catch (\Exception $fkError) {
                // Ignore if this fails
            }

            Log::error('Delete All Parent Accounts Error: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
            ]);

            return redirect()
                ->route('parent.manage-access')
                ->with('error', 'Failed to delete all parent accounts: ' . $e->getMessage());
        }
    }

    /**
     * Export parent accounts to Excel, CSV, or PDF
     */
    public function export(Request $request, string $format)
    {
        $query = ParentAccount::query();
        
        // Apply search filter if present
        if ($request->has('search') && $request->search) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('whatsapp', 'like', "%{$search}%")
                  ->orWhere('id_card_number', 'like', "%{$search}%")
                  ->orWhere('profession', 'like', "%{$search}%")
                  ->orWhere('address', 'like', "%{$search}%");
            });
        }
        
        $parents = $query->latest()->get();
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($parents);
            case 'csv':
                return $this->exportCSV($parents);
            case 'pdf':
                return $this->exportPDF($parents);
            default:
                return redirect()->route('parent.manage-access')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($parents)
    {
        $filename = 'parent_accounts_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($parents) {
            $file = fopen('php://output', 'w');
            
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['ID', 'Name', 'Email', 'Phone', 'WhatsApp', 'ID Card Number', 'Address', 'Profession', 'Created At']);
            
            foreach ($parents as $parent) {
                fputcsv($file, [
                    $parent->id,
                    $parent->name,
                    $parent->email,
                    $parent->phone ?? 'N/A',
                    $parent->whatsapp ?? 'N/A',
                    $parent->id_card_number ?? 'N/A',
                    $parent->address ?? 'N/A',
                    $parent->profession ?? 'N/A',
                    $parent->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to CSV
     */
    private function exportCSV($parents)
    {
        $filename = 'parent_accounts_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($parents) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['ID', 'Name', 'Email', 'Phone', 'WhatsApp', 'ID Card Number', 'Address', 'Profession', 'Created At']);
            
            foreach ($parents as $parent) {
                fputcsv($file, [
                    $parent->id,
                    $parent->name,
                    $parent->email,
                    $parent->phone ?? 'N/A',
                    $parent->whatsapp ?? 'N/A',
                    $parent->id_card_number ?? 'N/A',
                    $parent->address ?? 'N/A',
                    $parent->profession ?? 'N/A',
                    $parent->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($parents)
    {
        $html = view('parent.manage-access-pdf', compact('parents'))->render();
        
        return response($html)
            ->header('Content-Type', 'text/html');
    }
}

