@extends('layouts.app')

@section('title', 'Exam Grades - For Particular Exam')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Exam Grades - For Particular Exam</h4>
                @if($showResults)
                    <button type="button" class="btn btn-sm py-1 px-2 d-inline-flex align-items-center gap-1 rounded-8 grade-add-btn text-white" data-bs-toggle="modal" data-bs-target="#gradeModal" onclick="resetForm()">
                        <span class="material-symbols-outlined text-white" style="font-size: 16px;">add</span>
                        <span class="text-white">Add Grades</span>
                    </button>
                @endif
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Filter Form -->
            <form action="{{ route('exam.grades.particular') }}" method="GET" id="filterForm">
                <div class="row g-2 mb-3 align-items-end">
                    <!-- Campus -->
                    <div class="col-md-3">
                        <label for="filter_campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                        <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px;">
                            <option value="">All Campuses</option>
                            @foreach($campuses as $campus)
                                @php
                                    $campusName = is_object($campus) ? ($campus->campus_name ?? '') : $campus;
                                @endphp
                                <option value="{{ $campusName }}" {{ $filterCampus == $campusName ? 'selected' : '' }}>{{ $campusName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Exam (only shows exams for selected campus after Filter) -->
                    <div class="col-md-3">
                        <label for="filter_exam" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Exam</label>
                        <select class="form-select form-select-sm" id="filter_exam" name="filter_exam" style="height: 32px;">
                            <option value="">All Exams</option>
                            @foreach($exams as $examName)
                                <option value="{{ $examName }}" {{ request('filter_exam') == $examName ? 'selected' : '' }}>{{ $examName }}</option>
                            @endforeach
                        </select>
                        @if(!$filterCampus)
                            <small class="text-muted">Select campus and apply filter to see exams</small>
                        @endif
                    </div>

                    <!-- Session -->
                    <div class="col-md-3">
                        <label for="filter_session" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Session</label>
                        <select class="form-select form-select-sm" id="filter_session" name="filter_session" style="height: 32px;">
                            <option value="">All Sessions</option>
                            @foreach($sessions as $sessionName)
                                <option value="{{ $sessionName }}" {{ $filterSession == $sessionName ? 'selected' : '' }}>{{ $sessionName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Button -->
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-sm py-1 px-3 rounded-8 filter-btn w-100" style="height: 32px;">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">filter_alt</span>
                            <span style="font-size: 12px;">Filter</span>
                        </button>
                    </div>
                </div>
            </form>
            @if($showResults)
                <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 18px;">list</span>
                        <span>Grades List</span>
                    </h5>
                </div>

                <div class="default-table-area" style="margin-top: 0;">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Grade Name</th>
                                    <th>For Exam</th>
                                    <th>From %</th>
                                    <th>To %</th>
                                    <th>GPA</th>
                                    <th>Campus</th>
                                    <th class="text-end">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($grades as $index => $grade)
                                    <tr>
                                        <td>{{ $index + 1 }}</td>
                                        <td><strong class="text-primary">{{ $grade->name }}</strong></td>
                                        <td>{{ $grade->for_exam }}</td>
                                        <td>{{ number_format($grade->from_percentage, 2) }}%</td>
                                        <td>{{ number_format($grade->to_percentage, 2) }}%</td>
                                        <td>{{ number_format($grade->grade_points, 2) }}</td>
                                        <td><span class="badge bg-info text-white">{{ $grade->campus }}</span></td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <button type="button" class="btn btn-sm btn-primary px-2 py-0" title="Edit" onclick="editGrade({{ $grade->id }}, '{{ addslashes($grade->campus) }}', '{{ addslashes($grade->name) }}', '{{ $grade->from_percentage }}', '{{ $grade->to_percentage }}', '{{ $grade->grade_points }}', '{{ addslashes($grade->for_exam) }}', '{{ addslashes($grade->session) }}')">
                                                    <span class="material-symbols-outlined" style="font-size: 14px; color: white;">edit</span>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger px-2 py-0" title="Delete" onclick="if(confirm('Are you sure you want to delete this grade?')) { document.getElementById('delete-form-{{ $grade->id }}').submit(); }">
                                                    <span class="material-symbols-outlined" style="font-size: 14px; color: white;">delete</span>
                                                </button>
                                                <form id="delete-form-{{ $grade->id }}" action="{{ route('exam.grades.particular.destroy', $grade->id) }}" method="POST" class="d-none">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="8" class="text-center text-muted py-5">
                                            <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                            <p class="mt-2 mb-0">No grades found.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <span class="material-symbols-outlined text-muted" style="font-size: 64px;">filter_alt</span>
                    <p class="text-muted mt-3 mb-0">Please apply filters to view grades.</p>
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Grade Modal -->
<div class="modal fade" id="gradeModal" tabindex="-1" aria-labelledby="gradeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white p-3" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                <h5 class="modal-title fs-15 fw-semibold mb-0 d-flex align-items-center gap-2" id="gradeModalLabel">
                    <span class="material-symbols-outlined" style="font-size: 20px;">grade</span>
                    <span>Add New Grade</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 0.8;"></button>
            </div>
            <form id="gradeForm" method="POST" action="{{ route('exam.grades.particular.store') }}">
                @csrf
                <div id="methodField"></div>
                <div class="modal-body p-3">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm grade-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">location_on</span>
                                </span>
                                <select class="form-control grade-input" name="campus" id="campus" required style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;">
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        @php
                                            $campusName = is_object($campus) ? ($campus->campus_name ?? '') : $campus;
                                        @endphp
                                        <option value="{{ $campusName }}">{{ $campusName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Name <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm grade-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">grade</span>
                                </span>
                                <input type="text" class="form-control grade-input" name="name" id="name" placeholder="Enter grade name" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">From % <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm grade-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">percent</span>
                                </span>
                                <input type="number" step="0.01" min="0" max="100" class="form-control grade-input" name="from_percentage" id="from_percentage" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">To % <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm grade-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">percent</span>
                                </span>
                                <input type="number" step="0.01" min="0" max="100" class="form-control grade-input" name="to_percentage" id="to_percentage" placeholder="100.00" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Grade Points (GPA) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm grade-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">calculate</span>
                                </span>
                                <input type="number" step="0.01" min="0" class="form-control grade-input" name="grade_points" id="grade_points" placeholder="0.00" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">For Exam/Test <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm grade-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">assignment</span>
                                </span>
                                <select class="form-control grade-input" name="for_exam" id="for_exam" required style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;">
                                    <option value="">Select Exam/Test</option>
                                </select>
                                <small class="text-muted d-block mt-1">Select campus first to load exams</small>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Session <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm grade-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">calendar_month</span>
                                </span>
                                <select class="form-control grade-input" name="session" id="session" required style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;">
                                    <option value="">Select Session</option>
                                    @foreach($sessions as $sessionName)
                                        <option value="{{ $sessionName }}">{{ $sessionName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-3" style="background-color: #f8f9fa; border-top: 1px solid #e9ecef;">
                    <button type="button" class="btn btn-sm py-2 px-4 rounded-8" data-bs-dismiss="modal" style="background-color: #6c757d; color: white; border: none; transition: all 0.3s ease;">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">close</span>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-sm py-2 px-4 rounded-8 grade-submit-btn">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">add</span>
                        Add Grade
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

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

.default-table-area {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #dee2e6;
}

.default-table-area thead {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.default-table-area thead th {
    font-weight: 600;
    font-size: 13px;
    color: #003471;
    border-bottom: 2px solid #dee2e6;
    padding: 12px;
}

.grade-add-btn {
    background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
    color: white;
    border: none;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 2px 6px rgba(0, 52, 113, 0.2);
}

.grade-add-btn:hover {
    background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(0, 52, 113, 0.3);
    color: white;
}

#gradeModal .grade-input-group {
    border-radius: 8px;
    overflow: hidden;
    transition: all 0.3s ease;
    border: 1px solid #dee2e6;
    height: 32px;
}

#gradeModal .grade-input-group:focus-within {
    box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
    border-color: #003471;
}

#gradeModal .grade-input {
    font-size: 13px;
    padding: 0.35rem 0.65rem;
    border: none;
    border-left: 1px solid #e0e7ff;
    border-radius: 0 8px 8px 0;
    transition: all 0.3s ease;
    height: 32px;
}

#gradeModal .grade-input:focus {
    border-left-color: #003471;
    box-shadow: none;
    outline: none;
}

#gradeModal .input-group-text {
    padding: 0 0.65rem;
    display: flex;
    align-items: center;
    border: none;
    border-right: 1px solid #e0e7ff;
    border-radius: 8px 0 0 8px;
    transition: all 0.3s ease;
    height: 32px;
}

#gradeModal .grade-input-group:focus-within .input-group-text {
    background-color: #003471 !important;
    color: white !important;
    border-right-color: #003471;
}

#gradeModal .grade-submit-btn {
    background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
    color: white;
    border: none;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 2px 8px rgba(0, 52, 113, 0.25);
}

#gradeModal .grade-submit-btn:hover {
    background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 12px rgba(0, 52, 113, 0.35);
}
</style>

<script>
// Load exams for selected campus (filter form or modal). Only that campus's exams.
function loadExamsForFilter(campus, session, targetSelectId, emptyOptionText) {
    const examSelect = document.getElementById(targetSelectId);
    if (!examSelect) return;
    examSelect.innerHTML = '<option value="">' + (emptyOptionText || 'Loading...') + '</option>';
    if (!campus || campus.trim() === '') {
        examSelect.innerHTML = '<option value="">' + (emptyOptionText || 'All Exams') + '</option>';
        return;
    }
    const params = new URLSearchParams();
    params.append('campus', campus.trim());
    if (session && session.trim() !== '') params.append('session', session.trim());
    fetch(`{{ route('exam.grades.get-exams') }}?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            examSelect.innerHTML = '<option value="">' + (emptyOptionText || 'All Exams') + '</option>';
            (Array.isArray(data) ? data : []).forEach(exam => {
                const opt = document.createElement('option');
                opt.value = exam;
                opt.textContent = exam;
                examSelect.appendChild(opt);
            });
        })
        .catch(() => {
            examSelect.innerHTML = '<option value="">' + (emptyOptionText || 'All Exams') + '</option>';
        });
}

// Load exams in Add/Edit Grade modal by campus (particular endpoint returns { exams: [] })
function loadExamsForModal(campus, session, callback) {
    const forExamSelect = document.getElementById('for_exam');
    if (!forExamSelect) return;
    forExamSelect.innerHTML = '<option value="">Loading...</option>';
    if (!campus || campus.trim() === '') {
        forExamSelect.innerHTML = '<option value="">Select Exam/Test</option>';
        if (callback) callback();
        return;
    }
    const params = new URLSearchParams();
    params.append('campus', campus.trim());
    if (session && session.trim() !== '') params.append('session', session.trim());
    fetch(`{{ route('exam.grades.particular.get-exams-by-campus') }}?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            forExamSelect.innerHTML = '<option value="">Select Exam/Test</option>';
            (data.exams || []).forEach(exam => {
                const opt = document.createElement('option');
                opt.value = exam;
                opt.textContent = exam;
                forExamSelect.appendChild(opt);
            });
            if (callback) callback();
        })
        .catch(() => {
            forExamSelect.innerHTML = '<option value="">Select Exam/Test</option>';
            if (callback) callback();
        });
}

