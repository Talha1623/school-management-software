@extends('layouts.app')

@section('title', 'Teacher Remarks - For Final Result')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Teacher Remarks - For Final Result</h4>
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

            <form action="{{ route('exam.teacher-remarks.final') }}" method="GET" id="filterForm">
                <div class="row g-2 mb-3 align-items-end">
                    <div class="col-md-2">
                        <label for="filter_campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                        <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px;">
                            <option value="">All Campuses</option>
                            @foreach($campuses as $campus)
                                @php $campusName = is_object($campus) ? ($campus->campus_name ?? '') : $campus; @endphp
                                <option value="{{ $campusName }}" {{ $filterCampus == $campusName ? 'selected' : '' }}>{{ $campusName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="filter_session" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Session</label>
                        <select class="form-select form-select-sm" id="filter_session" name="filter_session" style="height: 32px;">
                            <option value="">All Sessions</option>
                            @foreach($sessions as $session)
                                <option value="{{ $session }}" {{ $filterSession == $session ? 'selected' : '' }}>{{ $session }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="filter_class" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class</label>
                        <select class="form-select form-select-sm" id="filter_class" name="filter_class" data-selected-class="{{ $filterClass }}" style="height: 32px;">
                            <option value="">All Classes</option>
                            @foreach($classes as $className)
                                <option value="{{ $className }}" {{ $filterClass == $className ? 'selected' : '' }}>{{ $className }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <label for="filter_section" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Section</label>
                        <select class="form-select form-select-sm" id="filter_section" name="filter_section" data-selected-section="{{ $filterSection }}" style="height: 32px;">
                            <option value="">All Sections</option>
                            @foreach($sections as $sectionName)
                                <option value="{{ $sectionName }}" {{ $filterSection == $sectionName ? 'selected' : '' }}>{{ $sectionName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm py-1 px-3 rounded-8 filter-btn w-100" style="height: 32px;">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">filter_alt</span>
                            <span style="font-size: 12px;">Filter</span>
                        </button>
                    </div>
                </div>
            </form>

            @if(request()->hasAny(['filter_campus', 'filter_session', 'filter_class', 'filter_section']))
            <div class="mt-3">
                <div class="mb-3 p-3 rounded-8" style="background-color: #ff9800; border: 1px solid #f57c00;">
                    <div class="d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined text-white" style="font-size: 20px;">info</span>
                        <span class="text-white fs-13 fw-medium">
                            Navigation Guide: Use arrow keys to move between remark fields.
                        </span>
                    </div>
                </div>

                @php
                    $canEditFinalRemarks = $canEditFinalRemarks ?? true;
                    $finalRemarksReadonly = isset($canEditFinalRemarks) && !$canEditFinalRemarks;
                @endphp
                @if(!empty($isStaffFinalUser) && $finalRemarksReadonly)
                    <div class="alert alert-warning py-2 mb-3" style="font-size: 13px;">
                        Final result remarks are <strong>view only</strong> for you. Only the class teacher (Manage Section) can add or edit remarks.
                    </div>
                @elseif(!empty($isStaffFinalUser) && !empty($canEditFinalRemarks))
                    <div class="alert alert-info py-2 mb-3" style="font-size: 13px;">
                        You are the class teacher for this class/section — you can add or edit final result remarks.
                    </div>
                @endif

                <div class="mb-3">
                    <div class="input-group input-group-sm search-input-group" style="max-width: 400px;">
                        <span class="input-group-text bg-light border-end-0" style="background-color: #f0f4ff !important; border-color: #e0e7ff; padding: 4px 8px; height: 32px;">
                            <span class="material-symbols-outlined" style="font-size: 14px; color: #003471;">search</span>
                        </span>
                        <input type="text" id="searchInput" class="form-control border-start-0 border-end-0" placeholder="Search by Name / Student Code ..." style="padding: 4px 8px; font-size: 13px; height: 32px;">
                        <button class="btn btn-outline-secondary border-start-0" type="button" id="clearSearchBtn" style="padding: 4px 8px; height: 32px; display: none;">
                            <span class="material-symbols-outlined" style="font-size: 14px;">close</span>
                        </button>
                    </div>
                </div>

                <div class="default-table-area" style="margin-top: 0;">
                    <div class="table-responsive">
                        <form id="remarksForm" method="POST" action="{{ route('exam.teacher-remarks.final.save') }}">
                            @csrf
                            <input type="hidden" name="campus" value="{{ request('filter_campus') }}">
                            <input type="hidden" name="session" value="{{ request('filter_session') }}">
                            <input type="hidden" name="class" value="{{ request('filter_class') }}">
                            <input type="hidden" name="section" value="{{ request('filter_section') }}">

                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Roll</th>
                                        <th>Name</th>
                                        <th>Parent</th>
                                        <th>Total</th>
                                        <th>Obtained</th>
                                        <th>Teacher Remarks For Final Result</th>
                                    </tr>
                                </thead>
                                <tbody id="studentsTableBody">
                                    @forelse($students as $index => $student)
                                    <tr class="student-row" data-student-name="{{ strtolower($student->student_name) }}" data-student-code="{{ strtolower($student->student_code ?? '') }}">
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $student->student_code ?? $student->gr_number ?? 'N/A' }}</td>
                                        <td><strong class="text-primary">{{ $student->student_name }}</strong></td>
                                        <td>{{ $student->father_name ?? 'N/A' }}</td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" value="{{ number_format($student->finalTotal ?? 0, 2) }}" readonly style="width: 80px; text-align: center; background-color: #f8f9fa;">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" value="{{ number_format($student->finalObtained ?? 0, 2) }}" readonly style="width: 80px; text-align: center; background-color: #f8f9fa;">
                                        </td>
                                        <td>
                                            <textarea name="remarks[{{ $student->id }}]" class="form-control form-control-sm remarks-input" placeholder="{{ $finalRemarksReadonly ? 'View only' : 'Type Teacher Remarks for ' . $student->student_name }}" rows="2" style="min-width: 300px;" @if($finalRemarksReadonly) readonly disabled @endif>{{ optional($student->finalRemark)->teacher_remarks ?? '' }}</textarea>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <div class="d-flex flex-column align-items-center">
                                                <span class="material-symbols-outlined text-muted" style="font-size: 48px;">inbox</span>
                                                <p class="text-muted mt-2 mb-0">No students found. Please apply filters.</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>

                            @if($students->count() > 0 && !$finalRemarksReadonly)
                            <div class="text-center mt-3">
                                <button type="submit" class="btn btn-success px-4 py-2" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border: none; font-weight: 500;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle; color: white;">thumb_up</span>
                                    <span style="color: white; font-size: 14px;">Save Changes</span>
                                </button>
                            </div>
                            @endif
                        </form>
                    </div>
                </div>
            </div>
            @else
            <div class="text-center py-5">
                <span class="material-symbols-outlined text-muted" style="font-size: 64px;">filter_alt</span>
                <p class="text-muted mt-3 mb-0">Please apply filters to view students and enter teacher remarks</p>
            </div>
            @endif
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
.default-table-area table { margin-bottom: 0; border-collapse: collapse; }
.default-table-area thead th {
    padding: 8px 12px;
    font-size: 13px;
    font-weight: 600;
    border: 1px solid #dee2e6;
    background-color: #f8f9fa;
    color: #003471;
}
.default-table-area tbody td {
    padding: 8px 12px;
    font-size: 13px;
    vertical-align: middle;
    border: 1px solid #dee2e6;
}
.default-table-area tbody tr:hover { background-color: #f8f9fa; }
.search-input-group {
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #dee2e6;
    height: 32px;
}
.search-input-group:focus-within {
    border-color: #003471;
    box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
}
.remarks-input { resize: vertical; min-height: 60px; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const campusSelect = document.getElementById('filter_campus');
    const classSelect = document.getElementById('filter_class');
    const sectionSelect = document.getElementById('filter_section');

    function resetSections() {
        if (!sectionSelect) return;
        sectionSelect.innerHTML = '<option value="">All Sections</option>';
        sectionSelect.disabled = true;
    }

    function populateClasses(classes, selectedClass = '', selectedSection = '') {
        if (!classSelect) return;
        classSelect.innerHTML = '<option value="">All Classes</option>';
        (classes || []).forEach(className => {
            const option = document.createElement('option');
            option.value = className;
            option.textContent = className;
            if (selectedClass && selectedClass === className) {
                option.selected = true;
            }
            classSelect.appendChild(option);
        });
        classSelect.disabled = false;
        if (selectedClass) {
            loadSections(selectedClass, selectedSection);
        }
    }

    function populateSections(sections, selectedSection = '') {
        if (!sectionSelect) return;
        sectionSelect.innerHTML = '<option value="">All Sections</option>';
        (sections || []).forEach(sectionName => {
            const option = document.createElement('option');
            option.value = sectionName;
            option.textContent = sectionName;
            if (selectedSection && selectedSection === sectionName) {
                option.selected = true;
            }
            sectionSelect.appendChild(option);
        });
        sectionSelect.disabled = false;
    }

    function loadClassesByCampus(campus, selectedClass = '', selectedSection = '') {
        if (!classSelect) return;
        classSelect.disabled = true;
        classSelect.innerHTML = '<option value="">Loading...</option>';
        resetSections();

        const params = new URLSearchParams();
        if (campus) params.append('campus', campus);

        fetch(`{{ route('exam.teacher-remarks.final.get-classes') }}?${params.toString()}`)
            .then(response => response.json())
            .then(data => populateClasses(data.classes || [], selectedClass, selectedSection))
            .catch(() => {
                classSelect.innerHTML = '<option value="">All Classes</option>';
                classSelect.disabled = false;
            });
    }

    function loadSections(selectedClass, selectedSection = '') {
        const campus = campusSelect ? campusSelect.value : '';
        if (!selectedClass || !sectionSelect) {
            resetSections();
            return;
        }

        sectionSelect.innerHTML = '<option value="">Loading...</option>';
        sectionSelect.disabled = true;

        const params = new URLSearchParams();
        params.append('class', selectedClass);
        if (campus) params.append('campus', campus);

        fetch(`{{ route('exam.teacher-remarks.final.get-sections') }}?${params.toString()}`)
            .then(response => response.json())
            .then(data => populateSections(data.sections || [], selectedSection))
            .catch(() => {
                sectionSelect.innerHTML = '<option value="">All Sections</option>';
                sectionSelect.disabled = false;
            });
    }

    if (campusSelect) {
        campusSelect.addEventListener('change', function() {
            loadClassesByCampus(this.value);
        });
    }
    if (classSelect) {
        classSelect.addEventListener('change', function() {
            loadSections(this.value);
        });
    }

    if (classSelect && sectionSelect) {
        const selectedClass = classSelect.dataset.selectedClass || '';
        const selectedSection = sectionSelect.dataset.selectedSection || '';
        loadClassesByCampus(campusSelect ? campusSelect.value : '', selectedClass, selectedSection);
    }

    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const studentsTableBody = document.getElementById('studentsTableBody');

    if (searchInput && studentsTableBody) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            studentsTableBody.querySelectorAll('.student-row').forEach(row => {
                const name = row.dataset.studentName || '';
                const code = row.dataset.studentCode || '';
                row.style.display = (name.includes(searchTerm) || code.includes(searchTerm)) ? '' : 'none';
            });
            if (clearSearchBtn) {
                clearSearchBtn.style.display = searchTerm.length > 0 ? 'inline-block' : 'none';
            }
        });
        if (clearSearchBtn) {
            clearSearchBtn.addEventListener('click', function() {
                searchInput.value = '';
                searchInput.dispatchEvent(new Event('input'));
            });
        }
    }

    document.querySelectorAll('.remarks-input').forEach((input, index, inputs) => {
        input.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowDown' && index < inputs.length - 1) {
                e.preventDefault();
                inputs[index + 1].focus();
            } else if (e.key === 'ArrowUp' && index > 0) {
                e.preventDefault();
                inputs[index - 1].focus();
            }
        });
    });
});
</script>
@endsection
