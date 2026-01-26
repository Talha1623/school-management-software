<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class StaffBirthdayController extends Controller
{
    /**
     * Display the staff birthdays page.
     */
    public function index(): View
    {
        $staff = $this->getStaffData();

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
        $today = Carbon::today();

        $staffMembers = Staff::whereNotNull('birthday')
            ->orderByRaw("DATE_FORMAT(birthday, '%m-%d') asc")
            ->get();

        return $staffMembers->map(function (Staff $member) use ($today) {
            $birthday = $member->birthday ? Carbon::parse($member->birthday) : null;
            $status = 'N/A';
            $wish = 'Pending';

            if ($birthday) {
                $birthdayThisYear = $birthday->copy()->year($today->year);
                if ($birthdayThisYear->isSameDay($today)) {
                    $status = 'Today';
                    $wish = 'Sent';
                } elseif ($birthdayThisYear->isAfter($today)) {
                    $status = 'Upcoming';
                } else {
                    $status = 'Past';
                }
            }

            return [
                'emp_code' => $member->emp_id ?? 'N/A',
                'name' => $member->name ?? 'N/A',
                'father_husband' => $member->father_husband_name ?? 'N/A',
                'birthday' => $member->birthday ? $member->birthday->format('Y-m-d') : null,
                'status' => $status,
                'wish' => $wish,
                'picture' => $member->photo ? Storage::url($member->photo) : null,
            ];
        })->values();
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