document.addEventListener('DOMContentLoaded', function() {
    const filterCampusSelect = document.getElementById('filter_campus');
    const filterSessionSelect = document.getElementById('filter_session');
    const filterExamSelect = document.getElementById('filter_exam');
    if (filterCampusSelect && filterExamSelect) {
        function onFilterCampusOrSessionChange() {
            loadExamsForFilter(filterCampusSelect.value, filterSessionSelect ? filterSessionSelect.value : '', 'filter_exam', 'All Exams');
        }
        filterCampusSelect.addEventListener('change', onFilterCampusOrSessionChange);
        if (filterSessionSelect) filterSessionSelect.addEventListener('change', onFilterCampusOrSessionChange);
    }

    const modalCampusSelect = document.getElementById('campus');
    const modalSessionSelect = document.getElementById('session');
    if (modalCampusSelect && document.getElementById('for_exam')) {
        modalCampusSelect.addEventListener('change', function() {
            loadExamsForModal(this.value, modalSessionSelect ? modalSessionSelect.value : '');
        });
        if (modalSessionSelect) {
            modalSessionSelect.addEventListener('change', function() {
                if (modalCampusSelect.value) loadExamsForModal(modalCampusSelect.value, this.value);
            });
        }
    }
});

function resetForm() {
    document.getElementById('gradeForm').reset();
    document.getElementById('gradeForm').action = "{{ route('exam.grades.particular.store') }}";
    document.getElementById('methodField').innerHTML = '';
    document.getElementById('gradeModalLabel').innerHTML = '<span class="material-symbols-outlined" style="font-size: 20px;">grade</span><span>Add New Grade</span>';
    document.getElementById('for_exam').innerHTML = '<option value="">Select Exam/Test</option>';

    const filterCampus = "{{ $filterCampus ?? '' }}";
    const filterExam = "{{ $filterExam ?? '' }}";
    const filterSession = "{{ $filterSession ?? '' }}";

    if (filterCampus) {
        document.getElementById('campus').value = filterCampus;
        loadExamsForModal(filterCampus, filterSession, function() {
            if (filterExam) document.getElementById('for_exam').value = filterExam;
        });
    }
    if (filterSession) document.getElementById('session').value = filterSession;
}

function editGrade(id, campus, name, fromPercentage, toPercentage, gradePoints, forExam, session) {
    document.getElementById('campus').value = campus;
    document.getElementById('name').value = name;
    document.getElementById('from_percentage').value = fromPercentage;
    document.getElementById('to_percentage').value = toPercentage;
    document.getElementById('grade_points').value = gradePoints;
    document.getElementById('session').value = session;
    document.getElementById('gradeForm').action = "{{ url('/exam/grades/particular') }}/" + id;
    document.getElementById('methodField').innerHTML = '@method("PUT")';
    document.getElementById('gradeModalLabel').innerHTML = '<span class="material-symbols-outlined" style="font-size: 20px;">edit</span><span>Edit Grade</span>';

    loadExamsForModal(campus, session, function() {
        document.getElementById('for_exam').value = forExam || '';
    });

    const modal = new bootstrap.Modal(document.getElementById('gradeModal'));
    modal.show();
}
</script>
@endsection
