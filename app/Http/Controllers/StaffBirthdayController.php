<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\StaffNotification;
use App\Models\GeneralSetting;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

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
     * Print staff birthdays list (dedicated print page)
     */
    public function print(Request $request): View
    {
        $staff = $this->getStaffData();
        $settings = GeneralSetting::getSettings();

        return view('staff.birthday-print', compact('staff', 'settings'));
    }

    /**
     * Print birthday card for a staff member.
     */
    public function printBirthdayCard(Staff $staff): View
    {
        return view('staff.birthday-card-print', [
            'staff' => $staff,
            'printedAt' => now()->format('d-m-Y H:i'),
            'autoPrint' => request()->get('auto_print'),
        ]);
    }

    /**
     * Send birthday wish notification to a single staff member (in-app notification for teacher app).
     * POST /staff/birthday/{staff}/wish
     */
    public function wish(Request $request, Staff $staff): JsonResponse
    {
        // Allow admin/staff session only
        if (!Auth::guard('admin')->check() && !Auth::guard('staff')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $title = $request->input('title') ?: 'Happy Birthday!';
        $message = $request->input('message') ?: ('Happy Birthday ' . ($staff->name ?? 'Staff') . '! 🎉');

        $creatorType = Auth::guard('admin')->check() ? 'admin' : 'staff';
        $creatorId = Auth::guard('admin')->check()
            ? (Auth::guard('admin')->user()->id ?? null)
            : (Auth::guard('staff')->user()->id ?? null);

        StaffNotification::create([
            'staff_id' => $staff->id,
            'title' => $title,
            'message' => $message,
            'data' => [
                'type' => 'birthday_wish',
                'staff_id' => $staff->id,
            ],
            'created_by_type' => $creatorType,
            'created_by_id' => $creatorId,
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Wish sent successfully',
        ]);
    }

    /**
     * Send birthday wishes to multiple staff members (in-app notifications).
     * POST /staff/birthday/wish-all
     * Body: { staff_ids?: [1,2,3], title?: string, message?: string }
     */
    public function wishAll(Request $request): JsonResponse
    {
        if (!Auth::guard('admin')->check() && !Auth::guard('staff')->check()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $validated = $request->validate([
            'staff_ids' => ['nullable', 'array'],
            'staff_ids.*' => ['integer'],
            'title' => ['nullable', 'string', 'max:255'],
            'message' => ['nullable', 'string', 'max:2000'],
        ]);

        $title = $validated['title'] ?? 'Happy Birthday!';
        $messageTemplate = $validated['message'] ?? 'Happy Birthday {name}! 🎉';

        $creatorType = Auth::guard('admin')->check() ? 'admin' : 'staff';
        $creatorId = Auth::guard('admin')->check()
            ? (Auth::guard('admin')->user()->id ?? null)
            : (Auth::guard('staff')->user()->id ?? null);

        $query = Staff::query()->whereNotNull('birthday');

        // If IDs provided, target only those. Otherwise, target "today birthdays".
        if (!empty($validated['staff_ids'])) {
            $query->whereIn('id', $validated['staff_ids']);
        } else {
            $today = Carbon::today();
            $query->whereRaw("DATE_FORMAT(birthday, '%m-%d') = ?", [$today->format('m-d')]);
        }

        $members = $query->get(['id', 'name']);

        if ($members->isEmpty()) {
            return response()->json([
                'success' => false,
                'message' => 'No staff found to send wishes.',
            ], 200);
        }

        $rows = $members->map(function (Staff $s) use ($title, $messageTemplate, $creatorType, $creatorId) {
            $name = $s->name ?: 'Staff';
            $message = str_replace('{name}', $name, $messageTemplate);

            return [
                'staff_id' => $s->id,
                'title' => $title,
                'message' => $message,
                'data' => json_encode([
                    'type' => 'birthday_wish',
                    'staff_id' => $s->id,
                ]),
                'read_at' => null,
                'created_by_type' => $creatorType,
                'created_by_id' => $creatorId,
                'created_at' => now(),
                'updated_at' => now(),
            ];
        })->values()->all();

        DB::table('staff_notifications')->insert($rows);

        return response()->json([
            'success' => true,
            'message' => 'Wishes sent successfully',
            'data' => [
                'count' => count($rows),
            ],
        ]);
    }

    /**
     * Get staff data - only show staff with birthdays today
     */
    private function getStaffData()
    {
        $today = Carbon::today();
        $todayMonth = $today->month;
        $todayDay = $today->day;

        // Get all staff with birthdays and filter to only today's birthdays
        $staffMembers = Staff::whereNotNull('birthday')
            ->get()
            ->filter(function (Staff $member) use ($today, $todayMonth, $todayDay) {
                if (!$member->birthday) {
                    return false;
                }
                
                $birthday = Carbon::parse($member->birthday);
                // Check if month and day match today (ignoring year)
                return $birthday->month == $todayMonth && $birthday->day == $todayDay;
            })
            ->values();

        return $staffMembers->map(function (Staff $member) use ($today) {
            $birthday = $member->birthday ? Carbon::parse($member->birthday) : null;
            $status = 'Today'; // All shown are today's birthdays
            $wish = 'Sent';
            $birthdayCard = 'Sent';

            return [
                'id' => $member->id,
                'emp_code' => $member->emp_id ?? 'N/A',
                'name' => $member->name ?? 'N/A',
                'father_husband' => $member->father_husband_name ?? 'N/A',
                'campus' => $member->campus ?? 'N/A',
                'designation' => $member->designation ?? 'N/A',
                'birthday' => $member->birthday ? $member->birthday->format('Y-m-d') : null,
                'status' => $status,
                'birthday_card' => $birthdayCard,
                'wish' => $wish,
                'picture' => $member->photo ? Storage::url($member->photo) : null,
                'phone' => $member->whatsapp ?? $member->phone,
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
            fputcsv($file, ['EMP Code', 'Name', 'Father/Husband', 'Campus', 'Designation', 'Birthday', 'Status', 'Birthday Card', 'Wish']);
            
            // Add data rows
            foreach ($staff as $member) {
                fputcsv($file, [
                    $member['emp_code'],
                    $member['name'],
                    $member['father_husband'],
                    $member['campus'],
                    $member['designation'],
                    $member['birthday'],
                    $member['status'],
                    $member['birthday_card'],
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
            fputcsv($file, ['EMP Code', 'Name', 'Father/Husband', 'Campus', 'Designation', 'Birthday', 'Status', 'Birthday Card', 'Wish']);
            
            // Add data rows
            foreach ($staff as $member) {
                fputcsv($file, [
                    $member['emp_code'],
                    $member['name'],
                    $member['father_husband'],
                    $member['campus'],
                    $member['designation'],
                    $member['birthday'],
                    $member['status'],
                    $member['birthday_card'],
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

