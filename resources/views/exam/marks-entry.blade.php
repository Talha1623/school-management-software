@extends('layouts.app')

@section('title', 'Marks Entry')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Marks Entry</h4>
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
            <form action="{{ route('exam.marks-entry') }}" method="GET" id="filterForm">
                <div class="row g-2 mb-3 align-items-end">
                    <!-- Exam -->
                    <div class="col-md-2">
                        <label for="filter_exam" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Exam Name</label>
                        <select class="form-select form-select-sm" id="filter_exam" name="filter_exam" style="height: 32px;" {{ !$filterClass || !$filterSection ? 'disabled' : '' }}>
                            <option value="">Select Exam</option>
                            @if(isset($exams) && $exams->count() > 0)
                                @foreach($exams as $examName)
                                    <option value="{{ $examName }}" {{ $filterExam == $examName ? 'selected' : '' }}>{{ $examName }}</option>
                                @endforeach
                            @else
                                <option value="" disabled>No exams found</option>
                            @endif
                        </select>
                    </div>

                    <!-- Class -->
                    <div class="col-md-2">
                        <label for="filter_class" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class</label>
                        <select class="form-select form-select-sm" id="filter_class" name="filter_class" style="height: 32px;">
                            <option value="">All Classes</option>
                            @foreach($classes as $className)
                                <option value="{{ $className }}" {{ $filterClass == $className ? 'selected' : '' }}>{{ $className }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Section -->
                    <div class="col-md-2">
                        <label for="filter_section" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Section</label>
                        <select class="form-select form-select-sm" id="filter_section" name="filter_section" style="height: 32px;" {{ !$filterClass ? 'disabled' : '' }}>
                            <option value="">Select Section</option>
                            @if($filterClass)
                                @foreach($sections as $sectionName)
                                    <option value="{{ $sectionName }}" {{ $filterSection == $sectionName ? 'selected' : '' }}>{{ $sectionName }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <!-- Subject -->
                    <div class="col-md-2">
                        <label for="filter_subject" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Subject</label>
                        <select class="form-select form-select-sm" id="filter_subject" name="filter_subject" style="height: 32px;" {{ !$filterClass || !$filterSection ? 'disabled' : '' }}>
                            <option value="">Select Subject</option>
                            @foreach($subjects as $subjectName)
                                <option value="{{ $subjectName }}" {{ $filterSubject == $subjectName ? 'selected' : '' }}>{{ $subjectName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Button -->
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm py-1 px-3 rounded-8 filter-btn w-100" style="height: 32px;">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">filter_alt</span>
                            <span style="font-size: 12px;">Filter</span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Results Table -->
            @if($filterClass && $filterSection)
            <div class="mt-3">
                <!-- Context Card -->
                <div class="card mb-3" style="background-color: #f8f9fa; border: 1px solid #e9ecef;">
                    <div class="card-body p-3">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="d-flex flex-column gap-1">
                                    @if($filterExam)
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fw-semibold" style="color: #003471;">Exam:</span>
                                            <span style="color: #495057;">{{ $filterExam }}</span>
                                        </div>
                                    @endif
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-semibold" style="color: #003471;">Class:</span>
                                        <span style="color: #495057;">{{ $filterClass }}</span>
                                        @if($filterSection)
                                            <span class="fw-semibold" style="color: #003471; margin-left: 10px;">Section:</span>
                                            <span style="color: #495057;">{{ $filterSection }}</span>
                                        @endif
                                    </div>
                                    @if($filterSubject)
                                        <div class="d-flex align-items-center gap-2">
                                            <span class="fw-semibold" style="color: #003471;">Subject:</span>
                                            <span style="color: #495057;">{{ $filterSubject }}</span>
                                        </div>
                                    @endif
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="material-symbols-outlined" style="font-size: 48px; color: #dee2e6; opacity: 0.5;">bar_chart</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Navigation Guide Banner -->
                <div class="alert alert-warning mb-3 d-flex align-items-center gap-2" style="background-color: #fff3cd; border-color: #ffc107; color: #856404;">
                    <span class="material-symbols-outlined" style="font-size: 20px;">info</span>
                    <span style="font-size: 13px;"><strong>Navigation Guide:</strong> Use ← (Left Arrow), → (Right Arrow), ↑ (Up Arrow), and ↓ (Down Arrow) to navigate between input fields.</span>
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
                        <form id="marksForm" method="POST" action="{{ route('exam.marks-entry.save') }}">
                            @csrf
                            <input type="hidden" name="exam_name" value="{{ request('filter_exam') }}">
                            <input type="hidden" name="class" value="{{ request('filter_class') }}">
                            <input type="hidden" name="section" value="{{ request('filter_section') }}">
                            <input type="hidden" name="subject" value="{{ request('filter_subject') }}">
                            
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr>
                                        <th>NO#</th>
                                        <th>Roll</th>
                                        <th>Name</th>
                                        <th>Parent</th>
                                        <th>Marks Obtained</th>
                                        <th>Total Marks</th>
                                        <th>Passing Marks</th>
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
                                            <input type="number" name="marks[{{ $student->id }}][obtained]" class="form-control form-control-sm marks-obtained" placeholder="0" min="0" step="0.01" value="{{ $student->mark && $student->mark->marks_obtained !== null ? $student->mark->marks_obtained : '' }}" style="width: 100px;">
                                        </td>
                                        <td>
                                            <input type="number" name="marks[{{ $student->id }}][total]" class="form-control form-control-sm marks-total" placeholder="0" min="0" step="0.01" value="{{ $student->mark && $student->mark->total_marks !== null ? $student->mark->total_marks : '' }}" style="width: 100px;">
                                        </td>
                                        <td>
                                            <input type="number" name="marks[{{ $student->id }}][passing]" class="form-control form-control-sm marks-passing" placeholder="0" min="0" step="0.01" value="{{ $student->mark && $student->mark->passing_marks !== null ? $student->mark->passing_marks : '' }}" style="width: 100px;">
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
                                    <span style="color: white; font-size: 14px;">Save Result</span>
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
                <p class="text-muted mt-3 mb-0">Please select Class and Section to view students for marks entry</p>
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
    height: 32px;
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
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const classSelect = document.getElementById('filter_class');
    const sectionSelect = document.getElementById('filter_section');
    const subjectSelect = document.getElementById('filter_subject');

    // Load sections when class changes
    if (classSelect) {
        classSelect.addEventListener('change', function() {
            const selectedClass = this.value;
            if (selectedClass) {
                loadSections(selectedClass);
                // Enable section dropdown
                if (sectionSelect) {
                    sectionSelect.disabled = false;
                }
            } else {
                // Disable section, exam, and subject dropdowns if no class selected
                if (sectionSelect) {
                    sectionSelect.disabled = true;
                    sectionSelect.innerHTML = '<option value="">Select Section</option>';
                }
                if (subjectSelect) {
                    subjectSelect.disabled = true;
                    subjectSelect.innerHTML = '<option value="">Select Subject</option>';
                }
                const examSelect = document.getElementById('filter_exam');
                if (examSelect) {
                    examSelect.disabled = true;
                }
            }
            // Also reload subjects when class changes
            loadSubjects();
        });
    }

    // Load subjects when class or section changes
    if (sectionSelect) {
        sectionSelect.addEventListener('change', function() {
            const selectedSection = this.value;
            if (selectedSection && classSelect && classSelect.value) {
                // Enable exam and subject dropdowns
                const examSelect = document.getElementById('filter_exam');
                if (examSelect) {
                    examSelect.disabled = false;
                }
                if (subjectSelect) {
                    subjectSelect.disabled = false;
                }
                loadSubjects();
            } else {
                // Disable exam and subject dropdowns if no section selected
                const examSelect = document.getElementById('filter_exam');
                if (examSelect) {
                    examSelect.disabled = true;
                }
                if (subjectSelect) {
                    subjectSelect.disabled = true;
                    subjectSelect.innerHTML = '<option value="">Select Subject</option>';
                }
            }
        });
    }

    function loadSections(selectedClass) {
        if (!sectionSelect) return;
        
        if (selectedClass) {
            sectionSelect.innerHTML = '<option value="">Loading...</option>';
            
            fetch(`{{ route('exam.marks-entry.get-sections') }}?class=${encodeURIComponent(selectedClass)}`)
                .then(response => response.json())
                .then(data => {
                    sectionSelect.innerHTML = '<option value="">Select Section</option>';
                    if (data.sections && data.sections.length > 0) {
                        data.sections.forEach(section => {
                            const option = document.createElement('option');
                            option.value = section;
                            option.textContent = section;
                            // Preserve selected section if it exists
                            if (section === '{{ $filterSection }}') {
                                option.selected = true;
                            }
                            sectionSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading sections:', error);
                    sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
                });
        } else {
            sectionSelect.innerHTML = '<option value="">Select Section</option>';
        }
    }

    function loadSubjects() {
        if (!subjectSelect) return;
        
        const classValue = classSelect ? classSelect.value : '';
        const section = sectionSelect ? sectionSelect.value : '';
        
        if (!classValue || !section) {
            subjectSelect.innerHTML = '<option value="">Select Subject</option>';
            return;
        }
        
        subjectSelect.innerHTML = '<option value="">Loading...</option>';
        
        const params = new URLSearchParams();
        if (classValue) params.append('class', classValue);
        if (section) params.append('section', section);
        
        fetch(`{{ route('exam.marks-entry.get-subjects') }}?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                subjectSelect.innerHTML = '<option value="">Select Subject</option>';
                if (data.subjects && data.subjects.length > 0) {
                    data.subjects.forEach(subject => {
                        const option = document.createElement('option');
                        option.value = subject;
                        option.textContent = subject;
                        // Preserve selected subject if it exists
                        if (subject === '{{ $filterSubject }}') {
                            option.selected = true;
                        }
                        subjectSelect.appendChild(option);
                    });
                }
            })
            .catch(error => {
                console.error('Error loading subjects:', error);
                subjectSelect.innerHTML = '<option value="">Error loading subjects</option>';
            });
    }

    // Initial load if filters are already set
    const initialClass = classSelect ? classSelect.value : '';
    const initialSection = sectionSelect ? sectionSelect.value : '';
    
    if (initialClass) {
        if (sectionSelect) {
            sectionSelect.disabled = false;
        }
        loadSections(initialClass);
    } else {
        // Disable dependent dropdowns if no class selected
        if (sectionSelect) {
            sectionSelect.disabled = true;
        }
        if (subjectSelect) {
            subjectSelect.disabled = true;
        }
        const examSelect = document.getElementById('filter_exam');
        if (examSelect) {
            examSelect.disabled = true;
        }
    }
    
    if (initialClass && initialSection) {
        // Enable exam and subject dropdowns if both class and section are selected
        const examSelect = document.getElementById('filter_exam');
        if (examSelect) {
            examSelect.disabled = false;
        }
        if (subjectSelect) {
            subjectSelect.disabled = false;
        }
        loadSubjects();
    } else if (initialSection) {
        // If section is selected but class is not, disable subject
        if (subjectSelect) {
            subjectSelect.disabled = true;
        }
        const examSelect = document.getElementById('filter_exam');
        if (examSelect) {
            examSelect.disabled = true;
        }
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
});
</script>
@endsection
