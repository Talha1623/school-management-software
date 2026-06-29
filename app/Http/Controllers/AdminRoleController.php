<?php

namespace App\Http\Controllers;

use App\Models\AdminRole;
use App\Models\Campus;
use App\Models\GeneralSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class AdminRoleController extends Controller
{
    /**
     * Display a listing of admin roles.
     */
    public function index(Request $request): View
    {
        $query = AdminRole::query();
        
        // Search functionality
        if ($request->filled('search')) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(email) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(phone) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(admin_of) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $perPage = $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;
        
        $adminRoles = $query->orderBy('name', 'asc')->paginate($perPage)->withQueryString();

        // Get campuses for dropdown
        $campuses = Campus::orderBy('campus_name', 'asc')->get();

        return view('admin.roles-management', compact('adminRoles', 'campuses'));
    }

    /**
     * Store a newly created admin role.
     */
    public function store(Request $request): RedirectResponse
    {
        AdminRole::ensurePhotoColumn();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:admin_roles,email'],
            'password' => ['required', 'string', 'min:6'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'admin_of' => ['nullable', 'string', 'max:255'],
            'super_admin' => ['nullable', 'boolean'],
        ]);

        $validated['super_admin'] = $request->has('super_admin') ? 1 : 0;

        if ($request->hasFile('photo')) {
            $validated['photo'] = $request->file('photo')->store('admin/photos', 'public');
        } else {
            unset($validated['photo']);
        }

        AdminRole::create($validated);

        return redirect()
            ->route('admin.roles-management')
            ->with('success', 'Admin role created successfully!');
    }

    /**
     * Update the specified admin role.
     */
    public function update(Request $request, AdminRole $adminRole): RedirectResponse
    {
        AdminRole::ensurePhotoColumn();

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:admin_roles,email,' . $adminRole->id],
            'password' => ['nullable', 'string', 'min:6'],
            'photo' => ['nullable', 'image', 'mimes:jpeg,jpg,png,webp', 'max:2048'],
            'admin_of' => ['nullable', 'string', 'max:255'],
            'super_admin' => ['nullable', 'boolean'],
        ]);

        // Plain password only — AdminRole mutator hashes once
        if (empty($validated['password'])) {
            unset($validated['password']);
        }

        $validated['super_admin'] = $request->has('super_admin') ? 1 : 0;

        if ($request->hasFile('photo')) {
            if (! empty($adminRole->photo)) {
                Storage::disk('public')->delete($adminRole->photo);
            }
            $validated['photo'] = $request->file('photo')->store('admin/photos', 'public');
        } else {
            unset($validated['photo']);
        }

        $adminRole->update($validated);

        return redirect()
            ->route('admin.roles-management')
            ->with('success', 'Admin role updated successfully!');
    }

    /**
     * Remove the specified admin role.
     */
    public function destroy(AdminRole $adminRole): RedirectResponse
    {
        if (Schema::hasColumn($adminRole->getTable(), 'photo') && ! empty($adminRole->photo)) {
            Storage::disk('public')->delete($adminRole->photo);
        }

        $adminRole->delete();

        return redirect()
            ->route('admin.roles-management')
            ->with('success', 'Admin role deleted successfully!');
    }

    /**
     * Export admin roles to Excel, CSV, or PDF
     */
    public function export(Request $request, string $format)
    {
        $query = AdminRole::query();
        
        // Apply search filter if present
        if ($request->has('search') && $request->search) {
            $search = trim($request->search);
            if (!empty($search)) {
                $searchLower = strtolower($search);
                $query->where(function($q) use ($search, $searchLower) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(email) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(phone) LIKE ?', ["%{$searchLower}%"])
                      ->orWhereRaw('LOWER(admin_of) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }
        
        $adminRoles = $query->orderBy('name', 'asc')->get();
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($adminRoles);
            case 'pdf':
                return $this->exportPDF($adminRoles);
            default:
                return redirect()->route('admin.roles-management')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($adminRoles)
    {
        $filename = 'admin_roles_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($adminRoles) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for UTF-8
            fputs($file, "\xEF\xBB\xBF");
            
            // Headers
            fputcsv($file, ['#', 'Name', 'Phone', 'Email', 'Admin Of', 'Super Admin']);
            
            // Data
            foreach ($adminRoles as $index => $adminRole) {
                fputcsv($file, [
                    $index + 1,
                    $adminRole->name,
                    $adminRole->phone ?? 'N/A',
                    $adminRole->email,
                    $adminRole->admin_of ?? 'N/A',
                    $adminRole->super_admin ? 'Yes' : 'No',
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($adminRoles)
    {
        $html = view('admin.roles-management-pdf', compact('adminRoles'))->render();
        
        // Simple PDF generation (you can use DomPDF or similar package)
        return response($html)
            ->header('Content-Type', 'text/html');
    }

    /**
     * Print admin roles list in a dedicated print-only page.
     */
    public function print(Request $request): View
    {
        $query = AdminRole::query();

        if ($request->filled('search')) {
            $search = trim((string) $request->get('search'));
            if ($search !== '') {
                $searchLower = strtolower($search);
                $query->where(function ($q) use ($searchLower) {
                    $q->whereRaw('LOWER(name) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(email) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(phone) LIKE ?', ["%{$searchLower}%"])
                        ->orWhereRaw('LOWER(admin_of) LIKE ?', ["%{$searchLower}%"]);
                });
            }
        }

        $adminRoles = $query->orderBy('name', 'asc')->get();
        $settings = GeneralSetting::getSettings();

        return view('admin.roles-management-print', [
            'adminRoles' => $adminRoles,
            'settings' => $settings,
            'printedAt' => now()->format('d M Y, h:i A'),
        ]);
    }
}
