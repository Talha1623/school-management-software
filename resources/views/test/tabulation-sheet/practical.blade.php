@extends('layouts.app')

@section('title', 'Tabulation Sheet - For Practical Test')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Tabulation Sheet - For Practical Test</h4>
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
            <form action="{{ route('test.tabulation-sheet.practical') }}" method="GET" id="filterForm">
                <div class="row g-2 mb-3 align-items-end">
                    <!-- Campus -->
                    <div class="col-md-2">
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
                        <select class="form-select form-select-sm" id="filter_section" name="filter_section" style="height: 32px;">
                            <option value="">All Sections</option>
                            @foreach($sections as $sectionName)
                                <option value="{{ $sectionName }}" {{ $filterSection == $sectionName ? 'selected' : '' }}>{{ $sectionName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Subject -->
                    <div class="col-md-2">
                        <label for="filter_subject" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Subject</label>
                        <select class="form-select form-select-sm" id="filter_subject" name="filter_subject" style="height: 32px;">
                            <option value="">All Subjects</option>
                            @foreach($subjects as $subjectName)
                                <option value="{{ $subjectName }}" {{ $filterSubject == $subjectName ? 'selected' : '' }}>{{ $subjectName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Test -->
                    <div class="col-md-2">
                        <label for="filter_test" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Test</label>
                        <select class="form-select form-select-sm" id="filter_test" name="filter_test" style="height: 32px;">
                            <option value="">All Tests</option>
                            @foreach($tests as $testName)
                                <option value="{{ $testName }}" {{ $filterTest == $testName ? 'selected' : '' }}>{{ $testName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Type -->
                    <div class="col-md-2">
                        <label for="filter_type" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Type</label>
                        <select class="form-select form-select-sm" id="filter_type" name="filter_type" style="height: 32px;">
                            <option value="normal" {{ $filterType == 'normal' ? 'selected' : '' }}>Normal</option>
                            <option value="editable" {{ $filterType == 'editable' ? 'selected' : '' }}>Editable</option>
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
            @if(request()->hasAny(['filter_campus', 'filter_class', 'filter_section', 'filter_subject', 'filter_test']))
            <div class="mt-3">
                <div class="mb-2 p-2 rounded-8 d-flex justify-content-between align-items-center" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 18px;">table_chart</span>
                        <span>Tabulation Sheet</span>
                    </h5>
                    <button type="button" class="btn btn-light btn-sm print-btn" onclick="window.print()">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                        <span style="font-size: 12px;">Print</span>
                    </button>
                </div>

                <div class="default-table-area" style="margin-top: 0;">
                    @if($filterType == 'editable')
                    <form method="POST" action="{{ route('test.tabulation-sheet.practical.save') }}" id="tabulationSaveForm">
                        @csrf
                        <input type="hidden" name="campus" value="{{ $filterCampus }}">
                        <input type="hidden" name="class" value="{{ $filterClass }}">
                        <input type="hidden" name="section" value="{{ $filterSection ?? '' }}">
                        <input type="hidden" name="subject" value="{{ $filterSubject ?? '' }}">
                        <input type="hidden" name="test_name" value="{{ $filterTest }}">
                    @endif
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student Code</th>
                                    <th>Student Name</th>
                                    <th>Class</th>
                                    <th>Section</th>
                                    <th>Marks</th>
                                    <th>Grade</th>
                                    <th>Remarks</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($students as $index => $student)
                                @php
                                    $mark = $studentMarks->get($student->id);
                                    $marksObtained = $mark ? $mark->marks_obtained : null;
                                    $totalMarks = $mark ? $mark->total_marks : 100; // Default to 100 if not set
                                    $remarks = $mark ? $mark->teacher_remarks : null;
                                    
                                    // Calculate grade based on marks
                                    $calculatedGrade = null;
                                    if ($marksObtained && $totalMarks && $totalMarks > 0) {
                                        $percentage = ($marksObtained / $totalMarks) * 100;
                                        foreach ($gradeDefinitions as $gradeDef) {
                                            if ($percentage >= $gradeDef->from_percentage && $percentage <= $gradeDef->to_percentage) {
                                                $calculatedGrade = $gradeDef->name;
                                                break;
                                            }
                                        }
                                    }
                                @endphp
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $student->student_code ?? 'N/A' }}</td>
                                    <td>
                                        <strong class="text-primary">{{ $student->student_name }}</strong>
                                    </td>
                                    <td>{{ $student->class }}</td>
                                    <td>
                                        <span class="badge bg-info text-white">{{ $student->section ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        @if($filterType == 'editable')
                                            <input type="hidden" name="marks[{{ $student->id }}][total]" value="{{ $totalMarks }}">
                                            <input type="number" name="marks[{{ $student->id }}][obtained]" class="form-control form-control-sm marks-input" 
                                                   data-student-id="{{ $student->id }}" 
                                                   data-total-marks="{{ $totalMarks }}"
                                                   value="{{ $marksObtained }}" 
                                                   placeholder="Marks" min="0" step="0.01" 
                                                   style="width: 100px; display: inline-block;">
                                        @else
                                            <span class="marks-display" data-student-id="{{ $student->id }}">
                                                {{ $marksObtained ? number_format($marksObtained, 2) : '-' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($filterType == 'editable')
                                            <span class="grade-display-calculated badge bg-success" 
                                                  data-student-id="{{ $student->id }}" 
                                                  style="font-size: 12px; padding: 4px 8px;">
                                                {{ $calculatedGrade ?? '-' }}
                                            </span>
                                        @else
                                            <span class="grade-display badge bg-success" data-student-id="{{ $student->id }}" style="font-size: 12px; padding: 4px 8px;">
                                                {{ $calculatedGrade ?? '-' }}
                                            </span>
                                        @endif
                                    </td>
                                    <td>
                                        @if($filterType == 'editable')
                                            <textarea name="marks[{{ $student->id }}][remarks]" class="form-control form-control-sm remarks-input" 
                                                      data-student-id="{{ $student->id }}" 
                                                      placeholder="Remarks" rows="1" 
                                                      style="width: 150px; min-width: 150px;">{{ $remarks }}</textarea>
                                        @else
                                            <span class="remarks-display" data-student-id="{{ $student->id }}">
                                                {{ $remarks ?? '-' }}
                                            </span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="8" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <span class="material-symbols-outlined text-muted" style="font-size: 48px;">inbox</span>
                                            <p class="text-muted mt-2 mb-0">No students found</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if($students->count() > 0)
                            <tfoot>
                                <tr class="fw-bold" style="background-color: #f8f9fa;">
                                    <td colspan="7" class="text-end">Total Students:</td>
                                    <td>{{ $students->count() }}</td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                    @if($filterType == 'editable' && $students->count() > 0)
                    <div class="mt-3 no-print">
                        <button type="submit" class="btn btn-primary btn-sm px-4 py-2 rounded-8">
                            <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">save</span>
                            <span>Save Marks</span>
                        </button>
                    </div>
                    @endif
                    @if($filterType == 'editable')
                    </form>
                    @endif
                </div>
            </div>
            @else
            <div class="text-center py-5">
                <span class="material-symbols-outlined text-muted" style="font-size: 64px;">filter_alt</span>
                <p class="text-muted mt-3 mb-0">Please apply filters to view tabulation sheet</p>
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
}

.default-table-area table {
    margin-bottom: 0;
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

.default-table-area tbody td {
    font-size: 13px;
    padding: 12px;
    vertical-align: middle;
}

.default-table-area tbody tr:hover {
    background-color: #f8f9fa;
}

.badge {
    font-size: 11px;
    padding: 4px 8px;
}

.print-btn {
    border: 1px solid rgba(255, 255, 255, 0.3);
    transition: all 0.3s ease;
}

.print-btn:hover {
    background-color: rgba(255, 255, 255, 0.9) !important;
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}

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
    .settings-btn {
        display: none !important;
        visibility: hidden !important;
    }
    
    /* Hide filter form and buttons */
    .filter-btn,
    .print-btn,
    #filterForm,
    .mb-2.p-2.rounded-8,
    .d-flex.justify-content-between,
    h4.mb-0,
    .card-header,
    .no-print {
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
    }
    
    .container-fluid,
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
    
    /* Hide card title */
    h4.mb-0.fs-16 {
        display: none !important;
    }
    
    /* Table area */
    .default-table-area {
        border: none !important;
        margin: 0 !important;
        padding: 0 !important;
        background: white !important;
    }
    
    /* Table styles */
    .table {
        width: 100% !important;
        border-collapse: collapse !important;
    }
    
    .table th,
    .table td {
        border: 1px solid #000 !important;
        padding: 8px !important;
        font-size: 12px !important;
    }
    
    .table thead {
        background: #f0f0f0 !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    
    /* Ensure table prints properly */
    .table-responsive {
        overflow: visible !important;
    }
    
    /* Page break */
    .table {
        page-break-inside: auto;
    }
    
    .table tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }
    
    .table thead {
        display: table-header-group;
    }
    
    .table tfoot {
        display: table-footer-group;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load sections when class changes
    const campusSelect = document.getElementById('filter_campus');
    const classSelect = document.getElementById('filter_class');
    const sectionSelect = document.getElementById('filter_section');
    const subjectSelect = document.getElementById('filter_subject');
    
    // Function to load sections dynamically
    function loadSections(selectedClass) {
        if (selectedClass) {
            sectionSelect.disabled = false;
            sectionSelect.innerHTML = '<option value="">Loading...</option>';
            
            fetch(`{{ route('test.tabulation-sheet.practical.get-sections') }}?class=${encodeURIComponent(selectedClass)}`)
                .then(response => response.json())
                .then(data => {
                    sectionSelect.innerHTML = '<option value="">All Sections</option>';
                    data.sections.forEach(section => {
                        const option = document.createElement('option');
                        option.value = section;
                        option.textContent = section;
                        @if($filterSection)
                        if (section === '{{ $filterSection }}') {
                            option.selected = true;
                        }
                        @endif
                        sectionSelect.appendChild(option);
                    });
                    
                    // Load subjects after sections are loaded
                    loadSubjects();
                })
                .catch(error => {
                    console.error('Error loading sections:', error);
                    sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
                    loadSubjects();
                });
        } else {
            sectionSelect.disabled = true;
            sectionSelect.innerHTML = '<option value="">All Sections</option>';
            loadSubjects();
        }
    }
    
    // Function to load subjects dynamically
    function loadSubjects() {
        const campus = campusSelect ? campusSelect.value : '';
        const classValue = classSelect ? classSelect.value : '';
        const section = sectionSelect ? sectionSelect.value : '';
        
        if (!classValue) {
            // If no class selected, clear subjects
            if (subjectSelect) {
                subjectSelect.innerHTML = '<option value="">All Subjects</option>';
            }
            return;
        }
        
        // Build query parameters
        const params = new URLSearchParams();
        if (campus) params.append('campus', campus);
        if (classValue) params.append('class', classValue);
        if (section) params.append('section', section);
        
        // Show loading state
        if (subjectSelect) {
            const currentValue = subjectSelect.value;
            subjectSelect.innerHTML = '<option value="">Loading...</option>';
            
            fetch(`{{ route('test.tabulation-sheet.practical.get-subjects') }}?${params.toString()}`)
                .then(response => response.json())
                .then(data => {
                    subjectSelect.innerHTML = '<option value="">All Subjects</option>';
                    if (data.subjects && data.subjects.length > 0) {
                        data.subjects.forEach(function(subject) {
                            const option = document.createElement('option');
                            option.value = subject;
                            option.textContent = subject;
                            // Restore selected value if it still exists
                            if (subject === currentValue) {
                                option.selected = true;
                            }
                            subjectSelect.appendChild(option);
                        });
                    }
                })
                .catch(error => {
                    console.error('Error loading subjects:', error);
                    subjectSelect.innerHTML = '<option value="">All Subjects</option>';
                });
        }
    }
    
    // Load sections when class changes
    if (classSelect) {
        classSelect.addEventListener('change', function() {
            loadSections(this.value);
        });
    }
    
    // Load subjects when campus or section changes
    if (campusSelect) {
        campusSelect.addEventListener('change', function() {
            const classValue = classSelect ? classSelect.value : '';
            if (classValue) {
                loadSubjects();
            }
        });
    }
    
    // Load subjects when section changes
    if (sectionSelect) {
        sectionSelect.addEventListener('change', function() {
            loadSubjects();
        });
    }
    
    // Load sections on page load if class is already selected
    @if($filterClass)
    loadSections('{{ $filterClass }}');
    @endif
    
    
    // Auto-calculate grade based on marks using grade definitions (only for editable mode)
    @if($filterType == 'editable')
    // Grade definitions from server
    @php
        $gradeDefsArray = $gradeDefinitions->map(function($g) {
            return [
                'name' => $g->name,
                'from_percentage' => (float)$g->from_percentage,
                'to_percentage' => (float)$g->to_percentage
            ];
        })->values()->toArray();
    @endphp
    const gradeDefinitions = @json($gradeDefsArray);
    
    function calculateGradeFromMarks(marks, totalMarks) {
        if (!marks || !totalMarks || totalMarks == 0) {
            return null;
        }
        
        const percentage = (marks / totalMarks) * 100;
        
        for (let i = 0; i < gradeDefinitions.length; i++) {
            const gradeDef = gradeDefinitions[i];
            if (percentage >= gradeDef.from_percentage && percentage <= gradeDef.to_percentage) {
                return gradeDef.name;
            }
        }
        
        return null;
    }
    
    document.querySelectorAll('.marks-input').forEach(function(input) {
        input.addEventListener('input', function() {
            const marks = parseFloat(this.value);
            const studentId = this.getAttribute('data-student-id');
            const totalMarks = parseFloat(this.getAttribute('data-total-marks')) || 100;
            const gradeDisplay = document.querySelector('.grade-display-calculated[data-student-id="' + studentId + '"]');
            
            if (!isNaN(marks) && gradeDisplay) {
                const calculatedGrade = calculateGradeFromMarks(marks, totalMarks);
                if (calculatedGrade) {
                    gradeDisplay.textContent = calculatedGrade;
                    gradeDisplay.classList.remove('bg-secondary');
                    gradeDisplay.classList.add('bg-success');
                } else {
                    gradeDisplay.textContent = '-';
                    gradeDisplay.classList.remove('bg-success');
                    gradeDisplay.classList.add('bg-secondary');
                }
            } else if (gradeDisplay) {
                gradeDisplay.textContent = '-';
                gradeDisplay.classList.remove('bg-success');
                gradeDisplay.classList.add('bg-secondary');
            }
        });
    });
    @endif
});
</script>
@endsection
