<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\View\View;

class StaffBirthdayController extends Controller
{
    /**
     * Display the staff birthdays page.
     */
    public function index(): View
    {
        // Empty array - no static data
        $staff = [];

        return view('staff.birthday', compact('staff'));
    }

    /**
     * Export staff birthdays to Excel, CSV, or PDF
     */
    public function export(Request $request, string $format)
    {
        $staff = $this->getStaffData();
        
        switch ($format) {
            case 'excel':
                return $this->exportExcel($staff);
            case 'csv':
                return $this->exportCSV($staff);
            case 'pdf':
                return $this->exportPDF($staff);
            default:
                return redirect()->route('staff.birthday')
                    ->with('error', 'Invalid export format!');
        }
    }

    /**
     * Get staff data (empty for now)
     */
    private function getStaffData()
    {
        return [];
    }

    /**
     * Export to Excel (CSV format for Excel compatibility)
     */
    private function exportExcel($staff)
    {
        $filename = 'staff_birthdays_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'application/vnd.ms-excel',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($staff) {
            $file = fopen('php://output', 'w');
            
            // Add BOM for Excel UTF-8 compatibility
            fprintf($file, chr(0xEF).chr(0xBB).chr(0xBF));
            
            // Add headers
            fputcsv($file, ['EMP Code', 'Name', 'Father/Husband', 'Birthday', 'Status', 'Wish']);
            
            // Add data rows
            foreach ($staff as $member) {
                fputcsv($file, [
                    $member['emp_code'],
                    $member['name'],
                    $member['father_husband'],
                    $member['birthday'],
                    $member['status'],
                    $member['wish'],
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to CSV
     */
    private function exportCSV($staff)
    {
        $filename = 'staff_birthdays_' . date('Y-m-d_His') . '.csv';
        
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ];

        $callback = function() use ($staff) {
            $file = fopen('php://output', 'w');
            
            // Add headers
            fputcsv($file, ['EMP Code', 'Name', 'Father/Husband', 'Birthday', 'Status', 'Wish']);
            
            // Add data rows
            foreach ($staff as $member) {
                fputcsv($file, [
                    $member['emp_code'],
                    $member['name'],
                    $member['father_husband'],
                    $member['birthday'],
                    $member['status'],
                    $member['wish'],
                ]);
            }
            
            fclose($file);
        };

        return response()->stream($callback, 200, $headers);
    }

    /**
     * Export to PDF
     */
    private function exportPDF($staff)
    {
        $html = view('staff.birthday-pdf', compact('staff'))->render();
        
        return response($html)
            ->header('Content-Type', 'text/html');
    }
}

