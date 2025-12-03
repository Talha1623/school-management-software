@extends('layouts.app')

@section('title', 'Teacher Remarks - For Particular Exam')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Teacher Remarks - For Particular Exam</h4>
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
            <form action="{{ route('exam.teacher-remarks.particular') }}" method="GET" id="filterForm">
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

                    <!-- Exam -->
                    <div class="col-md-3">
                        <label for="filter_exam" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Exam</label>
                        <select class="form-select form-select-sm" id="filter_exam" name="filter_exam" style="height: 32px;" {{ !$filterCampus || !$filterClass ? 'disabled' : '' }}>
                            <option value="">All Exams</option>
                            @if($filterCampus && $filterClass)
                                @foreach($exams as $examName)
                                    <option value="{{ $examName }}" {{ $filterExam == $examName ? 'selected' : '' }}>{{ $examName }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <!-- Class -->
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
            @if($filterCampus && $filterExam && $filterClass)
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
                        <form id="remarksForm" method="POST" action="{{ route('exam.teacher-remarks.particular.save') }}">
                            @csrf
                            <input type="hidden" name="exam_name" value="{{ request('filter_exam') }}">
                            <input type="hidden" name="campus" value="{{ request('filter_campus') }}">
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
                                        <th>Teacher Remarks For Selected Exam</th>
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
                                            <input type="text" class="form-control form-control-sm marks-input" value="{{ isset($student->mark) && $student->mark && $student->mark->total_marks !== null ? number_format($student->mark->total_marks, 2) : '0' }}" readonly>
                                        </td>
                                        <td>
                                            <input type="text" class="form-control form-control-sm marks-input" value="{{ isset($student->mark) && $student->mark && $student->mark->marks_obtained !== null ? number_format($student->mark->marks_obtained, 2) : '0' }}" readonly>
                                        </td>
                                        <td>
                                            <textarea name="remarks[{{ $student->id }}]" class="form-control form-control-sm remarks-input" placeholder="Type Teacher Remarks for {{ $student->student_name }}" rows="2" style="min-width: 300px;">{{ isset($student->mark) && $student->mark ? ($student->mark->teacher_remarks ?? '') : '' }}</textarea>
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

.default-table-area {
    background-color: white;
    border-radius: 8px;
    padding: 15px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.05);
}

.default-table-area table {
    margin-bottom: 0;
}

.default-table-area thead th {
    background-color: #f8f9fa;
    border-bottom: 2px solid #dee2e6;
    color: #003471;
    font-weight: 600;
    font-size: 13px;
    padding: 12px 10px;
    text-transform: uppercase;
    letter-spacing: 0.5px;
}

.default-table-area tbody td {
    padding: 12px 10px;
    vertical-align: middle;
    border-bottom: 1px solid #e9ecef;
    font-size: 13px;
    color: #495057;
}

.default-table-area tbody tr:hover {
    background-color: #f8f9fa;
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const campusSelect = document.getElementById('filter_campus');
    const examSelect = document.getElementById('filter_exam');
    const classSelect = document.getElementById('filter_class');
    const sectionSelect = document.getElementById('filter_section');

    function loadExams() {
        const campus = campusSelect ? campusSelect.value : '';
        const classValue = classSelect ? classSelect.value : '';
        
        if (!examSelect) return;
        
        // Require campus and class to load exams
        if (campus && classValue) {
            examSelect.disabled = false;
            examSelect.innerHTML = '<option value="">Loading...</option>';
            
            const params = new URLSearchParams();
            params.append('campus', campus);
            params.append('class', classValue);
            
            fetch(`{{ route('exam.teacher-remarks.get-exams') }}?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    examSelect.innerHTML = '<option value="">All Exams</option>';
                    if (data && data.length > 0) {
                        data.forEach(exam => {
                            const option = document.createElement('option');
                            option.value = exam;
                            option.textContent = exam;
                            // Preserve selected exam if it exists
                            @if($filterExam)
                            if (exam === '{{ $filterExam }}') {
                                option.selected = true;
                            }
                            @endif
                            examSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading exams:', error);
                    examSelect.innerHTML = '<option value="">Error loading exams</option>';
                });
        } else {
            examSelect.disabled = true;
            examSelect.innerHTML = '<option value="">All Exams</option>';
        }
    }

    function loadSections(selectedClass) {
        if (!sectionSelect) return;
        
        if (selectedClass) {
            sectionSelect.disabled = false;
            sectionSelect.innerHTML = '<option value="">Loading...</option>';
            
            fetch(`{{ route('exam.teacher-remarks.get-sections') }}?class=${encodeURIComponent(selectedClass)}`)
                .then(response => response.json())
                .then(data => {
                    sectionSelect.innerHTML = '<option value="">All Sections</option>';
                    if (data && data.length > 0) {
                        data.forEach(section => {
                            const option = document.createElement('option');
                            option.value = section;
                            option.textContent = section;
                            // Preserve selected section if it exists
                            @if($filterSection)
                            if (section === '{{ $filterSection }}') {
                                option.selected = true;
                            }
                            @endif
                            sectionSelect.appendChild(option);
                        });
                    }
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

    // Load exams when campus or class changes
    if (campusSelect) {
        campusSelect.addEventListener('change', function() {
            loadExams();
        });
    }

    // Load sections and exams when class changes
    if (classSelect) {
        classSelect.addEventListener('change', function() {
            loadSections(this.value);
            loadExams(); // Reload exams when class changes
        });
    }

    // Initial load if filters are already set
    const initialCampus = campusSelect ? campusSelect.value : '';
    const initialClass = classSelect ? classSelect.value : '';
    
    // Load exams if both campus and class are selected
    if (initialCampus && initialClass) {
        loadExams();
    }
    
    // Load sections if class is selected
    if (initialClass) {
        loadSections(initialClass);
    }

    // Search functionality
    const searchInput = document.getElementById('searchInput');
    const clearSearchBtn = document.getElementById('clearSearchBtn');
    const studentRows = document.querySelectorAll('.student-row');

    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const searchTerm = this.value.toLowerCase().trim();
            
            if (searchTerm) {
                clearSearchBtn.style.display = 'block';
            } else {
                clearSearchBtn.style.display = 'none';
            }
            
            studentRows.forEach(row => {
                const studentName = row.getAttribute('data-student-name') || '';
                const studentCode = row.getAttribute('data-student-code') || '';
                
                if (searchTerm === '' || studentName.includes(searchTerm) || studentCode.includes(searchTerm)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            });
        });
    }

    if (clearSearchBtn) {
        clearSearchBtn.addEventListener('click', function() {
            searchInput.value = '';
            this.style.display = 'none';
            studentRows.forEach(row => {
                row.style.display = '';
            });
        });
    }

    // Keyboard navigation for textareas
    const textareas = document.querySelectorAll('.remarks-input');
    textareas.forEach((textarea, index) => {
        textarea.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowDown' && index < textareas.length - 1) {
                e.preventDefault();
                textareas[index + 1].focus();
            } else if (e.key === 'ArrowUp' && index > 0) {
                e.preventDefault();
                textareas[index - 1].focus();
            }
        });
    });
});
</script>
@endsection
