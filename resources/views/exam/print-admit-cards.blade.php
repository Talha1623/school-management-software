@if($isPrint && $filterCampus && $filterClass)
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Print Admit Cards / Slip</title>
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
        table {
            font-size: 12px;
            width: 100%;
        }
        .table th,
        .table td {
            padding: 8px;
            border: 1px solid #000;
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
            .table {
                border-collapse: collapse !important;
            }
            .table th,
            .table td {
                border: 1px solid #000 !important;
            }
            .page-break {
                page-break-after: always;
            }
        }
    </style>
</head>
<body>
<div class="container-fluid">
    @if($filterType == 'admit slip')
        <!-- Admit Slip Format -->
        @forelse($students as $index => $student)
            <div class="mb-4 p-3 border rounded-8" style="page-break-after: always; border: 1px solid #dee2e6;">
                <!-- Student Information Table -->
                <table class="table table-bordered table-sm mb-3">
                    <thead>
                        <tr>
                            <th>Seat#</th>
                            <th>Roll</th>
                            <th>Student</th>
                            <th>Parent</th>
                            <th>Class</th>
                            <th>Section</th>
                            <th>Campus</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $student->student_code ?? 'N/A' }}</td>
                            <td>{{ $student->student_name }}</td>
                            <td>{{ $student->father_name ?? 'N/A' }}</td>
                            <td>{{ $student->class ?? 'N/A' }}</td>
                            <td>{{ $student->section ?? 'N/A' }}</td>
                            <td>{{ $student->campus ?? 'N/A' }}</td>
                        </tr>
                    </tbody>
                </table>
                
                <!-- Exam Timetable Table -->
                @if($examTimetables->isNotEmpty())
                    <table class="table table-bordered table-sm mb-3">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Day</th>
                                <th>Subject</th>
                                <th>Starts At</th>
                                <th>Ends At</th>
                                <th>Total Time</th>
                                <th>Room/Block</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($examTimetables as $timetable)
                                @php
                                    $start = \Carbon\Carbon::createFromFormat('H:i:s', $timetable->starting_time);
                                    $end = \Carbon\Carbon::createFromFormat('H:i:s', $timetable->ending_time);
                                    if ($end->lessThan($start)) {
                                        $end = $end->addDay();
                                    }
                                    $diff = $start->diff($end);
                                    $totalTime = sprintf('%d hour & %d minutes', ($diff->h + ($diff->d * 24)), $diff->i);
                                @endphp
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($timetable->exam_date)->format('d-m-Y') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($timetable->exam_date)->format('l') }}</td>
                                    <td>{{ $timetable->subject }}</td>
                                    <td>{{ \Carbon\Carbon::createFromFormat('H:i:s', $timetable->starting_time)->format('h:i A') }}</td>
                                    <td>{{ \Carbon\Carbon::createFromFormat('H:i:s', $timetable->ending_time)->format('h:i A') }}</td>
                                    <td>{{ $totalTime }}</td>
                                    <td>{{ $timetable->room_block ?? 'N/A' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                @endif
                
                <!-- Exam Settings Instructions -->
                @if($examSettings && $examSettings->admit_card_instructions)
                    <div class="mt-3 p-3 bg-light rounded-8">
                        <h6 class="fw-semibold mb-2" style="color: #003471;">Instructions:</h6>
                        <div class="small">{!! nl2br(e($examSettings->admit_card_instructions)) !!}</div>
                    </div>
                @endif
            </div>
        @empty
            <div class="text-center text-muted py-4">No students found for the selected filters.</div>
        @endforelse
    @elseif($filterType == 'admit card slip' || !$filterType)
        <!-- Admit Card Slip Format -->
        <div class="row g-3">
            @forelse($students as $student)
                <div class="col-md-6">
                    <div class="border rounded-8 p-3 h-100" style="border: 1px solid #dee2e6;">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <div class="fw-semibold" style="color: #003471;">Admit Card</div>
                            <div class="small text-muted">{{ $filterExam ?: 'Exam' }}</div>
                        </div>
                        <div class="d-flex gap-3">
                            <div>
                                @if($student->photo)
                                    <img src="{{ asset('storage/' . $student->photo) }}" alt="Photo" style="width: 70px; height: 70px; border-radius: 8px; object-fit: cover;">
                                @else
                                    <div style="width: 70px; height: 70px; border-radius: 8px; background-color: #e0e7ff; display: flex; align-items: center; justify-content: center;">
                                        <span class="material-symbols-outlined" style="font-size: 28px; color: #003471;">person</span>
                                    </div>
                                @endif
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-semibold">{{ $student->student_name }}</div>
                                <div class="small text-muted">Student Code: {{ $student->student_code ?? 'N/A' }}</div>
                                <div class="small text-muted">Class/Section: {{ $student->class ?? 'N/A' }}/{{ $student->section ?? 'N/A' }}</div>
                                <div class="small text-muted">Campus: {{ $student->campus ?? 'N/A' }}</div>
                            </div>
                        </div>
                        <div class="mt-3 small">
                            <div><strong>Exam:</strong> {{ $filterExam ?: 'N/A' }}</div>
                            <div><strong>Type:</strong> {{ $filterType ?: 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            @empty
                <div class="col-12 text-center text-muted py-4">No students found for the selected filters.</div>
            @endforelse
        </div>
    @endif
</div>
<script>
window.onload = function() {
    window.print();
};
</script>
</body>
</html>
@else
@extends('layouts.app')

@section('title', 'Print Admit Cards / Slip')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Print Admit Cards / Slip</h4>
            </div>

            <!-- Filter Form -->
            <form action="{{ route('exam.print-admit-cards') }}" method="GET" id="filterForm" target="_blank">
                <div class="row g-2 mb-3 align-items-end">
                    <!-- Campus -->
                    <div class="col-md-2">
                        <label for="filter_campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                        <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px;">
                            <option value="">All Campuses</option>
                            @foreach($campuses as $campus)
                                <option value="{{ $campus }}" {{ $filterCampus == $campus ? 'selected' : '' }}>{{ $campus }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Exam -->
                    <div class="col-md-3">
                        <label for="filter_exam" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Exam</label>
                        <select class="form-select form-select-sm" id="filter_exam" name="filter_exam" style="height: 32px;">
                            <option value="">All Exams</option>
                            @foreach($exams as $examName)
                                <option value="{{ $examName }}" {{ $filterExam == $examName ? 'selected' : '' }}>{{ $examName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Class -->
                    <div class="col-md-2">
                        <label for="filter_class" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class</label>
                        <select class="form-select form-select-sm" id="filter_class" name="filter_class" style="height: 32px;">
                            <option value="">All Classes</option>
                            @foreach(($filterClasses ?? $classes) as $className)
                                <option value="{{ $className }}" {{ $filterClass == $className ? 'selected' : '' }}>{{ $className }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Section -->
                    <div class="col-md-2">
                        <label for="filter_section" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Section</label>
                        <select class="form-select form-select-sm" id="filter_section" name="filter_section" style="height: 32px;">
                            <option value="">All Sections</option>
                            @foreach($sections as $sectionName)
                                <option value="{{ $sectionName }}" {{ $filterSection == $sectionName ? 'selected' : '' }}>{{ $sectionName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Type -->
                    <div class="col-md-3">
                        <label for="filter_type" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Type</label>
                        <select class="form-select form-select-sm" id="filter_type" name="filter_type" style="height: 32px;">
                            <option value="">Select Type</option>
                            <option value="admit slip" {{ $filterType == 'admit slip' ? 'selected' : '' }}>Admit Slip</option>
                            <option value="admit card slip" {{ $filterType == 'admit card slip' ? 'selected' : '' }}>Admit Card Slip</option>
                        </select>
                    </div>
                </div>

                <!-- Filter Button -->
                <div class="row">
                    <div class="col-md-12 text-end d-flex gap-2 justify-content-end">
                        <button type="submit" class="btn btn-sm py-1 px-3 rounded-8 filter-btn" style="height: 32px;">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">filter_alt</span>
                            <span style="font-size: 12px;">Filter</span>
                        </button>
                        @if($filterCampus && $filterClass)
                            <a href="{{ route('exam.print-admit-cards', array_merge(request()->all(), ['print' => 1])) }}" target="_blank" class="btn btn-sm py-1 px-3 rounded-8 filter-btn" style="height: 32px;">
                                <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">print</span>
                                <span style="font-size: 12px;">Print</span>
                            </a>
                        @endif
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@if($filterCampus && $filterClass)
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="mb-2 p-2 rounded-8 d-flex align-items-center justify-content-between" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                    <span class="material-symbols-outlined" style="font-size: 18px;">badge</span>
                    <span>Admit Cards</span>
                </h5>
                <button type="button" class="btn btn-sm btn-light no-print" onclick="printAdmitCards()" style="height: 28px;">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                    <span style="font-size: 11px;">Print</span>
                </button>
            </div>
            
            @if($filterType == 'admit slip')
                <!-- Admit Slip Format -->
                <div id="admitCardsPrintArea">
                    @forelse($students as $index => $student)
                        <div class="mb-4 p-3 border rounded-8" style="page-break-after: always; border: 1px solid #dee2e6;">
                            <!-- Student Information Table -->
                            <table class="table table-bordered table-sm mb-3">
                                <thead>
                                    <tr>
                                        <th>Seat#</th>
                                        <th>Roll</th>
                                        <th>Student</th>
                                        <th>Parent</th>
                                        <th>Class</th>
                                        <th>Section</th>
                                        <th>Campus</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $student->student_code ?? 'N/A' }}</td>
                                        <td>{{ $student->student_name }}</td>
                                        <td>{{ $student->father_name ?? 'N/A' }}</td>
                                        <td>{{ $student->class ?? 'N/A' }}</td>
                                        <td>{{ $student->section ?? 'N/A' }}</td>
                                        <td>{{ $student->campus ?? 'N/A' }}</td>
                                    </tr>
                                </tbody>
                            </table>
                            
                            <!-- Exam Timetable Table -->
                            @if($examTimetables->isNotEmpty())
                                <table class="table table-bordered table-sm mb-3">
                                    <thead>
                                        <tr>
                                            <th>Date</th>
                                            <th>Day</th>
                                            <th>Subject</th>
                                            <th>Starts At</th>
                                            <th>Ends At</th>
                                            <th>Total Time</th>
                                            <th>Room/Block</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($examTimetables as $timetable)
                                            @php
                                                $start = \Carbon\Carbon::createFromFormat('H:i:s', $timetable->starting_time);
                                                $end = \Carbon\Carbon::createFromFormat('H:i:s', $timetable->ending_time);
                                                if ($end->lessThan($start)) {
                                                    $end = $end->addDay();
                                                }
                                                $diff = $start->diff($end);
                                                $totalTime = sprintf('%d hour & %d minutes', ($diff->h + ($diff->d * 24)), $diff->i);
                                            @endphp
                                            <tr>
                                                <td>{{ \Carbon\Carbon::parse($timetable->exam_date)->format('d-m-Y') }}</td>
                                                <td>{{ \Carbon\Carbon::parse($timetable->exam_date)->format('l') }}</td>
                                                <td>{{ $timetable->subject }}</td>
                                                <td>{{ \Carbon\Carbon::createFromFormat('H:i:s', $timetable->starting_time)->format('h:i A') }}</td>
                                                <td>{{ \Carbon\Carbon::createFromFormat('H:i:s', $timetable->ending_time)->format('h:i A') }}</td>
                                                <td>{{ $totalTime }}</td>
                                                <td>{{ $timetable->room_block ?? 'N/A' }}</td>
                                            </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            @endif
                            
                            <!-- Exam Settings Instructions -->
                            @if($examSettings && $examSettings->admit_card_instructions)
                                <div class="mt-3 p-3 bg-light rounded-8">
                                    <h6 class="fw-semibold mb-2" style="color: #003471;">Instructions:</h6>
                                    <div class="small">{!! nl2br(e($examSettings->admit_card_instructions)) !!}</div>
                                </div>
                            @endif
                        </div>
                    @empty
                        <div class="col-12 text-center text-muted py-4">No students found for the selected filters.</div>
                    @endforelse
                </div>
            @elseif($filterType == 'admit card slip' || !$filterType)
                <!-- Admit Card Slip Format -->
                <div id="admitCardsPrintArea" class="row g-3">
                    @forelse($students as $student)
                        <div class="col-md-6">
                            <div class="border rounded-8 p-3 h-100" style="border: 1px solid #dee2e6;">
                                <div class="d-flex align-items-center justify-content-between mb-2">
                                    <div class="fw-semibold" style="color: #003471;">Admit Card</div>
                                    <div class="small text-muted">{{ $filterExam ?: 'Exam' }}</div>
                                </div>
                                <div class="d-flex gap-3">
                                    <div>
                                        @if($student->photo)
                                            <img src="{{ asset('storage/' . $student->photo) }}" alt="Photo" style="width: 70px; height: 70px; border-radius: 8px; object-fit: cover;">
                                        @else
                                            <div style="width: 70px; height: 70px; border-radius: 8px; background-color: #e0e7ff; display: flex; align-items: center; justify-content: center;">
                                                <span class="material-symbols-outlined" style="font-size: 28px; color: #003471;">person</span>
                                            </div>
                                        @endif
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="fw-semibold">{{ $student->student_name }}</div>
                                        <div class="small text-muted">Student Code: {{ $student->student_code ?? 'N/A' }}</div>
                                        <div class="small text-muted">Class/Section: {{ $student->class ?? 'N/A' }}/{{ $student->section ?? 'N/A' }}</div>
                                        <div class="small text-muted">Campus: {{ $student->campus ?? 'N/A' }}</div>
                                    </div>
                                </div>
                                <div class="mt-3 small">
                                    <div><strong>Exam:</strong> {{ $filterExam ?: 'N/A' }}</div>
                                    <div><strong>Type:</strong> {{ $filterType ?: 'N/A' }}</div>
                                </div>
                            </div>
                        </div>
                    @empty
                        <div class="col-12 text-center text-muted py-4">No students found for the selected filters.</div>
                    @endforelse
                </div>
            @endif
        </div>
    </div>
</div>
@endif

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

.no-print {
    display: block;
}

@media print {
    .no-print,
    .sidebar-area,
    .header-area,
    .main-content .header-area,
    .theme-settings-area,
    .theme-settings,
    .settings-btn,
    .btn {
        display: none !important;
    }
    .container-fluid,
    .main-content,
    .main-content-container {
        margin: 0 !important;
        padding: 0 !important;
    }
    body {
        background: #fff !important;
    }
    table {
        font-size: 12px;
    }
    .table th,
    .table td {
        padding: 8px;
        border: 1px solid #000;
    }
}
</style>

@if(!$isPrint)
<script>
function printAdmitCards() {
    window.print();
}

document.addEventListener('DOMContentLoaded', function() {
    const campusSelect = document.getElementById('filter_campus');
    const examSelect = document.getElementById('filter_exam');
    const classSelect = document.getElementById('filter_class');
    const sectionSelect = document.getElementById('filter_section');
    const allClassOptions = classSelect ? classSelect.innerHTML : '';

    function loadExams() {
        const campus = campusSelect.value;
        
        examSelect.innerHTML = '<option value="">Loading...</option>';
        
        fetch(`{{ route('exam.print-admit-cards.get-exams') }}?campus=${encodeURIComponent(campus)}`)
            .then(response => response.json())
            .then(data => {
                examSelect.innerHTML = '<option value="">All Exams</option>';
                data.forEach(exam => {
                    examSelect.innerHTML += `<option value="${exam}">${exam}</option>`;
                });
            })
            .catch(error => {
                console.error('Error loading exams:', error);
                examSelect.innerHTML = '<option value="">Error loading exams</option>';
            });
    }

    function loadSections(selectedClass) {
        if (selectedClass) {
            sectionSelect.innerHTML = '<option value="">Loading...</option>';

            const params = new URLSearchParams();
            params.append('class', selectedClass);
            if (campusSelect.value) {
                params.append('campus', campusSelect.value);
            }

            fetch(`{{ route('exam.timetable.get-sections') }}?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    sectionSelect.innerHTML = '<option value="">All Sections</option>';
                    data.forEach(section => {
                        sectionSelect.innerHTML += `<option value="${section}">${section}</option>`;
                    });
                })
                .catch(error => {
                    console.error('Error loading sections:', error);
                    sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
                });
        } else {
            sectionSelect.innerHTML = '<option value="">All Sections</option>';
        }
    }

    function loadClasses() {
        const campus = campusSelect.value;

        if (!campus) {
            classSelect.innerHTML = allClassOptions;
            classSelect.value = '';
            sectionSelect.innerHTML = '<option value="">All Sections</option>';
            return;
        }

        classSelect.innerHTML = '<option value="">Loading...</option>';
        fetch(`{{ route('exam.print-admit-cards.get-classes') }}?campus=${encodeURIComponent(campus)}`)
            .then(response => response.json())
            .then(data => {
                classSelect.innerHTML = '<option value="">All Classes</option>';
                if (data.classes && data.classes.length > 0) {
                    data.classes.forEach(className => {
                        classSelect.innerHTML += `<option value="${className}">${className}</option>`;
                    });
                }
                @if($filterClass)
                if (data.classes && data.classes.includes('{{ $filterClass }}')) {
                    classSelect.value = '{{ $filterClass }}';
                    loadSections(classSelect.value);
                }
                @endif
            })
            .catch(error => {
                console.error('Error loading classes:', error);
                classSelect.innerHTML = '<option value="">Error loading classes</option>';
            });
    }

    campusSelect.addEventListener('change', function() {
        loadExams();
        loadClasses();
    });
    classSelect.addEventListener('change', function() {
        loadSections(this.value);
    });

    if (campusSelect.value) {
        loadExams();
        loadClasses();
    }
});
</script>
@endif
@endsection
@endif
