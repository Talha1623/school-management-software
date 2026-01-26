@extends('layouts.app')

@section('title', 'Print Marksheet - For Particular Exam')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4 {{ $isPrint ? 'd-none' : '' }}">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Print Marksheet - For Particular Exam</h4>
            </div>

            <form action="{{ route('exam.print-marksheet.particular') }}" method="GET" id="filterForm" target="_blank">
                <input type="hidden" name="print" value="1">
                <div class="row g-2 mb-3 align-items-end">
                    <div class="col-md-3">
                        <label for="filter_campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                        <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px;">
                            <option value="">All Campuses</option>
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
                        <select class="form-select form-select-sm" id="filter_class" name="filter_class" style="height: 32px;">
                            <option value="">All Classes</option>
                            @foreach(($filterClasses ?? $classes) as $className)
                                <option value="{{ $className }}" {{ $filterClass == $className ? 'selected' : '' }}>{{ $className }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="filter_section" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Section</label>
                        <select class="form-select form-select-sm" id="filter_section" name="filter_section" style="height: 32px;">
                            <option value="">All Sections</option>
                            @foreach($sections as $sectionName)
                                <option value="{{ $sectionName }}" {{ $filterSection == $sectionName ? 'selected' : '' }}>{{ $sectionName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="filter_exam" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Exam</label>
                        <select class="form-select form-select-sm" id="filter_exam" name="filter_exam" style="height: 32px;">
                            <option value="">All Exams</option>
                            @foreach($exams as $examName)
                                <option value="{{ $examName }}" {{ $filterExam == $examName ? 'selected' : '' }}>{{ $examName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="filter_session" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Academic Session</label>
                        <select class="form-select form-select-sm" id="filter_session" name="filter_session" style="height: 32px;">
                            <option value="">All Sessions</option>
                            @foreach($sessions as $session)
                                <option value="{{ $session }}" {{ $filterSession == $session ? 'selected' : '' }}>{{ $session }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2 text-end">
                        <button type="submit" class="btn btn-sm py-1 px-3 rounded-8 filter-btn w-100" style="height: 32px;">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">filter_alt</span>
                            <span style="font-size: 12px;">Filter</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

@if($isPrint && $filterCampus && $filterClass && $filterExam)
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="mb-2 p-2 rounded-8 d-flex align-items-center justify-content-between" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                    <span class="material-symbols-outlined" style="font-size: 18px;">print</span>
                    <span>Marksheets</span>
                </h5>
                <button type="button" class="btn btn-sm btn-light no-print" onclick="printMarksheets()" style="height: 28px;">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                    <span style="font-size: 11px;">Print</span>
                </button>
            </div>

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
                        $presentCount = $summary['present_count'] ?? 0;
                        $subjectCount = $summary['subject_count'] ?? 0;
                    @endphp
                    <div class="marksheet-card page-break">
                        <div class="text-center mb-2">
                            <div class="fw-semibold fs-16" style="color: #003471;">{{ config('app.name') }}</div>
                            <div class="text-muted fs-12">{{ $filterCampus }}</div>
                            <div class="badge rounded-pill text-dark mt-2" style="background-color: #d9f0f0;">Result Card: {{ $student->student_name }} {{ $student->session ?? '' }}</div>
                        </div>

                        <div class="row g-2 align-items-center mb-2">
                            <div class="col-md-8">
                                <div class="d-flex flex-wrap gap-3 small">
                                    <div><strong>Student / Roll:</strong> {{ $student->student_name }} ({{ $student->student_code ?? 'N/A' }})</div>
                                    <div><strong>Parent / CNIC:</strong> {{ $student->father_name ?? 'N/A' }} {{ $student->father_cnic ?? '' }}</div>
                                    <div><strong>Class / Section:</strong> {{ $student->class ?? 'N/A' }} / {{ $student->section ?? 'N/A' }}</div>
                                    <div><strong>Campus / Session:</strong> {{ $student->campus ?? 'N/A' }} {{ $filterSession ?: ($student->session ?? '') }}</div>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                @if($student->photo)
                                    <img src="{{ asset('storage/' . $student->photo) }}" alt="Photo" class="student-photo">
                                @else
                                    <div class="student-photo placeholder">
                                        <span class="material-symbols-outlined" style="font-size: 28px; color: #003471;">person</span>
                                    </div>
                                @endif
                            </div>
                        </div>

                        <div class="table-responsive">
                            <table class="table table-sm table-bordered marksheet-table">
                                <thead>
                                    <tr>
                                        <th>Subject Name</th>
                                        <th>Total</th>
                                        <th>Min</th>
                                        <th>Obtained</th>
                                        <th>%</th>
                                        <th>Result</th>
                                        <th>Highest In Class</th>
                                        <th>Remarks</th>
                                        <th>Att.</th>
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
                                            $highest = $highestBySubject->get($mark->subject) ?? 0;
                                            $att = $mark->marks_obtained !== null ? 'Present' : 'Absent';
                                        @endphp
                                        <tr>
                                            <td>{{ $mark->subject }}</td>
                                            <td>{{ $subjectTotal ?: '' }}</td>
                                            <td>{{ $subjectPassing ?: '' }}</td>
                                            <td>{{ $subjectObtained ?: '' }}</td>
                                            <td>{{ $subjectPercent }}%</td>
                                            <td>{{ $subjectResult }}</td>
                                            <td>{{ $highest }}</td>
                                            <td></td>
                                            <td>{{ $att }}</td>
                                        </tr>
                                    @endforeach
                                    <tr class="summary-row">
                                        <td><strong>Total</strong></td>
                                        <td><strong>{{ $totalMarks }}</strong></td>
                                        <td><strong>{{ $totalPassing }}</strong></td>
                                        <td><strong>{{ $totalObtained }}</strong></td>
                                        <td><strong>{{ $percentage }}%</strong></td>
                                        <td><strong>{{ $status }}</strong></td>
                                        <td colspan="2"><strong>Rank: {{ $rank }}</strong></td>
                                        <td><strong>{{ $presentCount }} / {{ $subjectCount }}</strong></td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>

                        @php
                            $teacherRemark = $teacherRemarksByStudent->get($student->id, '');
                        @endphp
                        <div class="row mt-3">
                            <div class="col-md-6">
                                <div class="section-title">Progress Overview</div>
                                <div class="progress-placeholder">
                                    <div><strong>Total Marks:</strong> {{ number_format($totalMarks, 0) }}</div>
                                    <div><strong>Obtained:</strong> {{ number_format($totalObtained, 0) }}</div>
                                    <div><strong>Percentage:</strong> {{ number_format($percentage, 2) }}%</div>
                                    <div><strong>Rank:</strong> {{ $rank }}</div>
                                    <div><strong>Status:</strong> {{ $status }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="section-title">Teacher Remarks</div>
                                <div class="remarks-box">{{ $teacherRemark !== '' ? $teacherRemark : 'N/A' }}</div>
                            </div>
                        </div>

                        <div class="d-flex justify-content-between mt-3 small">
                            <div>School Stamp / Signature</div>
                            <div>Print Date: {{ now()->format('d-m-Y h:i:s A') }}</div>
                        </div>
                    </div>
                @empty
                    <div class="text-center text-muted py-4">No marksheet data found for selected filters.</div>
                @endforelse
            </div>
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

.marksheet-card {
    border: 2px solid #bfe9e6;
    border-radius: 14px;
    padding: 16px;
    margin-bottom: 16px;
    background: linear-gradient(180deg, #f7fffe 0%, #ffffff 60%);
    box-shadow: 0 6px 18px rgba(0, 52, 113, 0.08);
}

.marksheet-table th,
.marksheet-table td {
    font-size: 12px;
    vertical-align: middle;
    text-align: center;
}

.summary-row td {
    background: #dff6f4;
    font-weight: 600;
}

.student-photo {
    width: 70px;
    height: 70px;
    border-radius: 10px;
    object-fit: cover;
    border: 1px solid #e0e0e0;
}

.student-photo.placeholder {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    background: #e0e7ff;
}

.section-title {
    background: linear-gradient(135deg, #43c6ac 0%, #1e9f9b 100%);
    color: #fff;
    padding: 6px 10px;
    border-radius: 8px 8px 0 0;
    font-weight: 600;
    font-size: 12px;
}

.progress-placeholder,
.remarks-box {
    border: 1px solid #cfeeed;
    border-top: none;
    height: 120px;
    border-radius: 0 0 8px 8px;
    background: #f3fffe;
}

.page-break {
    page-break-after: always;
}

.no-print {
    display: block;
}

@media print {
    * {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
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
    .marksheet-card {
        page-break-after: always;
    }
}

@if($isPrint)
.sidebar-area,
.header-area,
.main-content .header-area,
.theme-settings-area,
.theme-settings,
.settings-btn {
    display: none !important;
}
.container-fluid,
.main-content,
.main-content-container {
    margin: 0 !important;
    padding: 0 !important;
}
@endif
</style>

<script>
function printMarksheets() {
    window.print();
}

document.addEventListener('DOMContentLoaded', function() {
    const campusSelect = document.getElementById('filter_campus');
    const classSelect = document.getElementById('filter_class');
    const sectionSelect = document.getElementById('filter_section');
    const examSelect = document.getElementById('filter_exam');
    const sessionSelect = document.getElementById('filter_session');
    const allClassOptions = classSelect ? classSelect.innerHTML : '';

    function loadClasses() {
        const campus = campusSelect.value;
        if (!campus) {
            classSelect.innerHTML = allClassOptions;
            classSelect.value = '';
            sectionSelect.innerHTML = '<option value="">All Sections</option>';
            return;
        }

        classSelect.innerHTML = '<option value="">Loading...</option>';
        fetch(`{{ route('exam.print-marksheet.particular.get-classes') }}?campus=${encodeURIComponent(campus)}`)
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

    function loadSections(selectedClass) {
        if (!selectedClass) {
            sectionSelect.innerHTML = '<option value="">All Sections</option>';
            return;
        }

        sectionSelect.innerHTML = '<option value="">Loading...</option>';
        const params = new URLSearchParams();
        params.append('class', selectedClass);
        if (campusSelect.value) {
            params.append('campus', campusSelect.value);
        }
        fetch(`{{ route('exam.print-marksheet.particular.get-sections') }}?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                sectionSelect.innerHTML = '<option value="">All Sections</option>';
                if (data.sections && data.sections.length > 0) {
                    data.sections.forEach(section => {
                        sectionSelect.innerHTML += `<option value="${section}">${section}</option>`;
                    });
                }
            })
            .catch(error => {
                console.error('Error loading sections:', error);
                sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
            });
    }

    function loadExams() {
        const campus = campusSelect.value;
        examSelect.innerHTML = '<option value="">Loading...</option>';
        fetch(`{{ route('exam.print-marksheet.particular.get-exams') }}?campus=${encodeURIComponent(campus)}`)
            .then(response => response.json())
            .then(data => {
                examSelect.innerHTML = '<option value="">All Exams</option>';
                if (data.exams && data.exams.length > 0) {
                    data.exams.forEach(exam => {
                        examSelect.innerHTML += `<option value="${exam}">${exam}</option>`;
                    });
                }
            })
            .catch(error => {
                console.error('Error loading exams:', error);
                examSelect.innerHTML = '<option value="">Error loading exams</option>';
            });
    }

    function loadSessions() {
        if (!sessionSelect) return;
        const params = new URLSearchParams();
        if (campusSelect.value) {
            params.append('campus', campusSelect.value);
        }
        if (classSelect.value) {
            params.append('class', classSelect.value);
        }
        sessionSelect.innerHTML = '<option value="">Loading...</option>';
        fetch(`{{ route('exam.print-marksheet.particular.get-sessions') }}?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                sessionSelect.innerHTML = '<option value="">All Sessions</option>';
                if (data.sessions && data.sessions.length > 0) {
                    data.sessions.forEach(session => {
                        const option = document.createElement('option');
                        option.value = session;
                        option.textContent = session;
                        if (session === '{{ $filterSession }}') {
                            option.selected = true;
                        }
                        sessionSelect.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error loading sessions:', error);
                sessionSelect.innerHTML = '<option value="">Error loading sessions</option>';
            });
    }

    campusSelect.addEventListener('change', function() {
        loadClasses();
        loadExams();
        loadSessions();
    });

    classSelect.addEventListener('change', function() {
        loadSections(this.value);
        loadSessions();
    });

    if (campusSelect.value) {
        loadClasses();
        loadExams();
        loadSessions();
    }

    if ({{ $isPrint ? 'true' : 'false' }}) {
        setTimeout(() => window.print(), 300);
    }
});
</script>
@endsection

