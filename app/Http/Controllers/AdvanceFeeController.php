<?php

namespace App\Http\Controllers;

use App\Models\AdvanceFee;
use App\Models\Student;
use App\Models\ParentAccount;
use App\Models\GeneralSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AdvanceFeeController extends Controller
{
    /**
     * Display a listing of advance fees.
     */
    public function index(Request $request): View
    {
        // Ensure all existing parent accounts have AdvanceFee records
        // This syncs Parent Accounts List with Manage Advance Fee
        $parents = ParentAccount::select('id', 'name', 'email', 'phone', 'id_card_number')->get();
        foreach ($parents as $parent) {
            // Use firstOrCreate to avoid duplicates, but also update if parent info changed
            $advanceFee = AdvanceFee::firstOrCreate(
                ['parent_id' => (string) $parent->id],
                [
                    'name' => $parent->name,
                    'email' => $parent->email,
                    'phone' => $parent->phone,
                    'id_card_number' => $parent->id_card_number,
                    'available_credit' => 0,
                    'increase' => 0,
                    'decrease' => 0,
                    'childs' => 0,
                ]
            );
            
            // Update parent info if it changed (but preserve available_credit, increase, decrease)
            if ($advanceFee->name !== $parent->name || 
                $advanceFee->email !== $parent->email || 
                $advanceFee->phone !== $parent->phone || 
                $advanceFee->id_card_number !== $parent->id_card_number) {
                $advanceFee->update([
                    'name' => $parent->name,
                    'email' => $parent->email,
                    'phone' => $parent->phone,
                    'id_card_number' => $parent->id_card_number,
                ]);
            }
        }
        
        // Query only non-deleted records (exclude soft-deleted if column exists)
        $query = AdvanceFee::query();
        if (Schema::hasColumn('advance_fees', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }
        
        // Get all existing parent IDs for filtering
        $existingParentIds = ParentAccount::pluck('id')->map(function($id) {
            return (string) $id;
        })->toArray();
        
        // Get unique parent_ids (to avoid duplicates)
        // For each parent_id, get only the latest record
        $uniqueParentIdsQuery = AdvanceFee::whereNotNull('parent_id')
            ->where('parent_id', '!=', '')
            ->whereIn('parent_id', $existingParentIds);
        
        if (Schema::hasColumn('advance_fees', 'deleted_at')) {
            $uniqueParentIdsQuery->whereNull('deleted_at');
        }
        
        $uniqueParentIds = $uniqueParentIdsQuery
            ->select('parent_id', \DB::raw('MAX(id) as max_id'))
            ->groupBy('parent_id')
            ->pluck('max_id')
            ->toArray();
        
        // Show advance fee records that are:
        // 1. Latest record for each parent_id (from Parent Accounts List), OR
        // 2. Have students with matching id_card_number (from bulk upload) and no parent_id
        $query->where(function($q) use ($uniqueParentIds, $existingParentIds) {
            // Latest record for each parent_id
            $q->whereIn('id', $uniqueParentIds)
              // OR id_card_number has at least one existing student (from bulk upload) and no parent_id
              ->orWhere(function($subQ) use ($existingParentIds) {
                  $subQ->where(function($cardQ) {
                      $cardQ->whereNull('parent_id')
                            ->orWhere('parent_id', '=', '');
                  })
                  ->whereExists(function($studentQuery) {
                      $studentQuery->select(\DB::raw(1))
                          ->from('students')
                          ->whereColumn('students.father_id_card', 'advance_fees.id_card_number')
                          ->whereNotNull('advance_fees.id_card_number')
                          ->where('advance_fees.id_card_number', '!=', '');
                  });
              });
        });
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(parent_id) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(email) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(phone) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(id_card_number) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $advanceFees = $query->orderBy('name')->paginate($perPage)->withQueryString();
        
        // Calculate children count dynamically for each advance fee
        foreach ($advanceFees as $advanceFee) {
            $childrenCount = 0;
            
            // Count children by parent_id (only if parent exists)
            if (!empty($advanceFee->parent_id) && in_array($advanceFee->parent_id, $existingParentIds)) {
                $childrenCount += Student::where('parent_account_id', $advanceFee->parent_id)->count();
            }
            
            // Count children by id_card_number (avoid double counting)
            if (!empty($advanceFee->id_card_number)) {
                $childrenByCard = Student::where('father_id_card', $advanceFee->id_card_number);
                if (!empty($advanceFee->parent_id) && in_array($advanceFee->parent_id, $existingParentIds)) {
                    // Exclude students already counted by parent_id
                    $childrenByCard->where('parent_account_id', '!=', $advanceFee->parent_id);
                }
                $childrenCount += $childrenByCard->count();
            }
            
            // Set children count dynamically
            $advanceFee->childs = $childrenCount;
        }
        
        return view('accounting.manage-advance-fee', compact('advanceFees'));
    }

    /**
     * Print advance fee records (dedicated print page)
     */
    public function print(Request $request): View
    {
        // Query only non-deleted records (exclude soft-deleted if column exists)
        $query = AdvanceFee::query();
        if (Schema::hasColumn('advance_fees', 'deleted_at')) {
            $query->whereNull('deleted_at');
        }

        // Get all existing parent IDs for filtering
        $existingParentIds = ParentAccount::pluck('id')->map(function ($id) {
            return (string) $id;
        })->toArray();

        // Get unique parent_ids (to avoid duplicates) - for each parent_id, get only the latest record
        $uniqueParentIdsQuery = AdvanceFee::whereNotNull('parent_id')
            ->where('parent_id', '!=', '')
            ->whereIn('parent_id', $existingParentIds);

        if (Schema::hasColumn('advance_fees', 'deleted_at')) {
            $uniqueParentIdsQuery->whereNull('deleted_at');
        }

        $uniqueParentIds = $uniqueParentIdsQuery
            ->select('parent_id', \DB::raw('MAX(id) as max_id'))
            ->groupBy('parent_id')
            ->pluck('max_id')
            ->toArray();

        // Same visibility rules as index()
        $query->where(function ($q) use ($uniqueParentIds, $existingParentIds) {
            $q->whereIn('id', $uniqueParentIds)
                ->orWhere(function ($subQ) {
                    $subQ->where(function ($cardQ) {
                        $cardQ->whereNull('parent_id')
                            ->orWhere('parent_id', '=', '');
                    })
                        ->whereExists(function ($studentQuery) {
                            $studentQuery->select(\DB::raw(1))
                                ->from('students')
                                ->whereColumn('students.father_id_card', 'advance_fees.id_card_number')
                                ->whereNotNull('advance_fees.id_card_number')
                                ->where('advance_fees.id_card_number', '!=', '');
                        });
                });
        });

        // Search (same as index)
        if ($request->filled('search')) {
            $search = trim((string) $request->get('search'));
            if ($search !== '') {
                $searchLower = strtolower($search);
                $query->where(function ($q) use ($searchLower) {
                    $q->whereRaw('LOWER(parent_id) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(email) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(phone) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(id_card_number) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }

        $advanceFees = $query->orderBy('name')->get();

        // Calculate children count dynamically for print
        foreach ($advanceFees as $advanceFee) {
            $childrenCount = 0;

            if (!empty($advanceFee->parent_id) && in_array($advanceFee->parent_id, $existingParentIds)) {
                $childrenCount += Student::where('parent_account_id', $advanceFee->parent_id)->count();
            }

            if (!empty($advanceFee->id_card_number)) {
                $childrenByCard = Student::where('father_id_card', $advanceFee->id_card_number);
                if (!empty($advanceFee->parent_id) && in_array($advanceFee->parent_id, $existingParentIds)) {
                    $childrenByCard->where('parent_account_id', '!=', $advanceFee->parent_id);
                }
                $childrenCount += $childrenByCard->count();
            }

            $advanceFee->childs = $childrenCount;
        }

        $settings = GeneralSetting::getSettings();

        return view('accounting.manage-advance-fee-print', [
            'advanceFees' => $advanceFees,
            'settings' => $settings,
            'printedAt' => now()->format('d M Y, h:i A'),
        ]);
    }

    /**
     * Store a newly created advance fee.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'parent_id' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'id_card_number' => ['nullable', 'string', 'max:255'],
            'available_credit' => ['nullable', 'numeric', 'min:0'],
            'increase' => ['nullable', 'numeric', 'min:0'],
            'decrease' => ['nullable', 'numeric', 'min:0'],
            'childs' => ['nullable', 'integer', 'min:0'],
        ]);

        // Calculate available credit if increase/decrease is provided
        if (isset($validated['increase']) && $validated['increase'] > 0) {
            $validated['available_credit'] = ($validated['available_credit'] ?? 0) + $validated['increase'];
        }
        if (isset($validated['decrease']) && $validated['decrease'] > 0) {
            $validated['available_credit'] = max(0, ($validated['available_credit'] ?? 0) - $validated['decrease']);
        }

        AdvanceFee::create($validated);

        return redirect()
            ->route('accounting.manage-advance-fee.index')
            ->with('success', 'Advance fee record created successfully!');
    }

    /**
     * Show the specified advance fee for editing.
     */
    public function show(AdvanceFee $advanceFee)
    {
        return response()->json($advanceFee);
    }

    /**
     * Get connected students for the given advance fee record.
     */
    public function connectedStudents(AdvanceFee $advanceFee)
    {
        $students = collect();

        if (!empty($advanceFee->parent_id) && ctype_digit((string) $advanceFee->parent_id)) {
            $parentAccount = ParentAccount::find((int) $advanceFee->parent_id);
            if ($parentAccount) {
                $students = $students->merge($parentAccount->students);
            }
        }

        if (!empty($advanceFee->id_card_number)) {
            $students = $students->merge(
                Student::where('father_id_card', $advanceFee->id_card_number)->get()
            );
        }

        $students = $students->unique('id')->values();

        return response()->json([
            'students' => $students->map(function ($student) {
                return [
                    'id' => $student->id,
                    'student_name' => $student->student_name,
                    'student_code' => $student->student_code,
                    'class' => $student->class,
                    'section' => $student->section,
                    'campus' => $student->campus,
                ];
            }),
        ]);
    }

    /**
     * Update the specified advance fee.
     */
    public function update(Request $request, AdvanceFee $advanceFee): RedirectResponse
    {
        $validated = $request->validate([
            'parent_id' => ['nullable', 'string', 'max:255'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'id_card_number' => ['nullable', 'string', 'max:255'],
            'available_credit' => ['nullable', 'numeric', 'min:0'],
            'increase' => ['nullable', 'numeric', 'min:0'],
            'decrease' => ['nullable', 'numeric', 'min:0'],
            'childs' => ['nullable', 'integer', 'min:0'],
        ]);

        // Handle increase/decrease
        $currentCredit = $advanceFee->available_credit;
        if (isset($validated['increase']) && $validated['increase'] > 0) {
            $validated['available_credit'] = $currentCredit + $validated['increase'];
        }
        if (isset($validated['decrease']) && $validated['decrease'] > 0) {
            $validated['available_credit'] = max(0, $currentCredit - $validated['decrease']);
        }

        $advanceFee->update($validated);

        return redirect()
            ->route('accounting.manage-advance-fee.index')
            ->with('success', 'Advance fee record updated successfully!');
    }

    /**
     * Remove the specified advance fee.
     */
    public function destroy(AdvanceFee $advanceFee): RedirectResponse
    {
        $advanceFee->delete();

        return redirect()
            ->route('accounting.manage-advance-fee.index')
            ->with('success', 'Advance fee record deleted successfully!');
    }

    /**
     * Export advance fees to Excel, CSV, or PDF
     */
    public function export(Request $request, string $format)
    {
        // Query only non-deleted records for export
        $query = AdvanceFee::query();
        
        // Get all existing parent IDs for filtering
        $existingParentIds = ParentAccount::pluck('id')->map(function($id) {
            return (string) $id;
        })->toArray();
        
        // Show only manually added advance fee records (same logic as index)
        $query->where(function($q) use ($existingParentIds) {
            // Either parent_id exists in parent_accounts table (manually linked)
            $q->where(function($subQ) use ($existingParentIds) {
                $subQ->whereIn('parent_id', $existingParentIds)
                      ->whereNotNull('parent_id')
                      ->where('parent_id', '!=', '');
            })
              // OR id_card_number has at least one existing student (manually added via id_card)
              ->orWhereExists(function($subQuery) {
                  $subQuery->select(\DB::raw(1))
                      ->from('students')
                      ->whereColumn('students.father_id_card', 'advance_fees.id_card_number')
                      ->whereNotNull('advance_fees.id_card_number')
                      ->where('advance_fees.id_card_number', '!=', '');
              });
        });
        
        // Apply search filter if present
        if ($request->has('search') && $request->search) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(parent_id) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(email) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(phone) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(id_card_number) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $advanceFees = $query->orderBy('name')->get();
        
        // Calculate children count dynamically for each advance fee in exports
        foreach ($advanceFees as $advanceFee) {
            $childrenCount = 0;
            
            // Count children by parent_id
            if (!empty($advanceFee->parent_id)) {
                $childrenCount += Student::where('parent_account_id', $advanceFee->parent_id)->count();
            }
            
            // Count children by id_card_number (avoid double counting)
            if (!empty($advanceFee->id_card_number)) {
                $childrenByCard = Student::where('father_id_card', $advanceFee->id_card_number);
                if (!empty($advanceFee->parent_id)) {
                    // Exclude students already counted by parent_id
                    $childrenByCard->where('parent_account_id', '!=', $advanceFee->parent_id);
                }
                $childrenCount += $childrenByCard->count();
            }
            
            // Set children count dynamically
            $advanceFee->childs = $childrenCount;
        }
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($advanceFees);
            case 'csv':
                return $this->exportCSV($advanceFees);
            case 'pdf':
                return $this->exportPDF($advanceFees);
            default:
                return redirect()->route('accounting.manage-advance-fee.index')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($advanceFees)
    {
        $filename = 'advance_fees_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($advanceFees) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            fputcsv($file, ['ID', 'Parent ID', 'Name', 'Email', 'Phone', 'ID Card Number', 'Available Credit', 'Increase', 'Decrease', 'Childs', 'Created At']);
            
            foreach ($advanceFees as $advanceFee) {
                fputcsv($file, [
                    $advanceFee->id,
                    $advanceFee->parent_id ?? '',
                    $advanceFee->name,
                    $advanceFee->email ?? '',
                    $advanceFee->phone ?? '',
                    $advanceFee->id_card_number ?? '',
                    $advanceFee->available_credit,
                    $advanceFee->increase,
                    $advanceFee->decrease,
                    $advanceFee->childs,
                    $advanceFee->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to CSV
     */
    private function exportCSV($advanceFees)
    {
        $filename = 'advance_fees_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($advanceFees) {
            $file = fopen('php://output', 'w');
            
            fputcsv($file, ['ID', 'Parent ID', 'Name', 'Email', 'Phone', 'ID Card Number', 'Available Credit', 'Increase', 'Decrease', 'Childs', 'Created At']);
            
            foreach ($advanceFees as $advanceFee) {
                fputcsv($file, [
                    $advanceFee->id,
                    $advanceFee->parent_id ?? '',
                    $advanceFee->name,
                    $advanceFee->email ?? '',
                    $advanceFee->phone ?? '',
                    $advanceFee->id_card_number ?? '',
                    $advanceFee->available_credit,
                    $advanceFee->increase,
                    $advanceFee->decrease,
                    $advanceFee->childs,
                    $advanceFee->created_at->format('Y-m-d H:i:s'),
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($advanceFees)
    {
        $html = view('accounting.manage-advance-fee-pdf', compact('advanceFees'))->render();
        
        return response($html)
            ->header('Content-Type', 'text/html');
    }
}

