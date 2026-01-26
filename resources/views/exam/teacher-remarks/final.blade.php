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

            <!-- Filter Form -->
            <form action="{{ route('exam.teacher-remarks.final') }}" method="GET" id="filterForm">
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

                    <!-- Class/Section -->
                    <div class="col-md-3">
                        <label for="filter_class" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class</label>
                        <select class="form-select form-select-sm" id="filter_class" name="filter_class" style="height: 32px;">
                            <option value="">All Classes</option>
                            @foreach($classes as $className)
                                <option value="{{ $className }}" {{ $filterClass == $className ? 'selected' : '' }}>{{ $className }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Section -->
                    <div class="col-md-3">
                        <label for="filter_section" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Section</label>
                        <select class="form-select form-select-sm" id="filter_section" name="filter_section" style="height: 32px;" {{ !$filterClass ? 'disabled' : '' }}>
                            <option value="">All Sections</option>
                            @if($filterClass)
                                @foreach($sections as $sectionName)
                                    <option value="{{ $sectionName }}" {{ $filterSection == $sectionName ? 'selected' : '' }}>{{ $sectionName }}</option>
                                @endforeach
                            @endif
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

            <!-- Results Table - Only show when all required filters are applied -->
            @if($filterCampus && $filterSession && $filterClass)
            <div class="mt-3">
                <!-- Navigation Guide -->
                <div class="mb-3 p-3 rounded-8" style="background-color: #ff9800; border: 1px solid #f57c00;">
                    <div class="d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined text-white" style="font-size: 20px;">info</span>
                        <span class="text-white fs-13 fw-medium">
                            Navigation Guide: Use ← (Left Arrow), → (Right Arrow), ↑ (Up Arrow), and ↓ (Down Arrow) to navigate between input fields.
                        </span>
                    </div>
                </div>

                <!-- Search Bar -->
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
                                        <th>Teacher Remarks For Final Exam</th>
                                    </tr>
                                </thead>
                                <tbody id="studentsTableBody">
                                    @forelse($students as $index => $student)
                                    <tr class="student-row" data-student-id="{{ $student->id }}" data-student-name="{{ strtolower($student->student_name) }}" data-student-code="{{ strtolower($student->student_code ?? '') }}">
                                        <td>{{ $index + 1 }}</td>
                                        <td>{{ $student->student_code ?? $student->gr_number ?? 'N/A' }}</td>
                                        <td>
                                            <strong class="text-primary">{{ $student->student_name }}</strong>
                                        </td>
                                        <td>{{ $student->father_name ?? 'N/A' }}</td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm marks-input" value="{{ isset($student->finalTotal) && $student->finalTotal !== null ? number_format($student->finalTotal, 0) : '0' }}" readonly>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm marks-input" value="{{ isset($student->finalObtained) && $student->finalObtained !== null ? number_format($student->finalObtained, 0) : '0' }}" readonly>
                                        </td>
                                        <td>
                                            <textarea name="remarks[{{ $student->id }}]" class="form-control form-control-sm remarks-input" placeholder="Type Teacher Remarks for {{ $student->student_name }}" rows="2" style="min-width: 300px;">{{ isset($student->finalRemark) && $student->finalRemark ? ($student->finalRemark->teacher_remarks ?? '') : '' }}</textarea>
                                        </td>
                                    </tr>
                                    @empty
                                    <tr>
                                        <td colspan="7" class="text-center py-4">
                                            <div class="d-flex flex-column align-items-center">
                                                <span class="material-symbols-outlined text-muted" style="font-size: 48px;">inbox</span>
                                                <p class="text-muted mt-2 mb-0">No students found. Please apply filters to view students.</p>
                                            </div>
                                        </td>
                                    </tr>
                                    @endforelse
                                </tbody>
                            </table>
                            
                            @if($students->count() > 0)
                            <div class="text-center mt-3">
                                <button type="submit" class="btn save-remarks-btn px-4 py-2">
                                    <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle; color: white;">save</span>
                                    <span style="color: white; font-size: 14px;">Save Teacher Remarks</span>
                                </button>
                            </div>
                            @endif
                        </form>
                    </div>
                </div>
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

.search-input-group {
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
    transition: all 0.3s ease;
}

.search-input-group:focus-within {
    box-shadow: 0 4px 8px rgba(0, 52, 113, 0.15);
}

.marks-input {
    width: 120px;
    text-align: center;
    background-color: #f8f9fa;
    font-weight: 500;
    font-size: 13px;
    border: 1px solid #dee2e6;
    border-radius: 4px;
    padding: 4px 8px;
    color: #495057;
}

.marks-input:focus {
    outline: none;
    border-color: #dee2e6;
    box-shadow: none;
}

.remarks-input {
    resize: vertical;
    min-height: 60px;
    font-size: 13px;
    line-height: 1.4;
    border: 1px solid #dee2e6;
}

.save-remarks-btn {
    background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
    color: white;
    border: none;
    font-weight: 500;
    transition: all 0.3s ease;
    box-shadow: 0 2px 6px rgba(0, 52, 113, 0.2);
}

.save-remarks-btn:hover {
    background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(0, 52, 113, 0.3);
    color: white;
}

.save-remarks-btn:active {
    transform: translateY(0);
}

.table th {
    background-color: #f8f9fa;
    font-weight: 600;
    font-size: 13px;
    color: #003471;
    border-bottom: 2px solid #dee2e6;
    padding: 10px 8px;
}

.table td {
    padding: 10px 8px;
    font-size: 13px;
    vertical-align: middle;
}

.table-hover tbody tr:hover {
    background-color: #f8f9fa;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const campusSelect = document.getElementById('filter_campus');
    const classSelect = document.getElementById('filter_class');
    const sectionSelect = document.getElementById('filter_section');
    const initialClass = "{{ $filterClass ?? '' }}";

    function loadClasses() {
        if (!classSelect) return;

        const campus = campusSelect ? campusSelect.value : '';
        classSelect.innerHTML = '<option value="">Loading...</option>';
        classSelect.disabled = true;

        fetch(`{{ route('exam.teacher-remarks.get-classes') }}?campus=${encodeURIComponent(campus)}`)
            .then(response => response.json())
            .then(data => {
                classSelect.innerHTML = '<option value="">All Classes</option>';
                if (data.classes && data.classes.length > 0) {
                    data.classes.forEach(className => {
                        classSelect.innerHTML += `<option value="${className}">${className}</option>`;
                    });
                }
                classSelect.disabled = false;
                if (initialClass && data.classes && data.classes.includes(initialClass)) {
                    classSelect.value = initialClass;
                    loadSections(initialClass);
                } else {
                    sectionSelect.disabled = true;
                    sectionSelect.innerHTML = '<option value="">All Sections</option>';
                }
            })
            .catch(error => {
                console.error('Error loading classes:', error);
                classSelect.innerHTML = '<option value="">Error loading classes</option>';
                classSelect.disabled = false;
            });
    }

    function loadSections(selectedClass) {
        if (selectedClass) {
            sectionSelect.disabled = false;
            sectionSelect.innerHTML = '<option value="">Loading...</option>';
            
            const campus = campusSelect ? campusSelect.value : '';
            const params = new URLSearchParams();
            params.append('class', selectedClass);
            if (campus) {
                params.append('campus', campus);
            }
            fetch(`{{ route('exam.teacher-remarks.get-sections') }}?${params.toString()}`)
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
            sectionSelect.disabled = true;
            sectionSelect.innerHTML = '<option value="">All Sections</option>';
        }
    }

    classSelect.addEventListener('change', function() {
        loadSections(this.value);
    });

    if (campusSelect) {
        campusSelect.addEventListener('change', function() {
            loadClasses();
        });
    }

    // Initial load if class is already selected
    loadClasses();

    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const studentRows = document.querySelectorAll('.student-row');

    function filterStudents() {
        const searchTerm = searchInput.value.toLowerCase().trim();
        let visibleCount = 0;

        studentRows.forEach(row => {
            const studentName = row.getAttribute('data-student-name') || '';
            const studentCode = row.getAttribute('data-student-code') || '';
            
            if (studentName.includes(searchTerm) || studentCode.includes(searchTerm)) {
                row.style.display = '';
                visibleCount++;
            } else {
                row.style.display = 'none';
            }
        });

        // Show/hide clear button
        clearSearchBtn.style.display = searchTerm ? 'block' : 'none';
    }

    searchInput.addEventListener('input', filterStudents);

    clearSearchBtn.addEventListener('click', function() {
        searchInput.value = '';
        filterStudents();
        searchInput.focus();
    });

    // Keyboard navigation for remarks textarea
    const remarksInputs = document.querySelectorAll('.remarks-input');
    remarksInputs.forEach((input, index) => {
        input.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowDown' && index < remarksInputs.length - 1) {
                e.preventDefault();
                remarksInputs[index + 1].focus();
            } else if (e.key === 'ArrowUp' && index > 0) {
                e.preventDefault();
                remarksInputs[index - 1].focus();
            }
        });
    });
});
</script>
@endsection
