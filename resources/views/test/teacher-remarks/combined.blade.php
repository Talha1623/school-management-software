@extends('layouts.app')

@section('title', 'Teacher Remarks - For Combine Result')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Teacher Remarks - For Combine Result</h4>
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
            <form action="{{ route('test.teacher-remarks.combined') }}" method="GET" id="filterForm">
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
                            @foreach($sessions as $session)
                                <option value="{{ $session }}" {{ $filterSession == $session ? 'selected' : '' }}>{{ $session }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Class/Section -->
                    <div class="col-md-3">
                        <label for="filter_class_section" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class/Section</label>
                        <select class="form-select form-select-sm" id="filter_class_section" name="filter_class_section" style="height: 32px;">
                            <option value="">All Classes/Sections</option>
                            @foreach($classSectionOptions as $option)
                                <option value="{{ $option }}" {{ $filterClassSection == $option ? 'selected' : '' }}>{{ $option }}</option>
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

            <!-- Results Table -->
            @if(request()->hasAny(['filter_campus', 'filter_session', 'filter_class_section']))
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
                        <form id="remarksForm" method="POST" action="{{ route('test.teacher-remarks.combined.save') }}">
                            @csrf
                            <input type="hidden" name="campus" value="{{ request('filter_campus') }}">
                            <input type="hidden" name="session" value="{{ request('filter_session') }}">
                            <input type="hidden" name="class_section" value="{{ request('filter_class_section') }}">
                            
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>#</th>
                                        <th>Roll</th>
                                        <th>Name</th>
                                        <th>Parent</th>
                                        <th>Total</th>
                                        <th>Obtained</th>
                                        <th>Teacher Remarks For combined Test Result</th>
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
                                            <input type="text" class="form-control form-control-sm" value="{{ isset($student->combinedTotal) && $student->combinedTotal !== null ? number_format($student->combinedTotal, 2) : '0' }}" readonly style="width: 80px; text-align: center; background-color: #f8f9fa; font-weight: 500;">
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm" value="{{ isset($student->combinedObtained) && $student->combinedObtained !== null ? number_format($student->combinedObtained, 2) : '0' }}" readonly style="width: 80px; text-align: center; background-color: #f8f9fa; font-weight: 500;">
                                        </td>
                                        <td>
                                            <textarea name="remarks[{{ $student->id }}]" class="form-control form-control-sm remarks-input" placeholder="Type Teacher Remarks for {{ $student->student_name }}" rows="2" style="min-width: 300px;">{{ $student->combinedRemark->teacher_remarks ?? '' }}</textarea>
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

.default-table-area table {
    margin-bottom: 0;
    border-spacing: 0;
    border-collapse: collapse;
}

.default-table-area table thead th {
    padding: 8px 12px;
    font-size: 13px;
    font-weight: 600;
    vertical-align: middle;
    line-height: 1.3;
    white-space: nowrap;
    border: 1px solid #dee2e6;
    background-color: #f8f9fa;
}

.default-table-area table tbody td {
    padding: 8px 12px;
    font-size: 13px;
    vertical-align: middle;
    line-height: 1.4;
    border: 1px solid #dee2e6;
}

.default-table-area tbody tr:hover {
    background-color: #f8f9fa;
}

.default-table-area .form-control-sm {
    font-size: 12px;
    padding: 4px 8px;
    height: auto;
}

.search-input-group {
    border-radius: 8px;
    overflow: hidden;
    border: 1px solid #dee2e6;
    transition: all 0.3s ease;
    height: 32px;
}

.search-input-group:focus-within {
    border-color: #003471;
    box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
}

.search-input-group .form-control {
    border: none;
    font-size: 13px;
    height: 32px;
}

.remarks-input {
    resize: vertical;
    min-height: 60px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const campusSelect = document.getElementById('filter_campus');
    const classSectionSelect = document.getElementById('filter_class_section');

    // Load class/sections when campus changes
    if (campusSelect) {
        campusSelect.addEventListener('change', function() {
            loadClassSections();
        });
    }

    function loadClassSections() {
        if (!classSectionSelect) return;
        
        const campus = campusSelect ? campusSelect.value : '';
        
        if (!campus) {
            // Load all if no campus selected
            classSectionSelect.innerHTML = '<option value="">All Classes/Sections</option>';
            @foreach($classSectionOptions as $option)
                const option{{ $loop->index }} = document.createElement('option');
                option{{ $loop->index }}.value = '{{ $option }}';
                option{{ $loop->index }}.textContent = '{{ $option }}';
                @if($filterClassSection == $option)
                    option{{ $loop->index }}.selected = true;
                @endif
                classSectionSelect.appendChild(option{{ $loop->index }});
            @endforeach
            return;
        }
        
        classSectionSelect.innerHTML = '<option value="">Loading...</option>';
        
        fetch(`{{ route('test.teacher-remarks.combined.get-class-sections') }}?campus=${encodeURIComponent(campus)}`)
            .then(response => response.json())
            .then(data => {
                classSectionSelect.innerHTML = '<option value="">All Classes/Sections</option>';
                if (data.classSections && data.classSections.length > 0) {
                    data.classSections.forEach(option => {
                        const opt = document.createElement('option');
                        opt.value = option;
                        opt.textContent = option;
                        if (option === '{{ $filterClassSection }}') {
                            opt.selected = true;
                        }
                        classSectionSelect.appendChild(opt);
                    });
                }
            })
            .catch(error => {
                console.error('Error loading class/sections:', error);
                classSectionSelect.innerHTML = '<option value="">Error loading class/sections</option>';
            });
    }

    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const studentsTableBody = document.getElementById('studentsTableBody');

    if (searchInput && studentsTableBody) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase();
            const studentRows = studentsTableBody.querySelectorAll('.student-row');
            
            if (searchTerm.length > 0) {
                clearSearchBtn.style.display = 'inline-block';
            } else {
                clearSearchBtn.style.display = 'none';
            }

            studentRows.forEach(row => {
                const studentName = row.dataset.studentName || '';
                const studentCode = row.dataset.studentCode || '';
                
                if (studentName.includes(searchTerm) || studentCode.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });

        clearSearchBtn.addEventListener('click', function() {
            searchInput.value = '';
            searchInput.dispatchEvent(new Event('input'));
        });
    }

    // Keyboard navigation for input fields
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
