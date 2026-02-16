<?php

namespace App\Http\Controllers;

use App\Models\Staff;
use App\Models\StaffAttendance;
use App\Models\Campus;
use App\Models\Subject;
use App\Models\Timetable;
use App\Models\SalarySetting;
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
                    $hasClassOnDate = $this->hasClassOnDate($staff, $date, $dayName);
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
                    $timetableTime = $this->getTimeFromTimetable($staff, $date);
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

            foreach ($subjects as $subject) {
                $teacherKey = strtolower(trim((string) $subject->teacher));
                if (!isset($staffNameToId[$teacherKey])) {
                    continue;
                }
                
                // If day filter is enabled (for Subject Attendance type), check timetable
                if ($type === 'Subject Attendance' && $dayName) {
                    $subjectName = trim($subject->subject_name ?? '');
                    $subjectClass = trim($subject->class ?? '');
                    $subjectSection = trim($subject->section ?? '');
                    $subjectCampus = trim($subject->campus ?? '');
                    
                    if (empty($subjectName) || empty($subjectClass) || empty($subjectSection)) {
                        continue;
                    }
                    
                    // Check if this subject is scheduled on this day in timetable
                    $subjectNameLower = strtolower($subjectName);
                    
                    // Build base query for subject, day, class, section matching
                    $buildTimetableQuery = function($includeCampus = false) use ($subjectNameLower, $subjectNameMap, $dayName, $subjectClass, $subjectSection, $subjectCampus) {
                        $query = Timetable::where(function($q) use ($subjectNameLower, $subjectNameMap) {
                            // Exact match
                            $q->whereRaw('LOWER(TRIM(subject)) = ?', [$subjectNameLower]);
                            
                            // Flexible matching for common subject name variations
                            if (isset($subjectNameMap[$subjectNameLower])) {
                                foreach ($subjectNameMap[$subjectNameLower] as $variant) {
                                    $q->orWhereRaw('LOWER(TRIM(subject)) = ?', [$variant]);
                                }
                            }
                        })
                        ->whereRaw('LOWER(TRIM(day)) = ?', [strtolower($dayName)])
                        ->where(function($q) use ($subjectClass) {
                            // Match class name (handle variations like "Four" = "4" = "four")
                            $q->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($subjectClass)]);
                            // Also try numeric matching if class is numeric
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
                        
                        // Add campus filter only if requested
                        if ($includeCampus && !empty($subjectCampus)) {
                            $query->where(function($q) use ($subjectCampus) {
                                // Match campus (case-insensitive) or allow null/empty
                                $q->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($subjectCampus)])
                                  ->orWhereNull('campus')
                                  ->orWhere('campus', '');
                            });
                        }
                        
                        return $query;
                    };
                    
                    // First try without campus filter (most flexible)
                    $timetableExists = $buildTimetableQuery(false)->exists();
                    
                    // If no results and subject has campus, try with campus filter
                    if (!$timetableExists && !empty($subjectCampus)) {
                        $timetableExists = $buildTimetableQuery(true)->exists();
                    }
                    
                    // If still no match, try more flexible matching: just subject name + day (ignore class/section)
                    if (!$timetableExists) {
                        $flexibleQuery = Timetable::where(function($q) use ($subjectNameLower, $subjectNameMap) {
                            $q->whereRaw('LOWER(TRIM(subject)) = ?', [$subjectNameLower]);
                            if (isset($subjectNameMap[$subjectNameLower])) {
                                foreach ($subjectNameMap[$subjectNameLower] as $variant) {
                                    $q->orWhereRaw('LOWER(TRIM(subject)) = ?', [$variant]);
                                }
                            }
                        })
                        ->whereRaw('LOWER(TRIM(day)) = ?', [strtolower($dayName)]);
                        
                        $timetableExists = $flexibleQuery->exists();
                    }
                    
                    // Only include subject if it has a timetable entry for this day
                    if (!$timetableExists) {
                        continue;
                    }
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
                    $hasClassOnDate = $this->hasClassOnDate($staff, $date, $dayName);
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
                    $timetableTime = $this->getTimeFromTimetable($staff, $date);
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
     * Check if per hour teacher has classes scheduled on a specific date
     */
    private function hasClassOnDate(Staff $staff, string $date, string $dayName): bool
    {
        $staffName = trim($staff->name ?? '');
        if (empty($staffName)) {
            return false;
        }

        // Get staff's assigned subjects from Subject table
        $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower($staffName)])
            ->whereNotNull('subject_name')
            ->whereNotNull('class')
            ->whereNotNull('section')
            ->get();

        if ($assignedSubjects->isEmpty()) {
            return false;
        }

        // Subject name mapping for flexible matching
        $subjectNameMap = [
            'maths' => ['maths', 'mathematics', 'math'],
            'mathematics' => ['maths', 'mathematics', 'math'],
            'english' => ['english', 'eng'],
            'urdu' => ['urdu'],
            'science' => ['science', 'sci'],
            'islamiat' => ['islamiat', 'islamic studies', 'islamic'],
            'social studies' => ['social studies', 'social', 'sst'],
        ];

        // Check if any of the staff's subjects have timetable entries on this day
        foreach ($assignedSubjects as $subject) {
            $subjectName = trim($subject->subject_name ?? '');
            $subjectClass = trim($subject->class ?? '');
            $subjectSection = trim($subject->section ?? '');
            $subjectCampus = trim($subject->campus ?? '');
            
            if (empty($subjectName) || empty($subjectClass) || empty($subjectSection)) {
                continue;
            }
            
            $subjectNameLower = strtolower($subjectName);
            
            // Build timetable query
            $timetableQuery = Timetable::where(function($query) use ($subjectNameLower, $subjectNameMap) {
                $query->whereRaw('LOWER(TRIM(subject)) = ?', [$subjectNameLower]);
                
                if (isset($subjectNameMap[$subjectNameLower])) {
                    foreach ($subjectNameMap[$subjectNameLower] as $variant) {
                        $query->orWhereRaw('LOWER(TRIM(subject)) = ?', [$variant]);
                    }
                }
            })
            ->whereRaw('LOWER(TRIM(day)) = ?', [strtolower($dayName)])
            ->where(function($query) use ($subjectClass) {
                $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($subjectClass)]);
                if (is_numeric($subjectClass)) {
                    $query->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower($this->numberToWord($subjectClass))]);
                } else {
                    $wordToNumber = $this->wordToNumber($subjectClass);
                    if ($wordToNumber !== null) {
                        $query->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower((string)$wordToNumber)]);
                    }
                }
            })
            ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($subjectSection)]);
            
            // Add campus filter if both subject and timetable have campus
            if (!empty($subjectCampus)) {
                $timetableQuery->where(function($query) use ($subjectCampus) {
                    $query->whereNull('campus')
                        ->orWhereRaw('LOWER(TRIM(campus)) = ?', [strtolower($subjectCampus)]);
                });
            }
            
            // Check if timetable entry exists
            if ($timetableQuery->exists()) {
                return true;
            }
            
            // Try without campus filter if first query didn't find anything
            if (!empty($subjectCampus)) {
                $timetableQueryWithoutCampus = Timetable::where(function($query) use ($subjectNameLower, $subjectNameMap) {
                    $query->whereRaw('LOWER(TRIM(subject)) = ?', [$subjectNameLower]);
                    if (isset($subjectNameMap[$subjectNameLower])) {
                        foreach ($subjectNameMap[$subjectNameLower] as $variant) {
                            $query->orWhereRaw('LOWER(TRIM(subject)) = ?', [$variant]);
                        }
                    }
                })
                ->whereRaw('LOWER(TRIM(day)) = ?', [strtolower($dayName)])
                ->where(function($query) use ($subjectClass) {
                    $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($subjectClass)]);
                    if (is_numeric($subjectClass)) {
                        $query->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower($this->numberToWord($subjectClass))]);
                    } else {
                        $wordToNumber = $this->wordToNumber($subjectClass);
                        if ($wordToNumber !== null) {
                            $query->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower((string)$wordToNumber)]);
                        }
                    }
                })
                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($subjectSection)]);
                
                if ($timetableQueryWithoutCampus->exists()) {
                    return true;
                }
            }
        }

        return false;
    }

    /**
     * Get time from timetable for per hour teacher
     */
    private function getTimeFromTimetable(Staff $staff, string $date): ?array
    {
        $staffName = trim($staff->name ?? '');
        if (empty($staffName)) {
            return null;
        }

        // Get staff's assigned subjects from Subject table
        $assignedSubjects = Subject::whereRaw('LOWER(TRIM(teacher)) = ?', [strtolower($staffName)])
            ->whereNotNull('subject_name')
            ->whereNotNull('class')
            ->whereNotNull('section')
            ->get();

        if ($assignedSubjects->isEmpty()) {
            return null;
        }

        // Get day name from date
        $dayName = Carbon::parse($date)->format('l'); // Monday, Tuesday, etc.
        
        $earliestStartTime = null;
        $latestEndTime = null;
        $totalHours = 0; // Total hours count from all timetable entries

        // Subject name mapping for flexible matching (Maths = Mathematics, etc.)
        $subjectNameMap = [
            'maths' => ['maths', 'mathematics', 'math'],
            'mathematics' => ['maths', 'mathematics', 'math'],
            'english' => ['english', 'eng'],
            'urdu' => ['urdu'],
            'science' => ['science', 'sci'],
            'islamiat' => ['islamiat', 'islamic studies', 'islamic'],
            'social studies' => ['social studies', 'social', 'sst'],
        ];

        // Get all timetable entries for this staff's subjects on this day
        foreach ($assignedSubjects as $subject) {
            $subjectName = trim($subject->subject_name ?? '');
            $subjectClass = trim($subject->class ?? '');
            $subjectSection = trim($subject->section ?? '');
            $subjectCampus = trim($subject->campus ?? '');
            
            if (empty($subjectName) || empty($subjectClass) || empty($subjectSection)) {
                continue;
            }
            
            // Build timetable query with flexible subject name matching
            $subjectNameLower = strtolower($subjectName);
            
            // First try with campus match if campus is provided
            $timetableQuery = Timetable::where(function($query) use ($subjectNameLower, $subjectNameMap) {
                // Exact match
                $query->whereRaw('LOWER(TRIM(subject)) = ?', [$subjectNameLower]);
                
                // Flexible matching for common subject name variations
                if (isset($subjectNameMap[$subjectNameLower])) {
                    foreach ($subjectNameMap[$subjectNameLower] as $variant) {
                        $query->orWhereRaw('LOWER(TRIM(subject)) = ?', [$variant]);
                    }
                }
            })
            ->whereRaw('LOWER(TRIM(day)) = ?', [strtolower($dayName)])
            ->where(function($query) use ($subjectClass) {
                // Match class name (handle variations like "Four" = "4" = "four")
                $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($subjectClass)]);
                // Also try numeric matching if class is numeric
                if (is_numeric($subjectClass)) {
                    $query->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower($this->numberToWord($subjectClass))]);
                } else {
                    $wordToNumber = $this->wordToNumber($subjectClass);
                    if ($wordToNumber !== null) {
                        $query->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower((string)$wordToNumber)]);
                    }
                }
            })
            ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($subjectSection)]);
            
            // Add campus filter if both subject and timetable have campus
            if (!empty($subjectCampus)) {
                $timetableQuery->where(function($query) use ($subjectCampus) {
                    // Match campus (case-insensitive)
                    $query->whereRaw('LOWER(TRIM(campus)) = ?', [strtolower($subjectCampus)])
                          // Also allow entries without campus if subject has campus
                          ->orWhereNull('campus')
                          ->orWhere('campus', '');
                });
            }
            
            $timetableEntries = $timetableQuery->get();
            
            // If no results with campus filter, try without campus filter (more flexible)
            if ($timetableEntries->isEmpty() && !empty($subjectCampus)) {
                $timetableQuery = Timetable::where(function($query) use ($subjectNameLower, $subjectNameMap) {
                    $query->whereRaw('LOWER(TRIM(subject)) = ?', [$subjectNameLower]);
                    if (isset($subjectNameMap[$subjectNameLower])) {
                        foreach ($subjectNameMap[$subjectNameLower] as $variant) {
                            $query->orWhereRaw('LOWER(TRIM(subject)) = ?', [$variant]);
                        }
                    }
                })
                ->whereRaw('LOWER(TRIM(day)) = ?', [strtolower($dayName)])
                ->where(function($query) use ($subjectClass) {
                    $query->whereRaw('LOWER(TRIM(class)) = ?', [strtolower($subjectClass)]);
                    if (is_numeric($subjectClass)) {
                        $query->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower($this->numberToWord($subjectClass))]);
                    } else {
                        $wordToNumber = $this->wordToNumber($subjectClass);
                        if ($wordToNumber !== null) {
                            $query->orWhereRaw('LOWER(TRIM(class)) = ?', [strtolower((string)$wordToNumber)]);
                        }
                    }
                })
                ->whereRaw('LOWER(TRIM(section)) = ?', [strtolower($subjectSection)]);
                
                $timetableEntries = $timetableQuery->get();
            }
            
            // Find earliest start time, latest end time, and calculate total hours
            foreach ($timetableEntries as $entry) {
                if (!empty($entry->starting_time) && !empty($entry->ending_time)) {
                    try {
                        // Parse time properly - handle both time string and Carbon object
                        $startTimeStr = is_object($entry->starting_time) ? $entry->starting_time->format('H:i') : $entry->starting_time;
                        $endTimeStr = is_object($entry->ending_time) ? $entry->ending_time->format('H:i') : $entry->ending_time;
                        
                        // Create Carbon instances from time format (H:i)
                        $startTime = Carbon::createFromFormat('H:i:s', $startTimeStr . ':00');
                        if (!$startTime) {
                            $startTime = Carbon::createFromFormat('H:i', $startTimeStr);
                        }
                        
                        $endTime = Carbon::createFromFormat('H:i:s', $endTimeStr . ':00');
                        if (!$endTime) {
                            $endTime = Carbon::createFromFormat('H:i', $endTimeStr);
                        }
                        
                        if ($startTime && $endTime) {
                            // Calculate hours for this period
                            $hours = $startTime->diffInMinutes($endTime) / 60;
                            $totalHours += $hours;
                            
                            if ($earliestStartTime === null || $startTime->lt($earliestStartTime)) {
                                $earliestStartTime = $startTime;
                            }
                            
                            if ($latestEndTime === null || $endTime->gt($latestEndTime)) {
                                $latestEndTime = $endTime;
                            }
                        }
                    } catch (\Exception $e) {
                        // Skip invalid times - log error for debugging
                        \Log::warning('Error parsing timetable time', [
                            'entry_id' => $entry->id ?? null,
                            'starting_time' => $entry->starting_time ?? null,
                            'ending_time' => $entry->ending_time ?? null,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }

        if ($earliestStartTime && $latestEndTime) {
            return [
                'start_time' => $earliestStartTime->format('H:i'),
                'end_time' => $latestEndTime->format('H:i'),
                'total_hours' => round($totalHours, 2), // Total hours from all timetable entries
            ];
        }

        return null;
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
    private function calculateLateArrivalWithTimetable($startTime, $timetableStartTime): ?string
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
    private function calculateEarlyExitWithTimetable($endTime, $timetableEndTime): ?string
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
    private function calculateLateArrival($startTime)
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
    private function calculateEarlyExit($endTime)
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
                
                $monthlySummary[] = [
                    'month' => $monthNum,
                    'month_name' => $monthName,
                    'present' => $monthAttendances->where('status', 'Present')->count(),
                    'absent' => $monthAttendances->where('status', 'Absent')->count(),
                    'leave' => $monthAttendances->where('status', 'Leave')->count(),
                    'holiday' => $monthAttendances->where('status', 'Holiday')->count(),
                    'sunday' => $monthAttendances->where('status', 'Sunday')->count(),
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
}

