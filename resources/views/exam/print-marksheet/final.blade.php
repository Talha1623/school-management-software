@if($isPrint && $filterCampus && $filterClass && $filterSection && $filterSession)
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Print Marksheet - For Final Result</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        html, body { width: 100%; height: 100%; margin: 0; padding: 0; overflow-x: hidden; }
        body { font-family: Arial, sans-serif; padding: 20px; background: white; }
        .sidebar-area, #sidebar-area, .sidebar, .navbar, .navbar-area, .header-area, #header-area,
        .main-header, .header-navbar, .theme-settings-area, .theme-settings, .settings-btn,
        .preloader, .footer-area, footer { display: none !important; }
        .marksheet-card { background: white; padding: 20px; margin-bottom: 30px; border: 1px solid #dee2e6; border-radius: 8px; }
        .page-break { page-break-after: always; }
        .page-break:last-child { page-break-after: auto; }
        .info-item { font-size: 13px; margin-bottom: 8px; color: #333; }
        .marksheet-table { font-size: 12px; margin-bottom: 15px; }
        .marksheet-table th, .marksheet-table td { padding: 8px 4px; text-align: center; vertical-align: middle; }
        .marksheet-table th { font-weight: 600; }
        .student-photo { width: 80px; height: 80px; object-fit: cover; border: 2px solid #003471; }
        .student-photo-placeholder { width: 80px; height: 80px; background: #e9ecef; border: 2px solid #003471; display: flex; align-items: center; justify-content: center; }
        .remarks-box { border: 1px solid #dee2e6; border-radius: 4px; min-height: 60px; padding: 10px; background: #f8f9fa; }
        .signature-box { border-top: 2px solid #333; padding-top: 10px; margin-top: 20px; }
        .print-date { margin-top: 20px; font-size: 12px; }
        .progress-overview .progress { background-color: #e9ecef; border: 1px solid #dee2e6; }
        .progress-overview .progress-bar { font-size: 11px; font-weight: 600; line-height: 18px; }
        @media print {
            @page { margin: 0.5cm; }
            body { padding: 0 !important; margin: 0 !important; background: white !important; }
            .marksheet-card { page-break-inside: avoid; margin: 0 !important; padding: 15px !important; border: 1px solid #000 !important; }
            .table th, .table td { border: 1px solid #000 !important; }
            .progress-overview .progress, .progress-overview .progress-bar {
                -webkit-print-color-adjust: exact;
                print-color-adjust: exact;
            }
        }
    </style>
</head>
<body>
@else
@extends('layouts.app')

@section('title', 'Print Marksheet - For Final Result')

@section('content')
@endif

@if(!($isPrint && $filterCampus && $filterClass && $filterSection && $filterSession))
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Print Marksheet - For Final Result</h4>
            </div>

            <form id="printMarksheetsForm" method="GET" action="{{ route('exam.print-marksheet.final') }}" target="_blank">
                <input type="hidden" name="print" value="1">
                <div class="row g-2 mb-3 align-items-end">
                    <div class="col-md-3">
                        <label for="filter_campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                        <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px;" required>
                            <option value="">Select Campus</option>
                            @foreach($campuses as $campus)
                                @php
                                    $campusName = is_object($campus) ? ($campus->campus_name ?? $campus->name ?? '') : $campus;
                                @endphp
                                <option value="{{ $campusName }}" {{ $filterCampus == $campusName ? 'selected' : '' }}>{{ $campusName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="filter_class" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class</label>
                        <select class="form-select form-select-sm" id="filter_class" name="filter_class" style="height: 32px;" required>
                            <option value="">Select Class</option>
                            @foreach(($filterClasses ?? $classes) as $className)
                                <option value="{{ $className }}" {{ $filterClass == $className ? 'selected' : '' }}>{{ $className }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="filter_section" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Section</label>
                        <select class="form-select form-select-sm" id="filter_section" name="filter_section" style="height: 32px;" required>
                            <option value="">Select Section</option>
                            @foreach($sections as $sectionName)
                                <option value="{{ $sectionName }}" {{ $filterSection == $sectionName ? 'selected' : '' }}>{{ $sectionName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-3">
                        <label for="filter_session" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Academic Session</label>
                        <select class="form-select form-select-sm" id="filter_session" name="filter_session" style="height: 32px;" required>
                            <option value="">Select Session</option>
                            @foreach($sessions as $session)
                                <option value="{{ $session }}" {{ $filterSession == $session ? 'selected' : '' }}>{{ $session }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm py-1 px-3 rounded-8 filter-btn w-100" style="height: 32px;">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">print</span>
                            <span style="font-size: 12px;">Print</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>
@endif

@if($isPrint && $filterCampus && $filterClass && $filterSection && $filterSession)
<div id="marksheetsPrintArea">
    @forelse($students as $student)
        @php
            $studentMarks = $marksByStudent->get($student->id, collect());
            $summary = $studentSummaries->get($student->id, []);
            $totalMarks = $summary['total_marks'] ?? 0;
            $totalPassing = $summary['total_passing'] ?? 0;
            $totalObtained = $summary['total_obtained'] ?? 0;
            $percentage = $summary['percentage'] ?? 0;
            $rank = $summary['rank'] ?? '-';
            $status = $summary['status'] ?? 'N/A';

            $calculatedGrade = null;
            if ($percentage > 0 && $gradeDefinitions->isNotEmpty()) {
                foreach ($gradeDefinitions as $gradeDef) {
                    if ($percentage >= $gradeDef->from_percentage && $percentage <= $gradeDef->to_percentage) {
                        $calculatedGrade = $gradeDef->name;
                        break;
                    }
                }
            }

            $rankDisplay = $rank;
            if ($rank == 1) $rankDisplay = '1st';
            elseif ($rank == 2) $rankDisplay = '2nd';
            elseif ($rank == 3) $rankDisplay = '3rd';
            elseif ($rank != '-') $rankDisplay = $rank . 'th';
        @endphp
        <div class="marksheet-card page-break">
            <div class="text-center mb-3">
                <div class="fw-semibold fs-18" style="color: #003471;">{{ $schoolName ?? 'School' }}</div>
                @if(!empty($schoolPhone))
                    <div class="text-muted fs-12">{{ $schoolPhone }}</div>
                @endif
                <div class="badge rounded-pill text-white mt-2 px-4 py-2" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); font-size: 14px;">
                    Result Card Final Result - Dated: {{ date('d-M-Y') }}
                </div>
            </div>

            <div class="row g-2 align-items-center mb-3">
                <div class="col-md-8">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <div class="info-item">
                                <strong>Student / Roll:</strong> {{ $student->student_name }} / {{ $student->student_code ?? 'N/A' }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <strong>Parent / CNIC:</strong> {{ $student->father_name ?? 'N/A' }} / {{ $student->father_id_card ?? 'N/A' }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <strong>Class / Section:</strong> {{ $student->class ?? 'N/A' }} / {{ $student->section ?? 'N/A' }}
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="info-item">
                                <strong>Campus / Session:</strong> {{ $student->campus ?? 'N/A' }} / {{ $filterSession ?: ($student->session ?? $runningSession ?? 'N/A') }}
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-4 text-end">
                    @if($student->photo)
                        <img src="{{ asset('storage/' . $student->photo) }}" alt="Photo" class="student-photo rounded-circle" style="width: 80px; height: 80px; object-fit: cover; border: 2px solid #003471;">
                    @else
                        <div class="student-photo-placeholder rounded-circle d-flex align-items-center justify-content-center" style="width: 80px; height: 80px; background: #e9ecef; border: 2px solid #003471; margin-left: auto;">
                            <span class="material-symbols-outlined" style="font-size: 40px; color: #003471;">person</span>
                        </div>
                    @endif
                </div>
            </div>

            <div class="table-responsive mb-3">
                <table class="table table-bordered marksheet-table">
                    <thead>
                        <tr style="background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);">
                            <th colspan="3" class="text-center">Subjects</th>
                            <th colspan="5" class="text-center">Performance</th>
                        </tr>
                        <tr style="background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);">
                            <th>Subject Name</th>
                            <th>Total M.</th>
                            <th>Passing M.</th>
                            <th>Obtained</th>
                            <th>Percentage</th>
                            <th>Result</th>
                            <th>Highest In Class</th>
                            <th>Teacher Remarks</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($studentMarks as $mark)
                            @php
                                $subjectTotal = (float) ($mark->total_marks ?? 0);
                                $subjectPassing = (float) ($mark->passing_marks ?? 0);
                                $subjectObtained = (float) ($mark->marks_obtained ?? 0);
                                $subjectPercent = $subjectTotal > 0 ? round(($subjectObtained / $subjectTotal) * 100, 2) : 0;
                                $subjectResult = $subjectObtained >= $subjectPassing ? 'Pass' : 'Fail';
                                $highest = $highestBySubject->get(trim((string) ($mark->subject ?? ''))) ?? 0;
                            @endphp
                            <tr>
                                <td><strong>{{ $mark->subject ?? 'N/A' }}</strong></td>
                                <td>{{ $subjectTotal ?: '-' }}</td>
                                <td>{{ $subjectPassing ?: '-' }}</td>
                                <td>{{ $subjectObtained ?: '-' }}</td>
                                <td>{{ $subjectPercent }}%</td>
                                <td>
                                    <span class="badge {{ $subjectResult == 'Pass' ? 'bg-success' : 'bg-danger' }}">{{ $subjectResult }}</span>
                                </td>
                                <td>{{ $highest }}</td>
                                <td>{{ $mark->teacher_remarks ?? '-' }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            <div class="table-responsive mb-3">
                <table class="table table-bordered marksheet-table">
                    <thead>
                        <tr style="background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);">
                            <th colspan="7" class="text-center">Result Overview</th>
                        </tr>
                        <tr style="background: linear-gradient(135deg, #d4edda 0%, #c3e6cb 100%);">
                            <th>Total</th>
                            <th>Passing Marks</th>
                            <th>%</th>
                            <th>Rank</th>
                            <th>Grade</th>
                            <th>Status</th>
                            <th>Final Result</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td><strong>{{ $totalObtained }} / {{ $totalMarks }}</strong></td>
                            <td><strong>{{ $totalPassing }}</strong></td>
                            <td><strong>{{ $percentage }}%</strong></td>
                            <td><strong>{{ $rankDisplay }}</strong></td>
                            <td><strong class="badge bg-success">{{ $calculatedGrade ?? '-' }}</strong></td>
                            <td><strong class="badge {{ $status == 'PASS' ? 'bg-success' : 'bg-danger' }}">{{ $status }}</strong></td>
                            <td><strong>{{ $calculatedGrade ?? '-' }}</strong></td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <div class="mb-3 progress-overview">
                <h6 class="fw-semibold mb-2" style="color: #003471;">Progress Overview</h6>
                @if($studentMarks->isNotEmpty())
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center small mb-1">
                            <strong>Overall Performance</strong>
                            <span>{{ $totalObtained }} / {{ $totalMarks }} ({{ $percentage }}%)</span>
                        </div>
                        <div class="progress" style="height: 22px;">
                            <div class="progress-bar {{ $status == 'PASS' ? 'bg-success' : 'bg-danger' }}" role="progressbar" style="width: {{ min(100, max(0, $percentage)) }}%;">{{ $percentage }}%</div>
                        </div>
                    </div>
                    @foreach($studentMarks as $mark)
                        @php
                            $subjectTotal = (float) ($mark->total_marks ?? 0);
                            $subjectObtained = (float) ($mark->marks_obtained ?? 0);
                            $subjectPassing = (float) ($mark->passing_marks ?? 0);
                            $subjectPercent = $subjectTotal > 0 ? round(($subjectObtained / $subjectTotal) * 100, 2) : 0;
                            $subjectPassed = $subjectObtained >= $subjectPassing;
                        @endphp
                        <div class="mb-2">
                            <div class="d-flex justify-content-between align-items-center small mb-1">
                                <span><strong>{{ $mark->subject ?? 'N/A' }}</strong></span>
                                <span>{{ $subjectObtained }} / {{ $subjectTotal }} ({{ $subjectPercent }}%)</span>
                            </div>
                            <div class="progress" style="height: 18px;">
                                <div class="progress-bar {{ $subjectPassed ? 'bg-success' : 'bg-danger' }}" role="progressbar" style="width: {{ min(100, max(0, $subjectPercent)) }}%;">{{ $subjectPercent }}%</div>
                            </div>
                        </div>
                    @endforeach
                @else
                    <div class="text-muted small">No subject performance data available.</div>
                @endif
            </div>

            <div class="mb-3">
                <h6 class="fw-semibold mb-2" style="color: #003471;">Class Teacher Remarks</h6>
                <div class="remarks-box p-2">
                    {{ $classTeacherRemarks->get($student->id) ?? '-' }}
                </div>
            </div>

            <div class="row mt-4">
                <div class="col-md-6">
                    <div class="signature-box"><strong>Stamp / Signature</strong></div>
                </div>
                <div class="col-md-6 text-end">
                    <div class="print-date"><strong>Print Date:</strong> {{ date('d-m-Y h:i:s A') }}</div>
                </div>
            </div>
        </div>
    @empty
        <div class="text-center py-5">
            <p class="text-muted">No students found</p>
        </div>
    @endforelse
</div>
@endif

@if(!($isPrint && $filterCampus && $filterClass && $filterSection && $filterSession))
<style>
.filter-btn {
    background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
    color: white;
    border: none;
    transition: all 0.3s ease;
}
.filter-btn:hover {
    background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 52, 113, 0.3);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const campusSelect = document.getElementById('filter_campus');
    const classSelect = document.getElementById('filter_class');
    const sectionSelect = document.getElementById('filter_section');

    function loadSections(selectedClass) {
        if (!selectedClass || !sectionSelect) {
            if (sectionSelect) sectionSelect.innerHTML = '<option value="">Select Section</option>';
            return;
        }

        sectionSelect.innerHTML = '<option value="">Loading...</option>';
        const params = new URLSearchParams({ class: selectedClass });
        if (campusSelect && campusSelect.value) params.append('campus', campusSelect.value);

        fetch(`{{ route('exam.timetable.get-sections') }}?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                sectionSelect.innerHTML = '<option value="">Select Section</option>';
                (data || []).forEach(section => {
                    sectionSelect.innerHTML += `<option value="${section}">${section}</option>`;
                });
            })
            .catch(() => {
                sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
            });
    }

    if (classSelect) {
        classSelect.addEventListener('change', function() {
            loadSections(this.value);
        });
    }
});
</script>
@endif

@if($isPrint && $filterCampus && $filterClass && $filterSection && $filterSession)
<script>
    window.onload = function() {
        window.print();
    };
</script>
</body>
</html>
@else
@endsection
@endif
