@extends('layouts.app')

@section('title', 'Manage Exam Timetable')

@section('content')
<div class="row no-print">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Manage Exam Timetable</h4>
            </div>

            <!-- Filter Form -->
            <form action="{{ route('exam.timetable.manage') }}" method="GET" id="filterForm">
                <div class="row g-2 mb-3 align-items-end">
                    <!-- Campus -->
                    <div class="col-md-3">
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
                    <div class="col-md-3">
                        <label for="filter_class" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class</label>
                        <select class="form-select form-select-sm" id="filter_class" name="filter_class" style="height: 32px;">
                            <option value="">All Classes</option>
                            @foreach(($filterClasses ?? $classes) as $className)
                                <option value="{{ $className }}" {{ $filterClass == $className ? 'selected' : '' }}>{{ $className }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Section -->
                    <div class="col-md-3">
                        <label for="filter_section" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Section</label>
                        <select class="form-select form-select-sm" id="filter_section" name="filter_section" style="height: 32px;">
                            <option value="">All Sections</option>
                            @foreach($sections as $sectionName)
                                <option value="{{ $sectionName }}" {{ $filterSection == $sectionName ? 'selected' : '' }}>{{ $sectionName }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                <!-- Filter Button -->
                <div class="row">
                    <div class="col-md-12 text-end">
                        <button type="submit" class="btn btn-sm py-1 px-3 rounded-8 filter-btn" style="height: 32px;">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">filter_alt</span>
                            <span style="font-size: 12px;">Filter</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@if($filterCampus || $filterExam || $filterClass || $filterSection)
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="mb-2 p-2 rounded-8 d-flex align-items-center justify-content-between" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                    <span class="material-symbols-outlined" style="font-size: 18px;">list</span>
                    <span>Exam Timetable</span>
                </h5>
                <button type="button" class="btn btn-sm btn-light no-print" onclick="printTimetable()" style="height: 28px;">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                    <span style="font-size: 11px;">Print</span>
                </button>
            </div>
            <div class="default-table-area" style="margin-top: 0;">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
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
                            @forelse($timetables as $item)
                                @php
                                    $start = \Carbon\Carbon::createFromFormat('H:i:s', $item->starting_time);
                                    $end = \Carbon\Carbon::createFromFormat('H:i:s', $item->ending_time);
                                    if ($end->lessThan($start)) {
                                        $end = $end->addDay();
                                    }
                                    $diff = $start->diff($end);
                                    $totalTime = sprintf('%02d:%02d', ($diff->h + ($diff->d * 24)), $diff->i);
                                @endphp
                                <tr>
                                    <td>{{ \Carbon\Carbon::parse($item->exam_date)->format('d M Y') }}</td>
                                    <td>{{ \Carbon\Carbon::parse($item->exam_date)->format('l') }}</td>
                                    <td>{{ $item->subject }}</td>
                                    <td>{{ \Carbon\Carbon::createFromFormat('H:i:s', $item->starting_time)->format('H:i') }}</td>
                                    <td>{{ \Carbon\Carbon::createFromFormat('H:i:s', $item->ending_time)->format('H:i') }}</td>
                                    <td>{{ $totalTime }}</td>
                                    <td>{{ $item->room_block ?? 'N/A' }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No timetable entries found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

<style>
.no-print {
    display: block;
}

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

@media print {
    .no-print {
        display: none !important;
    }
    .sidebar-area,
    .header-area,
    .main-content .header-area,
    .main-content-container ~ .flex-grow-1,
    .main-content-container ~ .footer-area,
    .main-content-container ~ footer,
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
    .card {
        border: none !important;
        box-shadow: none !important;
    }
}
</style>

<script>
function printTimetable() {
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
        
        fetch(`{{ route('exam.timetable.get-exams-manage') }}?campus=${encodeURIComponent(campus)}`)
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
        fetch(`{{ route('exam.timetable.get-classes') }}?campus=${encodeURIComponent(campus)}`)
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
@endsection
