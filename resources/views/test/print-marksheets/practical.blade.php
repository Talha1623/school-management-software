@if($isPrint && $filterCampus && $filterClass && $filterTest)
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Print Marksheets - For Practical Test</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        html, body {
            width: 100%;
            height: 100%;
            margin: 0;
            padding: 0;
            overflow-x: hidden;
        }
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
            background: white;
        }
        /* Hide any sidebar or navigation if somehow included */
        .sidebar-area,
        #sidebar-area,
        .sidebar,
        .navbar,
        .navbar-area,
        .header-area,
        #header-area,
        .main-header,
        .header-navbar,
        .theme-settings-area,
        .theme-settings,
        .settings-btn,
        .preloader,
        .footer-area,
        footer,
        .container-fluid > .sidebar-area,
        .main-content > .sidebar-area {
            display: none !important;
            visibility: hidden !important;
            width: 0 !important;
            height: 0 !important;
            overflow: hidden !important;
        }
        .marksheet-card {
            background: white;
            padding: 20px;
            margin-bottom: 30px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
        }
        .page-break {
            page-break-after: always;
        }
        .page-break:last-child {
            page-break-after: auto;
        }
        .info-item {
            font-size: 13px;
            margin-bottom: 8px;
            color: #333;
        }
        .marksheet-table {
            font-size: 12px;
            margin-bottom: 15px;
        }
        .marksheet-table th {
            font-weight: 600;
            text-align: center;
            padding: 8px 4px;
            vertical-align: middle;
        }
        .marksheet-table td {
            padding: 8px 4px;
            text-align: center;
            vertical-align: middle;
        }
        .student-photo {
            width: 80px;
            height: 80px;
            object-fit: cover;
            border: 2px solid #003471;
        }
        .student-photo-placeholder {
            width: 80px;
            height: 80px;
            background: #e9ecef;
            border: 2px solid #003471;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .remarks-box {
            border: 1px solid #dee2e6;
            border-radius: 4px;
            min-height: 60px;
            padding: 10px;
            background: #f8f9fa;
        }
        .signature-box {
            border-top: 2px solid #333;
            padding-top: 10px;
            margin-top: 20px;
        }
        .print-date {
            margin-top: 20px;
            font-size: 12px;
        }
        @media print {
            @page {
                margin: 0.5cm;
            }
            * {
                margin: 0;
                padding: 0;
            }
            html, body {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            body {
                padding: 0 !important;
                margin: 0 !important;
                background: white !important;
            }
            /* Hide sidebar and all navigation elements */
            .sidebar-area,
            #sidebar-area,
            .sidebar,
            .navbar,
            .navbar-area,
            .header-area,
            #header-area,
            .main-header,
            .header-navbar,
            .theme-settings-area,
            .theme-settings,
            .settings-btn,
            .preloader,
            .footer-area,
            footer {
                display: none !important;
                visibility: hidden !important;
                width: 0 !important;
                height: 0 !important;
                overflow: hidden !important;
                position: absolute !important;
                left: -9999px !important;
            }
            .marksheet-card {
                page-break-inside: avoid;
                margin: 0 !important;
                padding: 15px !important;
                border: 1px solid #000 !important;
            }
            .page-break {
                page-break-after: always;
            }
            .page-break:last-child {
                page-break-after: auto;
            }
            /* Ensure tables print properly */
            .table {
                border-collapse: collapse !important;
            }
            .table th,
            .table td {
                border: 1px solid #000 !important;
            }
        }
    </style>
</head>
<body>
@else
@extends('layouts.app')

@section('title', 'Print Marksheets - For Practical Test')

@section('content')
@endif

@if(!($isPrint && $filterCampus && $filterClass && $filterTest))
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Print Marksheets - For Practical Test</h4>
            </div>

            <form id="printMarksheetsForm" method="GET" action="{{ route('test.print-marksheets.practical') }}" target="_blank">
                <input type="hidden" name="print" value="1">
                <div class="row g-2 mb-3 align-items-end">
                    <!-- Campus -->
                    <div class="col-md-2">
                        <label for="campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                        <select class="form-select form-select-sm" id="campus" name="campus" style="height: 32px;" required>
                            <option value="">Select Campus</option>
                            @foreach($campusesList as $campusName)
                                <option value="{{ $campusName }}" {{ $filterCampus == $campusName ? 'selected' : '' }}>{{ $campusName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Class -->
                    <div class="col-md-2">
                        <label for="class" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class</label>
                        <select class="form-select form-select-sm" id="class" name="class" style="height: 32px;" required>
                            <option value="">Select Class</option>
                            @foreach($classes as $className)
                                <option value="{{ $className }}" {{ $filterClass == $className ? 'selected' : '' }}>{{ $className }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Section -->
                    <div class="col-md-2">
                        <label for="section" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Section</label>
                        <select class="form-select form-select-sm" id="section" name="section" style="height: 32px;">
                            <option value="">All Sections</option>
                            @if($filterClass)
                                @foreach($sections as $sectionName)
                                    <option value="{{ $sectionName }}" {{ $filterSection == $sectionName ? 'selected' : '' }}>{{ $sectionName }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <!-- Subject -->
                    <div class="col-md-2">
                        <label for="subject" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Subject</label>
                        <select class="form-select form-select-sm" id="subject" name="subject" style="height: 32px;">
                            <option value="">All Subjects</option>
                            @if($filterClass)
                                @foreach($subjects as $subjectName)
                                    <option value="{{ $subjectName }}" {{ $filterSubject == $subjectName ? 'selected' : '' }}>{{ $subjectName }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <!-- Test -->
                    <div class="col-md-2">
                        <label for="test" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Test</label>
                        <select class="form-select form-select-sm" id="test" name="test" style="height: 32px;" required>
                            <option value="">Select Test</option>
                            @foreach($tests as $testName)
                                <option value="{{ $testName }}" {{ $filterTest == $testName ? 'selected' : '' }}>{{ $testName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Button -->
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

@if($isPrint && $filterCampus && $filterClass && $filterTest)
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
                        
                        // Calculate grade
                        $calculatedGrade = null;
                        if ($percentage > 0 && $gradeDefinitions->isNotEmpty()) {
                            foreach ($gradeDefinitions as $gradeDef) {
                                if ($percentage >= $gradeDef->from_percentage && $percentage <= $gradeDef->to_percentage) {
                                    $calculatedGrade = $gradeDef->name;
                                    break;
                                }
                            }
                        }
                        
                        // Format rank
                        $rankDisplay = $rank;
                        if ($rank == 1) $rankDisplay = '1st';
                        elseif ($rank == 2) $rankDisplay = '2nd';
                        elseif ($rank == 3) $rankDisplay = '3rd';
                        elseif ($rank != '-') $rankDisplay = $rank . 'th';
                    @endphp
                    <div class="marksheet-card page-break">
                        <!-- Header -->
                        <div class="text-center mb-3">
                            <div class="d-flex align-items-center justify-content-center mb-2">
                                <div class="school-logo me-3">
                                    <div class="logo-circle bg-success rounded-circle d-flex align-items-center justify-content-center" style="width: 60px; height: 60px;">
                                        <span class="text-white fw-bold fs-20">DV</span>
                                    </div>
                                </div>
                                <div>
                                    <div class="fw-semibold fs-18" style="color: #003471;">Defence View</div>
                                    <div class="text-muted fs-12">+923316074246</div>
                                </div>
                            </div>
                            <div class="badge rounded-pill text-white mt-2 px-4 py-2" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); font-size: 14px;">
                                Result Card {{ $filterTest }} - Dated: {{ date('d-M-Y') }}
                            </div>
                        </div>

                        <!-- Student Information -->
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
                                            <strong>Campus / Session:</strong> {{ $student->campus ?? 'N/A' }} / {{ $testSession ?? 'N/A' }}
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

                        <!-- Subjects Performance Table -->
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
                                            $subjectTotal = (float)($mark->total_marks ?? 0);
                                            $subjectPassing = (float)($mark->passing_marks ?? 0);
                                            $subjectObtained = (float)($mark->marks_obtained ?? 0);
                                            $subjectPercent = $subjectTotal > 0 ? round(($subjectObtained / $subjectTotal) * 100, 2) : 0;
                                            $subjectResult = $subjectObtained >= $subjectPassing ? 'Pass' : 'Fail';
                                            $highest = $highestBySubject->get($mark->subject) ?? 0;
                                        @endphp
                                        <tr>
                                            <td><strong>{{ $mark->subject ?? 'N/A' }}</strong></td>
                                            <td>{{ $subjectTotal ?: '-' }}</td>
                                            <td>{{ $subjectPassing ?: '-' }}</td>
                                            <td>{{ $subjectObtained ?: '-' }}</td>
                                            <td>{{ $subjectPercent }}%</td>
                                            <td>
                                                <span class="badge {{ $subjectResult == 'Pass' ? 'bg-success' : 'bg-danger' }}">
                                                    {{ $subjectResult }}
                                                </span>
                                            </td>
                                            <td>{{ $highest }}</td>
                                            <td>{{ $mark->teacher_remarks ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        <!-- Result Overview Table -->
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

                        <!-- Progress Overview -->
                        <div class="mb-3">
                            <h6 class="fw-semibold mb-2" style="color: #003471;">Progress Overview</h6>
                            <div id="progressChart{{ $student->id }}" style="height: 200px;"></div>
                        </div>

                        <!-- Class Teacher Remarks -->
                        <div class="mb-3">
                            <h6 class="fw-semibold mb-2" style="color: #003471;">Class Teacher Remarks</h6>
                            <div class="remarks-box p-2" style="border: 1px solid #dee2e6; border-radius: 4px; min-height: 60px;">
                                {{ $classTeacherRemarks->get($student->id) ?? '-' }}
                            </div>
                        </div>

                        <!-- Footer -->
                        <div class="row mt-4">
                            <div class="col-md-6">
                                <div class="signature-box">
                                    <strong>Stamp / Signature</strong>
                                </div>
                            </div>
                            <div class="col-md-6 text-end">
                                <div class="print-date">
                                    <strong>Print Date:</strong> {{ date('d-m-Y h:i:s A') }}
                                </div>
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

@if(!($isPrint && $filterCampus && $filterClass && $filterTest))
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

/* Marksheet Styles */
.marksheet-card {
    background: white;
    padding: 20px;
    margin-bottom: 30px;
    border: 1px solid #dee2e6;
    border-radius: 8px;
}

.page-break {
    page-break-after: always;
}

.page-break:last-child {
    page-break-after: auto;
}

.info-item {
    font-size: 13px;
    margin-bottom: 8px;
    color: #333;
}

.marksheet-table {
    font-size: 12px;
    margin-bottom: 15px;
}

.marksheet-table th {
    font-weight: 600;
    text-align: center;
    padding: 8px 4px;
    vertical-align: middle;
}

.marksheet-table td {
    padding: 8px 4px;
    text-align: center;
    vertical-align: middle;
}

.student-photo {
    width: 80px;
    height: 80px;
    object-fit: cover;
    border: 2px solid #003471;
}

.student-photo-placeholder {
    width: 80px;
    height: 80px;
    background: #e9ecef;
    border: 2px solid #003471;
    display: flex;
    align-items: center;
    justify-content: center;
}

.remarks-box {
    border: 1px solid #dee2e6;
    border-radius: 4px;
    min-height: 60px;
    padding: 10px;
    background: #f8f9fa;
}

.signature-box {
    border-top: 2px solid #333;
    padding-top: 10px;
    margin-top: 20px;
}

.print-date {
    margin-top: 20px;
    font-size: 12px;
}

/* Print Styles */
@media print {
    /* Hide sidebar and navigation */
    .sidebar-area,
    #sidebar-area,
    .sidebar,
    .navbar,
    .navbar-area,
    .header-area,
    #header-area,
    .main-header,
    .header-navbar,
    .theme-settings-area,
    .theme-settings,
    .settings-btn,
    .preloader,
    .footer-area,
    footer {
        display: none !important;
        visibility: hidden !important;
    }
    
    /* Hide filter form and buttons */
    .filter-btn,
    .print-btn,
    #printMarksheetsForm,
    .no-print,
    .mb-2.p-2.rounded-8,
    .d-flex.justify-content-between,
    h4.mb-0,
    .card-header,
    .card.bg-white.border.border-white.rounded-10.p-3.mb-4:first-child {
        display: none !important;
    }
    
    /* Reset body and container */
    body {
        background: white !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    
    .main-content,
    .main-content-container {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        margin-left: 0 !important;
    }
    
    .container-fluid {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
    }
    
    .row {
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .col-12 {
        padding: 0 !important;
        width: 100% !important;
    }
    
    /* Card styles */
    .card {
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
        margin: 0 !important;
        background: white !important;
    }
    
    /* Marksheet card */
    .marksheet-card {
        border: 1px solid #000;
        page-break-inside: avoid;
        margin: 0 !important;
        padding: 20px !important;
    }
    
    .page-break {
        page-break-after: always;
    }
    
    .page-break:last-child {
        page-break-after: auto;
    }
    
    /* Ensure full width */
    #marksheetsPrintArea {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }
    
    /* Hide print button header */
    .mb-2.p-2.rounded-8.d-flex.align-items-center.justify-content-between {
        display: none !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selects = {
        campus: document.getElementById('campus'),
        class: document.getElementById('class'),
        section: document.getElementById('section'),
        subject: document.getElementById('subject'),
        test: document.getElementById('test')
    };

    function loadOptions(select, route, params, placeholder) {
        select.disabled = false;
        select.innerHTML = '<option value="">Loading...</option>';
        
        const queryString = new URLSearchParams(params).toString();
        fetch(`${route}?${queryString}`)
            .then(response => response.json())
            .then(data => {
                select.innerHTML = `<option value="">${placeholder}</option>`;
                data.forEach(item => {
                    select.innerHTML += `<option value="${item}">${item}</option>`;
                });
            })
            .catch(error => {
                console.error('Error loading options:', error);
                select.innerHTML = `<option value="">Error loading</option>`;
            });
    }

    function resetSelect(select, placeholder) {
        select.disabled = true;
        select.innerHTML = `<option value="">${placeholder}</option>`;
    }

    selects.class.addEventListener('change', function() {
        const selectedClass = this.value;
        const selectedCampus = selects.campus.value;
        if (selectedClass) {
            const params = {class: selectedClass};
            if (selectedCampus) params.campus = selectedCampus;
            loadOptions(selects.section, '{{ route("test.print-marksheets.get-sections") }}', params, 'All Sections');
            selects.section.disabled = false;
            selects.section.innerHTML = '<option value="">All Sections</option>';
            // Load subjects when class changes
            const subjectParams = {class: selectedClass};
            if (selectedCampus) subjectParams.campus = selectedCampus;
            loadOptions(selects.subject, '{{ route("test.print-marksheets.get-subjects") }}', subjectParams, 'All Subjects');
            selects.subject.disabled = false;
            selects.subject.innerHTML = '<option value="">All Subjects</option>';
            // Load tests
            const testParams = {class: selectedClass};
            if (selectedCampus) testParams.campus = selectedCampus;
            loadOptions(selects.test, '{{ route("test.print-marksheets.get-tests") }}', testParams, 'Select Test');
            selects.test.disabled = false;
        } else {
            resetSelect(selects.section, 'All Sections');
            resetSelect(selects.subject, 'All Subjects');
            resetSelect(selects.test, 'Select Test');
        }
    });

    selects.section.addEventListener('change', function() {
        const selectedSection = this.value;
        const selectedClass = selects.class.value;
        const selectedCampus = selects.campus.value;
        if (selectedClass) {
            const params = {class: selectedClass};
            if (selectedCampus) params.campus = selectedCampus;
            if (selectedSection) params.section = selectedSection;
            loadOptions(selects.subject, '{{ route("test.print-marksheets.get-subjects") }}', params, 'All Subjects');
            selects.subject.disabled = false;
            // Reload tests
            const testParams = {class: selectedClass};
            if (selectedCampus) testParams.campus = selectedCampus;
            if (selectedSection) testParams.section = selectedSection;
            loadOptions(selects.test, '{{ route("test.print-marksheets.get-tests") }}', testParams, 'Select Test');
            selects.test.disabled = false;
        }
    });

    selects.subject.addEventListener('change', function() {
        const selectedSubject = this.value;
        const selectedSection = selects.section.value;
        const selectedClass = selects.class.value;
        const selectedCampus = selects.campus.value;
        if (selectedClass) {
            const params = {class: selectedClass};
            if (selectedCampus) params.campus = selectedCampus;
            if (selectedSection) params.section = selectedSection;
            if (selectedSubject) params.subject = selectedSubject;
            loadOptions(selects.test, '{{ route("test.print-marksheets.get-tests") }}', params, 'Select Test');
            selects.test.disabled = false;
        }
    });
    
    // Reload dependent fields when campus changes
    selects.campus.addEventListener('change', function() {
        if (selects.class.value) {
            selects.class.dispatchEvent(new Event('change'));
        }
    });

    // Initialize progress charts if marksheets are displayed
    @if($isPrint && $students->isNotEmpty())
        @foreach($students as $student)
            @php
                $studentMarks = $marksByStudent->get($student->id, collect());
                $summary = $studentSummaries->get($student->id, []);
                $totalMarks = $summary['total_marks'] ?? 0;
                $totalObtained = $summary['total_obtained'] ?? 0;
            @endphp
            @if($studentMarks->isNotEmpty())
                // Chart data for student {{ $student->id }}
                const chartData{{ $student->id }} = {
                    labels: {!! json_encode($studentMarks->pluck('subject')->toArray()) !!},
                    datasets: [{
                        label: 'Obtained Marks',
                        data: {!! json_encode($studentMarks->map(function($m) { return (float)($m->marks_obtained ?? 0); })->toArray()) !!},
                        backgroundColor: 'rgba(40, 167, 69, 0.6)',
                        borderColor: 'rgba(40, 167, 69, 1)',
                        borderWidth: 1
                    }, {
                        label: 'Total Marks',
                        data: {!! json_encode($studentMarks->map(function($m) { return (float)($m->total_marks ?? 0); })->toArray()) !!},
                        backgroundColor: 'rgba(255, 99, 132, 0.6)',
                        borderColor: 'rgba(255, 99, 132, 1)',
                        borderWidth: 1
                    }]
                };
                
                // Simple bar chart using CSS (fallback if Chart.js not available)
                const chartContainer{{ $student->id }} = document.getElementById('progressChart{{ $student->id }}');
                if (chartContainer{{ $student->id }}) {
                    chartContainer{{ $student->id }}.innerHTML = `
                        <div style="display: flex; align-items: flex-end; height: 100%; gap: 10px; padding: 10px;">
                            @foreach($studentMarks as $mark)
                                @php
                                    $subjectTotal = (float)($mark->total_marks ?? 0);
                                    $subjectObtained = (float)($mark->marks_obtained ?? 0);
                                    $maxHeight = $studentMarks->max(function($m) { return (float)($m->total_marks ?? 0); });
                                    $obtainedHeight = $maxHeight > 0 ? ($subjectObtained / $maxHeight) * 100 : 0;
                                    $totalHeight = $maxHeight > 0 ? ($subjectTotal / $maxHeight) * 100 : 0;
                                @endphp
                                <div style="flex: 1; display: flex; flex-direction: column; align-items: center;">
                                    <div style="width: 100%; height: 180px; display: flex; flex-direction: column; justify-content: flex-end; gap: 2px;">
                                        <div style="width: 100%; height: {{ $obtainedHeight }}%; background: rgba(40, 167, 69, 0.6); border: 1px solid rgba(40, 167, 69, 1); border-radius: 4px 4px 0 0;" title="Obtained: {{ $subjectObtained }}"></div>
                                        <div style="width: 100%; height: {{ max(0, $totalHeight - $obtainedHeight) }}%; background: rgba(255, 99, 132, 0.6); border: 1px solid rgba(255, 99, 132, 1); border-radius: 0 0 4px 4px;" title="Total: {{ $subjectTotal }}"></div>
                                    </div>
                                    <div style="font-size: 10px; margin-top: 5px; text-align: center; word-break: break-word;">{{ $mark->subject }}</div>
                                </div>
                            @endforeach
                        </div>
                        <div style="display: flex; gap: 20px; margin-top: 10px; font-size: 11px;">
                            <div><span style="display: inline-block; width: 15px; height: 15px; background: rgba(40, 167, 69, 0.6); border: 1px solid rgba(40, 167, 69, 1); margin-right: 5px;"></span> Obtained Marks</div>
                            <div><span style="display: inline-block; width: 15px; height: 15px; background: rgba(255, 99, 132, 0.6); border: 1px solid rgba(255, 99, 132, 1); margin-right: 5px;"></span> Total Marks</div>
                        </div>
                    `;
                }
            @endif
        @endforeach
    @endif
});
</script>
@endif

@if($isPrint && $filterCampus && $filterClass && $filterTest)
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
