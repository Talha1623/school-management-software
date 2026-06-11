<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\Campus;
use App\Models\Subject;
use App\Models\Timetable;
use App\Models\SalarySetting;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class StaffAttendanceController extends Controller
{
    /**
     * Display staff attendance page with filters.
     */
    public function index(Request $request): View
    {
        $campus = $request->get('campus');
        $staffCategory = $request->get('staff_category');
        $type = $request->get('type');
        $date = $request->get('date', date('Y-m-d'));

        // Get staff based on filters
        $staffQuery = Staff::query();

        if ($campus) {
            $staffQuery->where('campus', $campus);
        }

        if ($staffCategory) {
            $staffQuery->where('designation', $staffCategory);
        }

        if ($type === 'Subject Attendance') {
            $staffQuery->where('salary_type', 'lecture');
        } elseif ($type) {
            $staffQuery->where(function ($query) {
                $query->whereNull('salary_type')
                    ->orWhere('salary_type', '!=', 'lecture');
            });
        }

        $staffList = $staffQuery->orderBy('name', 'asc')->get();
        
        // Filter per hour teachers: only show if they have classes scheduled on the selected date
        if ($date && $staffList->isNotEmpty()) {
            $dayName = Carbon::parse($date)->format('l'); // Monday, Tuesday, etc.
            $filteredStaffList = collect();
            
            foreach ($staffList as $staff) {
                $isPerHour = strtolower(trim($staff->salary_type ?? '')) === 'per hour';
                
                // For per hour teachers, check if they have classes on this day
                if ($isPerHour) {
                    $hasClassOnDate = $this->hasClassOnDate($staff, $dayName, $campus);
                    if ($hasClassOnDate) {
                        $filteredStaffList->push($staff);
                    }
                } else {
                    // For non-per-hour staff, always include them
                    $filteredStaffList->push($staff);
                }
            }
            
            $staffList = $filteredStaffList;
        }
        if (!$type && $staffList->where('salary_type', 'lecture')->isNotEmpty()) {
            $type = 'Subject Attendance';
        }

        // Get existing attendance data for the selected date
        $attendanceData = [];
        if ($date && $staffList->isNotEmpty()) {
            $staffIds = $staffList->pluck('id');
            $attendances = StaffAttendance::whereIn('staff_id', $staffIds)
                ->whereDate('attendance_date', $date)
                ->get()
                ->keyBy('staff_id');

            $timetableTimesByStaff = []; // Store timetable times for per hour teachers
            
            foreach ($staffList as $staff) {
                $attendance = $attendances->get($staff->id);
                $isPerHour = strtolower(trim($staff->salary_type ?? '')) === 'per hour';
                
                // For per hour teacher, get time from timetable if not in attendance
                $startTime = $attendance ? $attendance->start_time : null;
                $endTime = $attendance ? $attendance->end_time : null;
                $timetableTime = null;
                
                if ($isPerHour) {
                    $timetableTime = $this->getTimeFromTimetable($staff, $date, $campus);
                    if ($timetableTime) {
                        // Store timetable time for JavaScript use
                        $timetableTimesByStaff[$staff->id] = $timetableTime;
                        
                        if (empty($startTime)) {
                            $startTime = $timetableTime['start_time'];
                        }
                        if (empty($endTime)) {
                            $endTime = $timetableTime['end_time'];
                        }
                    }
                }
                
                // For per hour teacher, calculate late arrival/early exit using timetable time
                $lateArrival = null;
                $earlyExit = null;
                
                if ($isPerHour && $timetableTime) {
                    // Use timetable time for late arrival calculation
                    if ($startTime) {
                        $lateArrival = $this->calculateLateArrivalWithTimetable($startTime, $timetableTime['start_time']);
                    }
                    // Use timetable time for early exit calculation
                    if ($endTime) {
                        $earlyExit = $this->calculateEarlyExitWithTimetable($endTime, $timetableTime['end_time']);
                    }
                } else {
                    // Use salary setting for non-per-hour staff
                    $lateArrival = $this->calculateLateArrival($startTime);
                    $earlyExit = $this->calculateEarlyExit($endTime);
                }
                
                $attendanceData[$staff->id] = [
                    'status' => $attendance ? $attendance->status : null,
                    'start_time' => $startTime,
                    'end_time' => $endTime,
                    'conducted_lectures' => $attendance ? $attendance->conducted_lectures : null,
                    'late_arrival' => $lateArrival,
                    'early_exit' => $earlyExit,
                ];
            }
        }

        // Get late arrival time and early exit time from Salary Setting
        $settings = SalarySetting::getSettings();
        $lateArrivalTime = $settings->late_arrival_time ?? '08:00:00';
        $earlyExitTime = $settings->early_exit_time ?? null;
        
        // Convert late arrival time to HH:MM format for JavaScript
        // Handle different time formats
        if (preg_match('/^(\d{2}):(\d{2}):(\d{2})$/', $lateArrivalTime, $matches)) {
            // Format: HH:MM:SS
            $lateArrivalTimeFormatted = $matches[1] . ':' . $matches[2];
        } elseif (preg_match('/^(\d{2}):(\d{2})$/', $lateArrivalTime, $matches)) {
            // Format: HH:MM
            $lateArrivalTimeFormatted = $lateArrivalTime;
        } elseif (preg_match('/(\d{1,2}):(\d{2})\s*(AM|PM)/i', $lateArrivalTime, $matches)) {
            // Format: hh:mm AM/PM
            $hours = (int)$matches[1];
            $minutes = $matches[2];
            $ampm = strtoupper($matches[3]);
            
            if ($ampm === 'PM' && $hours != 12) {
                $hours += 12;
            } elseif ($ampm === 'AM' && $hours == 12) {
                $hours = 0;
            }
            
            $lateArrivalTimeFormatted = str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' . $minutes;
        } else {
            // Default fallback
            $lateArrivalTimeFormatted = '08:00';
        }
        
        // Convert early exit time to HH:MM format for JavaScript (24-hour format)
        $earlyExitTimeFormatted = null;
        if ($earlyExitTime) {
            // Try to parse different time formats
            if (preg_match('/^(\d{2}):(\d{2}):(\d{2})$/', $earlyExitTime, $matches)) {
                // Format: HH:MM:SS (already 24-hour)
                $hours = (int)$matches[1];
                $minutes = $matches[2];
                $earlyExitTimeFormatted = str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' . $minutes;
            } elseif (preg_match('/^(\d{2}):(\d{2})$/', $earlyExitTime, $matches)) {
                // Format: HH:MM (already 24-hour)
                $hours = (int)$matches[1];
                $minutes = $matches[2];
                // If hours > 12, it's already 24-hour format
                if ($hours > 12) {
                    $earlyExitTimeFormatted = $earlyExitTime;
                } else {
                    // Assume it's 24-hour format
                    $earlyExitTimeFormatted = $earlyExitTime;
                }
            } elseif (preg_match('/(\d{1,2}):(\d{2})\s*(AM|PM)/i', $earlyExitTime, $matches)) {
                // Format: hh:mm AM/PM (12-hour format) - convert to 24-hour
                $hours = (int)$matches[1];
                $minutes = $matches[2];
                $ampm = strtoupper($matches[3]);
                
                // Convert 12-hour to 24-hour format
                if ($ampm === 'PM' && $hours != 12) {
                    $hours += 12; // 1 PM = 13:00, 2 PM = 14:00, etc.
                } elseif ($ampm === 'AM' && $hours == 12) {
                    $hours = 0; // 12 AM = 00:00
                }
                // Hours remain same for AM (except 12 AM)
                
                $earlyExitTimeFormatted = str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' . $minutes;
            } else {
                // Try Carbon to parse the time
                try {
                    $parsedTime = \Carbon\Carbon::createFromFormat('h:i A', $earlyExitTime);
                    $earlyExitTimeFormatted = $parsedTime->format('H:i');
                } catch (\Exception $e) {
                    // If parsing fails, try other formats
                    try {
                        $parsedTime = \Carbon\Carbon::createFromFormat('H:i:s', $earlyExitTime);
                        $earlyExitTimeFormatted = $parsedTime->format('H:i');
                    } catch (\Exception $e2) {
                        // Keep as is if all parsing fails
                        $earlyExitTimeFormatted = $earlyExitTime;
                    }
                }
            }
        }

        // Get campuses only from Campus model (Manage Campuses page)
        $campuses = Campus::whereNotNull('campus_name')
            ->orderBy('campus_name', 'asc')
            ->pluck('campus_name')
            ->unique()
            ->values();

        // Get day name from selected date for filtering subjects by timetable
        $dayName = null;
        if ($date) {
            try {
                $dayName = Carbon::parse($date)->format('l'); // Monday, Tuesday, etc.
            } catch (\Exception $e) {
                // If date parsing fails, continue without day filter
            }
        }

        $assignedSubjectsByStaff = [];
        // Initialize array for all staff members to ensure 0 shows correctly
        foreach ($staffList as $staff) {
            $assignedSubjectsByStaff[$staff->id] = [];
        }
        
        if ($staffList->isNotEmpty()) {
            $staffNameToId = $staffList->mapWithKeys(function ($staff) {
                return [strtolower(trim((string) $staff->name)) => $staff->id];
            });

            $subjectsQuery = Subject::whereNotNull('teacher');
            if ($campus) {
                $subjectsQuery->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower(trim($campus))]);
            }
            $subjects = $subjectsQuery->get(['teacher', 'class', 'section', 'subject_name', 'campus']);

            // Subject name mapping for flexible matching (same as in getTimeFromTimetable)
            $subjectNameMap = [
                'maths' => ['maths', 'mathematics', 'math'],
                'mathematics' => ['maths', 'mathematics', 'math'],
                'english' => ['english', 'eng'],
                'urdu' => ['urdu'],
                'science' => ['science', 'sci'],
                'islamiat' => ['islamiat', 'islamic studies', 'islamic'],
                'social studies' => ['social studies', 'social', 'sst'],
            ];

            // For Subject Attendance, count timetable entries (classes) instead of subjects
            if ($type === 'Subject Attendance' && $dayName) {
                // Get all timetable entries for each staff on this day
                foreach ($staffNameToId as $teacherName => $staffId) {
                    $staff = Staff::find($staffId);
                    if (!$staff) {
                        continue;
                    }
                    
                    // Get staff's assigned subjects to find matching timetable entries
                    $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower($teacherName)])
                        ->whereNotNull('subject_name')
                        ->whereNotNull('class')
                        ->whereNotNull('section')
                        ->get();
                    
                    if ($assignedSubjects->isEmpty()) {
                        continue;
                    }
                    
                    // Collect all timetable entries for this staff on this day
                    $timetableEntries = collect();
                    
                    foreach ($assignedSubjects as $subject) {
                        $subjectName = trim($subject->subject_name ?? '');
                        $subjectClass = trim($subject->class ?? '');
                        $subjectSection = trim($subject->section ?? '');
                        $subjectCampus = trim($subject->campus ?? '');
                        
                        if (empty($subjectName) || empty($subjectClass) || empty($subjectSection)) {
                            continue;
                        }
                        
                        $subjectNameLower = strtolower($subjectName);
                        
                        // Build query to find timetable entries matching this subject
                        $timetableQuery = Timetable::where(function($q) use ($subjectNameLower, $subjectNameMap) {
                            $q->whereRaw('LOWER(TRIM(subject)) = ?', [$subjectNameLower]);
                            if (isset($subjectNameMap[$subjectNameLower])) {
                                foreach ($subjectNameMap[$subjectNameLower] as $variant) {
                                    $q->orWhereRaw('LOWER(TRIM(subject)) = ?', [$variant]);
                                }
                            }
                        })
                        ->whereRaw('LOWER(TRIM(day)) = ?', [strtolower($dayName)])
                        ->where(function($q) use ($subjectClass) {
                            $q->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($subjectClass)]);
                            if (is_numeric($subjectClass)) {
                                $q->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower($this->numberToWord($subjectClass))]);
                            } else {
                                $wordToNumber = $this->wordToNumber($subjectClass);
                                if ($wordToNumber !== null) {
                                    $q->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower((string)$wordToNumber)]);
                                }
                            }
                        })
                        ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($subjectSection)]);
                        
                        // Add campus filter if available
                        if (!empty($subjectCampus)) {
                            $timetableQuery->where(function($q) use ($subjectCampus) {
                                $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($subjectCampus)])
                                  ->orWhereNull('campus')
                                  ->orWhere('campus', '');
                            });
                        }
                        
                        // Get all matching timetable entries (each entry is a class)
                        $entries = $timetableQuery->get();
                        $timetableEntries = $timetableEntries->merge($entries);
                    }
                    
                    // Remove duplicates based on timetable entry ID
                    $timetableEntries = $timetableEntries->unique('id');
                    
                    // Build labels for each timetable entry (class)
                    foreach ($timetableEntries as $entry) {
                        $labelParts = array_filter([
                            $entry->class ?? null,
                            $entry->section ?? null
                        ]);
                        $label = implode(' ', $labelParts);
                        $subjectLabel = trim(($label ? $label . ': ' : '') . ($entry->subject ?? ''));
                        if ($subjectLabel !== '') {
                            $assignedSubjectsByStaff[$staffId][] = $subjectLabel;
                        }
                    }
                }
            } else {
                // For non-Subject Attendance, use original logic
                foreach ($subjects as $subject) {
                    $teacherKey = strtolower(trim((string) $subject->teacher));
                    if (!isset($staffNameToId[$teacherKey])) {
                        continue;
                    }
                    
                    $staffId = $staffNameToId[$teacherKey];
                    $labelParts = array_filter([
                        $subject->class ?? null,
                        $subject->section ?? null
                    ]);
                    $label = implode(' ', $labelParts);
                    $subjectLabel = trim(($label ? $label . ': ' : '') . ($subject->subject_name ?? ''));
                    $assignedSubjectsByStaff[$staffId][] = $subjectLabel !== '' ? $subjectLabel : 'N/A';
                }
            }
        }

        // Format date label - include day name for Subject Attendance
        $dateLabel = Carbon::parse($date)->format('d F Y');
        if ($type === 'Subject Attendance' && $dayName) {
            $dateLabel = $dayName . ', ' . $dateLabel;
        }

        return view('attendance.staff', [
            'staffList' => $staffList,
            'attendanceData' => $attendanceData,
            'campuses' => $campuses,
            'lateArrivalTime' => $lateArrivalTimeFormatted,
            'earlyExitTime' => $earlyExitTimeFormatted ?? '',
            'campus' => $campus,
            'staffCategory' => $staffCategory,
            'type' => $type,
            'date' => $date,
            'dateLabel' => $dateLabel,
            'assignedSubjectsByStaff' => $assignedSubjectsByStaff,
            'timetableTimesByStaff' => $timetableTimesByStaff ?? [],
        ]);
    }

    /**
     * Store staff attendance.
     */
    public function store(Request $request)
    {
        $request->validate([
            'date' => 'required|date',
            'attendance' => 'required|array',
            'attendance.*.staff_id' => 'required|exists:staff,id',
            'attendance.*.status' => 'nullable|in:Present,Absent,Holiday,Sunday,Leave,Half Day',
            'attendance.*.start_time' => 'nullable|date_format:H:i',
            'attendance.*.end_time' => 'nullable|date_format:H:i',
            'attendance.*.leave_deduction' => 'nullable|in:Yes,No',
            'attendance.*.conducted_lectures' => 'nullable|integer|min:0',
            'attendance.*.late_arrival' => 'nullable|in:Auto,Yes,No',
            'attendance.*.auto_late_arrival' => 'nullable|in:Auto,Yes,No',
            'attendance.*.auto_early_exit' => 'nullable|in:Auto,Yes,No',
        ]);

        $date = $request->input('date');
        $attendanceData = $request->input('attendance', []);

        // Detect subject attendance even if type is missing
        $attendanceType = strtolower(trim((string) $request->input('type')));
        $hasLecturesField = false;
        $hasLateArrivalField = false;
        $hasStatusField = false;
        foreach ($attendanceData as $row) {
            if (!is_array($row)) {
                continue;
            }
            if (array_key_exists('conducted_lectures', $row)) {
                $hasLecturesField = true;
            }
            if (array_key_exists('late_arrival', $row)) {
                $hasLateArrivalField = true;
            }
            if (array_key_exists('status', $row)) {
                $hasStatusField = true;
            }
        }
        $isSubjectAttendance = ($attendanceType === 'subject attendance')
            || (!$hasStatusField && ($hasLecturesField || $hasLateArrivalField || !empty($attendanceData)));

        // Filter out entries without any data
        if ($isSubjectAttendance) {
            $attendanceData = array_filter($attendanceData, function($data) {
                return is_array($data) && !empty($data['staff_id']);
            });
        } else {
            $attendanceData = array_filter($attendanceData, function($data) {
                return !empty($data['status']);
            });
        }

        if (empty($attendanceData)) {
            if ($isSubjectAttendance) {
                return redirect()->back()->with('info', 'No lectures entered to save.')->withInput();
            }
            return redirect()->back()->with('error', 'Please select at least one attendance status before saving.')->withInput();
        }

        DB::beginTransaction();
        try {
            foreach ($attendanceData as $data) {
                if (!$isSubjectAttendance && empty($data['status'])) {
                    continue;
                }

                $staff = Staff::find($data['staff_id']);
                if (!$staff) {
                    continue;
                }

                // For per hour teachers, check if they have classes on this date
                $isPerHour = strtolower(trim($staff->salary_type ?? '')) === 'per hour';
                if ($isPerHour) {
                    $dayName = Carbon::parse($date)->format('l');
                    $hasClassOnDate = $this->hasClassOnDate($staff, $dayName, $request->input('campus'));
                    if (!$hasClassOnDate) {
                        // Skip per hour teachers who don't have classes on this date
                        continue;
                    }
                }

                $conductedLectures = isset($data['conducted_lectures']) && $data['conducted_lectures'] !== ''
                    ? (int) $data['conducted_lectures']
                    : 0;
                $status = $data['status'] ?? null;
                if ($isSubjectAttendance) {
                    if ($conductedLectures !== null) {
                        $status = ($conductedLectures > 0) ? 'Present' : 'Absent';
                    } elseif (!$status) {
                        $status = 'N/A';
                    }
                }

                // For per hour teacher, get time from timetable if not provided
                $startTime = $data['start_time'] ?? null;
                $endTime = $data['end_time'] ?? null;
                $isPerHour = strtolower(trim($staff->salary_type ?? '')) === 'per hour';
                $timetableTime = null;
                
                if ($isPerHour && $status === 'Present') {
                    // Get time from timetable for per hour teacher
                    $timetableTime = $this->getTimeFromTimetable($staff, $date, $request->input('campus'));
                    if ($timetableTime) {
                        if (empty($startTime)) {
                            $startTime = $timetableTime['start_time'];
                        }
                        if (empty($endTime)) {
                            $endTime = $timetableTime['end_time'];
                        }
                    }
                }

                // Calculate late arrival and early exit
                $lateArrival = null;
                $earlyExit = null;
                
                if ($isPerHour && $timetableTime) {
                    // For per hour teacher, use timetable time for calculation
                    if ($startTime) {
                        $lateArrival = $this->calculateLateArrivalWithTimetable($startTime, $timetableTime['start_time']);
                    }
                    if ($endTime) {
                        $earlyExit = $this->calculateEarlyExitWithTimetable($endTime, $timetableTime['end_time']);
                    }
                } else {
                    // For other staff, use salary setting time
                    if ($startTime) {
                        $lateArrival = $this->calculateLateArrival($startTime);
                    }
                    if ($endTime) {
                        $earlyExit = $this->calculateEarlyExit($endTime);
                    }
                }

                // If form has manual late_arrival value (not Auto), use it
                if (isset($data['late_arrival']) && $data['late_arrival'] !== 'Auto' && $data['late_arrival'] !== null && $data['late_arrival'] !== '') {
                    $lateArrival = $data['late_arrival'];
                }

                $remarks = $data['remarks'] ?? (isset($data['leave_deduction']) ? 'Leave Deduction: ' . $data['leave_deduction'] : null);
                if ($isSubjectAttendance && $lateArrival) {
                    $remarks = trim(($remarks ? $remarks . ' | ' : '') . 'Late Arrival: ' . $lateArrival);
                }
                
                // Add early exit to remarks if applicable
                if ($earlyExit) {
                    $remarks = trim(($remarks ? $remarks . ' | ' : '') . 'Early Exit: ' . $earlyExit);
                }

                StaffAttendance::updateOrCreate(
                    [
                        'staff_id' => $data['staff_id'],
                        'attendance_date' => $date,
                    ],
                    [
                        'status' => $status,
                        'start_time' => $startTime,
                        'end_time' => $endTime,
                        'conducted_lectures' => $conductedLectures,
                        'campus' => $staff->campus,
                        'designation' => $staff->designation,
                        'class' => null,
                        'section' => null,
                        'remarks' => $remarks,
                    ]
                );
            }

            DB::commit();
            return redirect()->route('attendance.staff', [
                'campus' => $request->input('campus'),
                'staff_category' => $request->input('staff_category'),
                'type' => $request->input('type'),
                'date' => $date,
            ])->with('success', 'Staff attendance saved successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Error saving attendance: ' . $e->getMessage())->withInput();
        }
    }

    /**
     * Check if per hour teacher has classes scheduled on a specific day (from Manage Timetable).
     */
    private function hasClassOnDate(Staff $staff, string $dayName, ?string $campus = null): bool
    {
        return $this->getStaffTimetableEntriesForDay($staff, $dayName, $campus)->isNotEmpty();
    }

    /**
     * Get arrival/exit times from Manage Timetable for per hour teacher on the selected date.
     */
    protected function getTimeFromTimetable(Staff $staff, string $date, ?string $campus = null): ?array
    {
        $dayName = Carbon::parse($date)->format('l');
        $entries = $this->getStaffTimetableEntriesForDay($staff, $dayName, $campus);

        if ($entries->isEmpty()) {
            return null;
        }

        $earliestStartTime = null;
        $latestEndTime = null;
        $totalHours = 0;

        foreach ($entries as $entry) {
            $startTime = $this->parseTimetableTime($entry->starting_time);
            $endTime = $this->parseTimetableTime($entry->ending_time);

            if (!$startTime || !$endTime) {
                continue;
            }

            $totalHours += $startTime->diffInMinutes($endTime) / 60;

            if ($earliestStartTime === null || $startTime->lt($earliestStartTime)) {
                $earliestStartTime = $startTime;
            }

            if ($latestEndTime === null || $endTime->gt($latestEndTime)) {
                $latestEndTime = $endTime;
            }
        }

        if (!$earliestStartTime || !$latestEndTime) {
            return null;
        }

        return [
            'start_time' => $earliestStartTime->format('H:i'),
            'end_time' => $latestEndTime->format('H:i'),
            'total_hours' => round($totalHours, 2),
        ];
    }

    /**
     * Timetable entries assigned to staff for a day (same source as Manage Timetable).
     */
    private function getStaffTimetableEntriesForDay(Staff $staff, string $dayName, ?string $campus = null): \Illuminate\Support\Collection
    {
        $staffName = strtolower(trim($staff->name ?? ''));
        if ($staffName === '') {
            return collect();
        }

        $campusFilter = $campus ?? $staff->campus;
        $campusNorm = $campusFilter ? strtolower(trim($campusFilter)) : '';

        $query = Timetable::whereRaw('LOWER(TRIM(day)) = ?', [strtolower($dayName)])
            ->where('subject', 'not like', '[%');

        if ($campusNorm !== '') {
            $query->where(function ($q) use ($campusNorm) {
                $q->whereRaw('LOWER(TRIM(campus)) = ?', [$campusNorm])
                    ->orWhereNull('campus')
                    ->orWhere('campus', '');
            });
        }

        return $query->get()->filter(function (Timetable $entry) use ($staffName) {
            $teacher = $this->resolveTeacherForTimetableEntry($entry);

            return $teacher !== null && strtolower(trim($teacher)) === $staffName;
        })->values();
    }

    /**
     * Resolve teacher for a timetable row (aligned with Manage Timetable).
     */
    private function resolveTeacherForTimetableEntry(Timetable $entry): ?string
    {
        $subject = trim((string) ($entry->subject ?? ''));
        if ($subject === '' || str_starts_with($subject, '[')) {
            return null;
        }

        $subjectVariants = $this->getSubjectNameVariants($subject);
        $campusNorm = !empty($entry->campus) ? strtolower(trim($entry->campus)) : '';
        $classNorm = !empty($entry->class) ? strtolower(trim($entry->class)) : '';
        $sectionNorm = !empty($entry->section) ? strtolower(trim($entry->section)) : '';

        $scopedQuery = function () use ($subjectVariants, $campusNorm) {
            $q = Subject::query()
                ->where(function ($query) use ($subjectVariants) {
                    foreach ($subjectVariants as $variant) {
                        $query->orWhereRaw('LOWER(TRIM(subject_name)) = ?', [$variant]);
                    }
                })
                ->whereNotNull('teacher')
                ->where('teacher', '!=', '');

            if ($campusNorm !== '') {
                $q->whereRaw('LOWER(TRIM(campus)) = ?', [$campusNorm]);
            }

            return $q;
        };

        if ($campusNorm !== '' && $classNorm !== '' && $sectionNorm !== '') {
            $exact = $scopedQuery()
                ->where(function ($query) use ($classNorm) {
                    $query->whereRaw('LOWER(TRIM(class)) = ?', [$classNorm]);
                    if (is_numeric($classNorm)) {
                        $query->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower($this->numberToWord($classNorm))]);
                    } else {
                        $wordToNumber = $this->wordToNumber($classNorm);
                        if ($wordToNumber !== null) {
                            $query->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower((string) $wordToNumber)]);
                        }
                    }
                })
                ->whereRaw('LOWER(TRIM(section)) = ?', [$sectionNorm])
                ->first();

            if ($exact?->teacher) {
                return trim($exact->teacher);
            }
        }

        if ($campusNorm !== '' && $classNorm !== '') {
            $byClass = $scopedQuery()
                ->where(function ($query) use ($classNorm) {
                    $query->whereRaw('LOWER(TRIM(class)) = ?', [$classNorm]);
                    if (is_numeric($classNorm)) {
                        $query->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower($this->numberToWord($classNorm))]);
                    } else {
                        $wordToNumber = $this->wordToNumber($classNorm);
                        if ($wordToNumber !== null) {
                            $query->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower((string) $wordToNumber)]);
                        }
                    }
                })
                ->first();

            if ($byClass?->teacher) {
                return trim($byClass->teacher);
            }
        }

        if ($campusNorm !== '') {
            $byCampus = $scopedQuery()->first();
            if ($byCampus?->teacher) {
                return trim($byCampus->teacher);
            }

            return null;
        }

        $query = Subject::query()
            ->where(function ($subjectQuery) use ($subjectVariants) {
                foreach ($subjectVariants as $variant) {
                    $subjectQuery->orWhereRaw('LOWER(TRIM(subject_name)) = ?', [$variant]);
                }
            })
            ->whereNotNull('teacher')
            ->where('teacher', '!=', '');

        if ($classNorm !== '') {
            $query->where(function ($classQuery) use ($classNorm) {
                $classQuery->whereRaw('LOWER(TRIM(class)) = ?', [$classNorm]);
                if (is_numeric($classNorm)) {
                    $classQuery->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower($this->numberToWord($classNorm))]);
                } else {
                    $wordToNumber = $this->wordToNumber($classNorm);
                    if ($wordToNumber !== null) {
                        $classQuery->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower((string) $wordToNumber)]);
                    }
                }
            });
        }

        if ($sectionNorm !== '') {
            $query->whereRaw('LOWER(TRIM(section)) = ?', [$sectionNorm]);
        }

        $match = $query->first();

        return $match?->teacher ? trim($match->teacher) : null;
    }

    /**
     * @return list<string>
     */
    private function getSubjectNameVariants(string $subjectName): array
    {
        $subjectNameMap = [
            'maths' => ['maths', 'mathematics', 'math'],
            'mathematics' => ['maths', 'mathematics', 'math'],
            'english' => ['english', 'eng'],
            'urdu' => ['urdu'],
            'science' => ['science', 'sci'],
            'islamiat' => ['islamiat', 'islamic studies', 'islamic'],
            'social studies' => ['social studies', 'social', 'sst'],
        ];

        $lower = strtolower(trim($subjectName));
        $variants = [$lower];

        if (isset($subjectNameMap[$lower])) {
            $variants = array_merge($variants, $subjectNameMap[$lower]);
        }

        return array_values(array_unique($variants));
    }

    /**
     * Parse timetable time values from database (H:i, H:i:s, or DateTime).
     */
    private function parseTimetableTime($time): ?Carbon
    {
        if (empty($time)) {
            return null;
        }

        if ($time instanceof \DateTimeInterface) {
            return Carbon::instance($time);
        }

        $timeStr = trim((string) $time);
        foreach (['H:i:s', 'H:i', 'h:i A', 'h:i a'] as $format) {
            try {
                return Carbon::createFromFormat($format, $timeStr);
            } catch (\Exception $e) {
                continue;
            }
        }

        try {
            return Carbon::parse($timeStr);
        } catch (\Exception $e) {
            return null;
        }
    }

    /**
     * Convert number to word (e.g., 4 -> "four")
     */
    private function numberToWord($number): string
    {
        $words = [
            1 => 'one', 2 => 'two', 3 => 'three', 4 => 'four', 5 => 'five',
            6 => 'six', 7 => 'seven', 8 => 'eight', 9 => 'nine', 10 => 'ten',
            11 => 'eleven', 12 => 'twelve'
        ];
        return $words[(int)$number] ?? (string)$number;
    }

    /**
     * Convert word to number (e.g., "four" -> 4)
     */
    private function wordToNumber($word): ?int
    {
        $words = [
            'one' => 1, 'two' => 2, 'three' => 3, 'four' => 4, 'five' => 5,
            'six' => 6, 'seven' => 7, 'eight' => 8, 'nine' => 9, 'ten' => 10,
            'eleven' => 11, 'twelve' => 12
        ];
        $wordLower = strtolower(trim($word));
        return $words[$wordLower] ?? null;
    }

    /**
     * Calculate late arrival using timetable time for per hour teacher
     */
    protected function calculateLateArrivalWithTimetable($startTime, $timetableStartTime): ?string
    {
        if (!$startTime || !$timetableStartTime) {
            return null;
        }

        try {
            $time = is_string($startTime) ? $startTime : $startTime->format('H:i:s');
            $standardTime = is_string($timetableStartTime) ? $timetableStartTime : $timetableStartTime->format('H:i:s');
            
            $start = strtotime($time);
            $standard = strtotime($standardTime);

            if ($start && $standard && $start > $standard) {
                $diff = $start - $standard;
                $hours = floor($diff / 3600);
                $minutes = floor(($diff % 3600) / 60);
                return sprintf('%02d:%02d', $hours, $minutes);
            }
        } catch (\Exception $e) {
            // If time parsing fails, return null
        }

        return null;
    }

    /**
     * Calculate early exit using timetable time for per hour teacher
     */
    protected function calculateEarlyExitWithTimetable($endTime, $timetableEndTime): ?string
    {
        if (!$endTime || !$timetableEndTime) {
            return null;
        }

        try {
            $time = is_string($endTime) ? $endTime : $endTime->format('H:i:s');
            $standardTime = is_string($timetableEndTime) ? $timetableEndTime : $timetableEndTime->format('H:i:s');
            
            $end = strtotime($time);
            $standard = strtotime($standardTime);

            if ($end && $standard && $end < $standard) {
                $diff = $standard - $end;
                $hours = floor($diff / 3600);
                $minutes = floor(($diff % 3600) / 60);
                return sprintf('%02d:%02d', $hours, $minutes);
            }
        } catch (\Exception $e) {
            // If time parsing fails, return null
        }

        return null;
    }

    /**
     * Calculate late arrival (assuming 9:00 AM is the standard time).
     */
    protected function calculateLateArrival($startTime)
    {
        if (!$startTime) {
            return null;
        }

        try {
            // Handle different time formats
            $time = is_string($startTime) ? $startTime : $startTime->format('H:i:s');
            $settings = SalarySetting::getSettings();
            $standardTime = $settings->late_arrival_time ?? '09:00:00';
            
            $start = strtotime($time);
            $standard = strtotime($standardTime);

            if ($start && $standard && $start > $standard) {
                $diff = $start - $standard;
                $hours = floor($diff / 3600);
                $minutes = floor(($diff % 3600) / 60);
                return sprintf('%02d:%02d', $hours, $minutes);
            }
        } catch (\Exception $e) {
            // If time parsing fails, return null
        }

        return null;
    }

    /**
     * Calculate early exit based on early exit time from salary settings.
     */
    protected function calculateEarlyExit($endTime)
    {
        if (!$endTime) {
            return null;
        }

        try {
            // Handle different time formats
            $time = is_string($endTime) ? $endTime : $endTime->format('H:i:s');
            $settings = SalarySetting::getSettings();
            $earlyExitTimeSetting = $settings->early_exit_time;
            
            if (!$earlyExitTimeSetting) {
                return null;
            }
            
            $end = strtotime($time);
            $standard = strtotime($earlyExitTimeSetting);

            if ($end && $standard && $end < $standard) {
                $diff = $standard - $end;
                $hours = floor($diff / 3600);
                $minutes = floor(($diff % 3600) / 60);
                return sprintf('%02d:%02d', $hours, $minutes);
            }
        } catch (\Exception $e) {
            // If time parsing fails, return null
        }

        return null;
    }

    /**
     * Display staff attendance overview page with all teachers
     */
    public function overview(Request $request): View
    {
        $year = $request->get('year', date('Y'));
        
        // Get all active staff (dynamic - includes newly added, excludes deleted)
        // Only show staff with Active status or no status set (for backward compatibility)
        $allStaff = Staff::where(function($query) {
                $query->where('status', 'Active')
                      ->orWhereNull('status')
                      ->orWhere('status', '');
            })
            ->orderBy('name', 'asc')
            ->get();
        
        // Month names
        $monthNames = [
            '01' => 'January',
            '02' => 'February',
            '03' => 'March',
            '04' => 'April',
            '05' => 'May',
            '06' => 'June',
            '07' => 'July',
            '08' => 'August',
            '09' => 'September',
            '10' => 'October',
            '11' => 'November',
            '12' => 'December',
        ];
        
        // Prepare data for each staff member
        $staffReports = [];
        
        foreach ($allStaff as $staff) {
            // Get all attendance records for this staff for the selected year
            $attendances = StaffAttendance::where('staff_id', $staff->id)
                ->whereYear('attendance_date', $year)
                ->get();
            
            // Key by date string
            $attendancesByDate = [];
            foreach ($attendances as $attendance) {
                $dateKey = $attendance->attendance_date->format('Y-m-d');
                $attendancesByDate[$dateKey] = $attendance;
            }
            
            // Prepare daily attendance data for each month
            $dailyAttendance = [];
            $monthlySummary = [];
            
            foreach ($monthNames as $monthNum => $monthName) {
                // Get days in month
                $daysInMonth = cal_days_in_month(CAL_GREGORIAN, (int)$monthNum, (int)$year);
                
                // Daily attendance for this month
                $monthDailyData = [];
                for ($day = 1; $day <= $daysInMonth; $day++) {
                    $date = sprintf('%s-%02d-%02d', $year, $monthNum, $day);
                    $attendance = $attendancesByDate[$date] ?? null;
                    
                    if ($attendance) {
                        $status = $attendance->status;
                        // Map status to display format
                        if ($status == 'Present') {
                            $monthDailyData[$day] = 'P';
                        } elseif ($status == 'Absent') {
                            $monthDailyData[$day] = 'A';
                        } elseif ($status == 'Leave') {
                            $monthDailyData[$day] = 'L';
                        } elseif ($status == 'Holiday') {
                            $monthDailyData[$day] = 'H';
                        } elseif ($status == 'Sunday') {
                            $monthDailyData[$day] = 'S';
                        } else {
                            $monthDailyData[$day] = '--';
                        }
                    } else {
                        $monthDailyData[$day] = '--';
                    }
                }
                
                $dailyAttendance[$monthNum] = [
                    'month_name' => $monthName,
                    'days' => $monthDailyData,
                ];
                
                // Monthly summary
                $monthAttendances = collect();
                foreach ($attendancesByDate as $dateKey => $attendance) {
                    $attDate = Carbon::parse($dateKey);
                    if ($attDate->year == (int)$year && $attDate->month == (int)$monthNum) {
                        $monthAttendances->push($attendance);
                    }
                }
                
                // Check if staff is full-time (full-time teachers should count Sunday and Holiday as present)
                $isFullTime = empty($staff->salary_type) || strtolower(trim($staff->salary_type)) === 'full time';
                
                $presentCount = $monthAttendances->where('status', 'Present')->count();
                $holidayCount = $monthAttendances->where('status', 'Holiday')->count();
                $sundayCount = $monthAttendances->where('status', 'Sunday')->count();
                
                // For full-time staff, count Sunday and Holiday as present
                if ($isFullTime) {
                    $presentCount += $holidayCount + $sundayCount;
                }
                
                $monthlySummary[] = [
                    'month' => $monthNum,
                    'month_name' => $monthName,
                    'present' => $presentCount,
                    'absent' => $monthAttendances->where('status', 'Absent')->count(),
                    'leave' => $monthAttendances->where('status', 'Leave')->count(),
                    'holiday' => $holidayCount,
                    'sunday' => $sundayCount,
                ];
            }
            
            $staffReports[] = [
                'staff' => $staff,
                'daily_attendance' => $dailyAttendance,
                'monthly_summary' => $monthlySummary,
            ];
        }
        
        // Year options
        $currentYear = date('Y');
        $years = collect();
        for ($i = 0; $i < 6; $i++) {
            $years->push($currentYear - $i);
        }
        
        return view('attendance.staff-overview', compact(
            'staffReports',
            'year',
            'years',
            'monthNames'
        ));
    }

    /**
     * Staff ID card / QR scan — check-in or check-out (gate; no login required).
     *
     * POST /attendance/staff-barcode/scan
     */
    public function scanIdCardGate(Request $request): JsonResponse
    {
        $this->mergeScanInputsIntoRequest($request);

        $staff = $this->resolveStaffFromCardScan($request);
        if (!$staff) {
            return response()->json([
                'success' => false,
                'message' => 'Staff not found for this ID card.',
            ], 404);
        }

        return $this->processStaffIdCardScan($staff);
    }

    /**
     * Staff ID card / QR scan — logged-in staff must match card (mobile API).
     *
     * POST /api/teacher/attendance/scan-id-card
     * POST /api/staff/attendance/scan-id-card
     */
    public function scanIdCardApi(Request $request): JsonResponse
    {
        $this->mergeScanInputsIntoRequest($request);

        $authStaff = $request->user();
        if (!$authStaff instanceof Staff) {
            return response()->json([
                'success' => false,
                'message' => 'Access denied. Staff login required.',
                'token' => null,
            ], 403);
        }

        $selfCheckIn = filter_var(
            $request->input('self_check_in', $request->boolean('self_check_in')),
            FILTER_VALIDATE_BOOLEAN
        );

        $barcode = $this->resolveScanRawFromRequest($request);

        if ($barcode === '' && $selfCheckIn) {
            $staff = $authStaff;
        } elseif ($barcode === '') {
            return response()->json([
                'success' => false,
                'message' => 'ID card / QR value is required (send barcode, emp_id, or QR string in JSON body with Content-Type: application/json, or use self_check_in: true).',
                'token' => null,
            ], 422);
        } else {
            $staff = $this->resolveStaffFromCardScan($request);
            if (!$staff) {
                return response()->json([
                    'success' => false,
                    'message' => 'Staff not found for this ID card.',
                    'token' => null,
                    'hint' => [
                        'your_emp_id' => $authStaff->emp_id,
                        'your_id' => $authStaff->id,
                    ],
                ], 404);
            }

            if ((int) $staff->id !== (int) $authStaff->id) {
                return response()->json([
                    'success' => false,
                    'message' => 'This ID card does not match your logged-in account.',
                    'token' => null,
                ], 403);
            }
        }

        $response = $this->processStaffIdCardScan($staff);
        $payload = $response->getData(true);
        if (is_array($payload)) {
            $payload['token'] = $authStaff->currentAccessToken()->token ?? null;
            return response()->json($payload, $response->getStatusCode());
        }

        return $response;
    }

    /**
     * Accept JSON body even when Content-Type is missing (common in Postman/mobile clients).
     */
    protected function mergeScanInputsIntoRequest(Request $request): void
    {
        if ($this->resolveScanRawFromRequest($request) !== '') {
            return;
        }

        $json = $request->json()->all();
        if (is_array($json) && $json !== []) {
            $request->merge($json);

            return;
        }

        $content = trim((string) $request->getContent());
        if ($content !== '' && str_starts_with($content, '{')) {
            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                $request->merge($decoded);
            }
        }
    }

    protected function resolveScanRawFromRequest(Request $request): string
    {
        foreach (['barcode', 'id_card', 'scan', 'emp_id', 'qr', 'qr_code', 'data', 'code'] as $key) {
            $value = $request->input($key);
            if (is_string($value) && trim($value) !== '') {
                return trim($value);
            }
        }

        return '';
    }

    protected function resolveStaffFromCardScan(Request $request): ?Staff
    {
        $raw = $this->resolveScanRawFromRequest($request);

        if ($raw === '') {
            return null;
        }

        foreach ($this->parseStaffCardScanValues($raw) as $candidate) {
            $staff = $this->findStaffByCardValue($candidate);
            if ($staff) {
                return $staff;
            }
        }

        return null;
    }

    /**
     * @return list<string>
     */
    protected function parseStaffCardScanValues(string $raw): array
    {
        $values = [];
        $trimmed = trim($raw);
        if ($trimmed === '') {
            return [];
        }

        $values[] = $trimmed;
        $decoded = trim(urldecode($trimmed));
        if ($decoded !== $trimmed) {
            $values[] = $decoded;
        }

        foreach ([$trimmed, $decoded] as $text) {
            if (preg_match('/\bID:\s*([^|]+)/iu', $text, $matches)) {
                $values[] = trim($matches[1]);
            }
        }

        return array_values(array_unique(array_filter($values, static fn ($v) => $v !== '')));
    }

    protected function findStaffByCardValue(string $value): ?Staff
    {
        $lower = strtolower(trim($value));

        $query = Staff::query()->where(function ($q) use ($lower, $value) {
            $q->whereRaw('LOWER(TRIM(emp_id)) = ?', [$lower]);
            if (is_numeric($value)) {
                $q->orWhere('id', (int) $value);
            }
        });

        return $query->first();
    }

    protected function processStaffIdCardScan(Staff $staff): JsonResponse
    {
        $today = Carbon::today()->format('Y-m-d');
        $nowTime = Carbon::now()->format('H:i:s');
        $salaryType = strtolower(trim($staff->salary_type ?? ''));
        $isFullTime = $salaryType === '' || $salaryType === 'full time';
        $isPerHour = $salaryType === 'per hour';
        $isPerLecture = $salaryType === 'lecture';

        $settings = SalarySetting::getSettings();
        $standardIn = $settings->late_arrival_time ?? '09:00:00';
        $standardOut = $settings->early_exit_time ?? null;

        $attendance = StaffAttendance::where('staff_id', $staff->id)
            ->whereDate('attendance_date', $today)
            ->first();

        $hasCheckIn = $attendance && !empty($attendance->start_time);
        $hasCheckOut = $attendance && !empty($attendance->end_time);

        if ($hasCheckIn && $hasCheckOut) {
            return response()->json([
                'success' => true,
                'message' => 'Attendance already completed for today (check-in and check-out recorded).',
                'data' => $this->formatStaffScanResponse($staff, $attendance, 'completed', $isFullTime, $isPerHour, $isPerLecture, $standardIn, $standardOut),
            ], 200);
        }

        if ($hasCheckIn && !$hasCheckOut) {
            $lateArrival = null;
            $earlyExit = null;
            $timetableTime = $isPerHour ? $this->getTimeFromTimetable($staff, $today) : null;

            if ($isPerHour && $timetableTime) {
                $earlyExit = $this->calculateEarlyExitWithTimetable($nowTime, $timetableTime['end_time']);
            } elseif ($isFullTime) {
                $earlyExit = $this->calculateEarlyExit($nowTime);
            }

            $remarks = $this->appendAttendanceRemark($attendance->remarks ?? '', 'ID card scan (check-out)');
            if ($earlyExit) {
                $remarks = $this->appendAttendanceRemark($remarks, 'Early Exit: ' . $earlyExit);
            }

            $attendance->update([
                'end_time' => $nowTime,
                'remarks' => $remarks,
            ]);
            $attendance->refresh();

            return response()->json([
                'success' => true,
                'message' => $earlyExit
                    ? 'Check-out recorded. Early exit: ' . $earlyExit
                    : 'Check-out recorded successfully.',
                'data' => $this->formatStaffScanResponse($staff, $attendance, 'check_out', $isFullTime, $isPerHour, $isPerLecture, $standardIn, $standardOut, null, $earlyExit),
            ], 200);
        }

        $lateArrival = null;
        $timetableTime = $isPerHour ? $this->getTimeFromTimetable($staff, $today) : null;

        if ($isPerHour && $timetableTime) {
            $lateArrival = $this->calculateLateArrivalWithTimetable($nowTime, $timetableTime['start_time']);
        } elseif ($isFullTime || $isPerHour) {
            $lateArrival = $this->calculateLateArrival($nowTime);
        }

        $remarks = 'ID card scan (check-in)';
        if ($lateArrival) {
            $remarks = $this->appendAttendanceRemark($remarks, 'Late Arrival: ' . $lateArrival);
        }

        $payload = [
            'staff_id' => $staff->id,
            'attendance_date' => $today,
            'status' => 'Present',
            'start_time' => $nowTime,
            'campus' => $staff->campus,
            'designation' => $staff->designation,
            'remarks' => $remarks,
        ];

        if ($isPerLecture) {
            $existing = $attendance ? (int) ($attendance->conducted_lectures ?? 0) : 0;
            $payload['conducted_lectures'] = max(1, $existing);
        }

        $attendance = StaffAttendance::updateOrCreate(
            [
                'staff_id' => $staff->id,
                'attendance_date' => $today,
            ],
            $payload
        );

        return response()->json([
            'success' => true,
            'message' => $lateArrival
                ? 'Check-in recorded. Late arrival: ' . $lateArrival
                : 'Check-in recorded successfully.',
            'data' => $this->formatStaffScanResponse($staff, $attendance, 'check_in', $isFullTime, $isPerHour, $isPerLecture, $standardIn, $standardOut, $lateArrival, null),
        ], 200);
    }

    protected function appendAttendanceRemark(?string $existing, string $snippet): string
    {
        $existing = trim((string) $existing);
        if ($existing === '') {
            return $snippet;
        }
        if (stripos($existing, $snippet) !== false) {
            return $existing;
        }

        return $existing . ' | ' . $snippet;
    }

    /**
     * @return array<string, mixed>
     */
    protected function formatStaffScanResponse(
        Staff $staff,
        StaffAttendance $attendance,
        string $action,
        bool $isFullTime,
        bool $isPerHour,
        bool $isPerLecture,
        ?string $standardIn,
        ?string $standardOut,
        ?string $lateArrival = null,
        ?string $earlyExit = null
    ): array {
        $salaryTypeLabel = $isPerLecture ? 'per lecture' : ($isPerHour ? 'per hour' : 'full time');

        return [
            'person_type' => 'staff',
            'action' => $action,
            'staff' => [
                'id' => $staff->id,
                'name' => $staff->name,
                'emp_id' => $staff->emp_id,
                'designation' => $staff->designation,
                'campus' => $staff->campus,
                'salary_type' => $salaryTypeLabel,
            ],
            'attendance' => [
                'date' => $attendance->attendance_date?->format('Y-m-d'),
                'status' => $attendance->status,
                'check_in' => $attendance->start_time ? date('h:i A', strtotime($attendance->start_time)) : null,
                'check_out' => $attendance->end_time ? date('h:i A', strtotime($attendance->end_time)) : null,
                'start_time' => $attendance->start_time,
                'end_time' => $attendance->end_time,
                'conducted_lectures' => $attendance->conducted_lectures,
                'late_arrival' => $lateArrival,
                'early_exit' => $earlyExit,
                'remarks' => $attendance->remarks,
            ],
            'salary_settings' => [
                'late_arrival_time' => $standardIn,
                'early_exit_time' => $standardOut,
            ],
        ];
    }
}

