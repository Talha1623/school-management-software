@extends('layouts.app')

@section('title', 'Assign Grades - For Particular Test')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Assign Grades - For Particular Test</h4>
            </div>

            @if(session('success'))
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">check_circle</span>
                {{ session('success') }}
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
            @endif

            <!-- Filter Form -->
            <form action="{{ route('test.assign-grades.particular') }}" method="GET" id="filterForm">
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
                        <select class="form-select form-select-sm" id="filter_section" name="filter_section" style="height: 32px;" {{ !$filterClass ? 'disabled' : '' }}>
                            <option value="">All Sections</option>
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

                    <!-- Filter Button -->
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm py-1 px-3 rounded-8 filter-btn w-100" style="height: 32px;">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">filter_alt</span>
                            <span style="font-size: 12px;">Filter</span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Add Grade Button - Show when filters are applied -->
            @if(request()->hasAny(['filter_campus', 'filter_class', 'filter_section', 'filter_subject', 'filter_test']))
            <div class="mt-3 mb-3">
                <button type="button" class="btn btn-primary add-grade-btn" data-bs-toggle="modal" data-bs-target="#addGradeModal">
                    <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">add</span>
                    Add Grade
                </button>
            </div>
            @endif

            <!-- Grade Definitions Table -->
            @if(isset($gradeDefinitions) && $gradeDefinitions->count() > 0)
            <div class="mt-3">
                <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 18px;">grade</span>
                        <span>Grade Definitions</span>
                    </h5>
                </div>

                <div class="default-table-area" style="margin-top: 0;">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Grade Name</th>
                                    <th>Test</th>
                                    <th>Class/Section</th>
                                    <th>Subject</th>
                                    <th>From %</th>
                                    <th>To %</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($gradeDefinitions as $index => $grade)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        <strong class="text-primary">{{ $grade->name }}</strong>
                                    </td>
                                    <td>{{ $grade->for_test }}</td>
                                    <td>
                                        {{ $grade->class }}@if($grade->section) / {{ $grade->section }}@endif
                                    </td>
                                    <td>{{ $grade->subject ?? 'N/A' }}</td>
                                    <td>{{ number_format($grade->from_percentage, 2) }}%</td>
                                    <td>{{ number_format($grade->to_percentage, 2) }}%</td>
                                    <td>
                                        <form action="{{ route('test.assign-grades.particular.delete', $grade->id) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this grade definition?');">
                                            @csrf
                                            @method('DELETE')
                                            <input type="hidden" name="filter_campus" value="{{ $filterCampus }}">
                                            <input type="hidden" name="filter_class" value="{{ $filterClass }}">
                                            <input type="hidden" name="filter_section" value="{{ $filterSection }}">
                                            <input type="hidden" name="filter_subject" value="{{ $filterSubject }}">
                                            <input type="hidden" name="filter_test" value="{{ $filterTest }}">
                                            <button type="submit" class="btn btn-sm btn-danger" style="font-size: 11px; padding: 4px 8px;">
                                                <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">delete</span>
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                                @endforeach
                            </tbody>
                            <tfoot>
                                <tr class="fw-bold" style="background-color: #f8f9fa;">
                                    <td colspan="7" class="text-end">Total Grades:</td>
                                    <td>{{ $gradeDefinitions->count() }}</td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
            @elseif(request()->hasAny(['filter_campus', 'filter_class', 'filter_section', 'filter_subject', 'filter_test']))
            <div class="text-center py-5">
                <span class="material-symbols-outlined text-muted" style="font-size: 64px;">inbox</span>
                <p class="text-muted mt-3 mb-0">No grade definitions found. Click "Add Grade" to create grade definitions.</p>
            </div>
            @else
            <div class="text-center py-5">
                <span class="material-symbols-outlined text-muted" style="font-size: 64px;">filter_alt</span>
                <p class="text-muted mt-3 mb-0">Please apply filters to view students and assign grades</p>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Add Grade Modal -->
<div class="modal fade" id="addGradeModal" tabindex="-1" aria-labelledby="addGradeModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                <h5 class="modal-title text-white" id="addGradeModalLabel">
                    <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">add</span>
                    Add Grade Definition
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addGradeForm" action="{{ route('test.assign-grades.particular.store-grade') }}" method="POST">
                @csrf
                <div class="modal-body">
                    <div class="row g-3">
                        <!-- Campus -->
                        <div class="col-md-6">
                            <label for="modal_campus" class="form-label fs-12 fw-semibold" style="color: #003471;">Campus <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="modal_campus" name="campus" required style="height: 32px;">
                                <option value="">Select Campus</option>
                                @foreach($campuses as $campus)
                                    @php
                                        $campusName = is_object($campus) ? ($campus->campus_name ?? '') : $campus;
                                    @endphp
                                    <option value="{{ $campusName }}" {{ $filterCampus == $campusName ? 'selected' : '' }}>{{ $campusName }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Name (Grade Name) -->
                        <div class="col-md-6">
                            <label for="modal_name" class="form-label fs-12 fw-semibold" style="color: #003471;">Name (Grade) <span class="text-danger">*</span></label>
                            <input type="text" class="form-control form-control-sm" id="modal_name" 
                                   name="name" placeholder="e.g., A+, A, B+, etc." required style="height: 32px;">
                        </div>

                        <!-- From % -->
                        <div class="col-md-6">
                            <label for="modal_from_percentage" class="form-label fs-12 fw-semibold" style="color: #003471;">From % <span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-sm" id="modal_from_percentage" 
                                   name="from_percentage" min="0" max="100" step="0.01" required style="height: 32px;">
                        </div>

                        <!-- To % -->
                        <div class="col-md-6">
                            <label for="modal_to_percentage" class="form-label fs-12 fw-semibold" style="color: #003471;">To % <span class="text-danger">*</span></label>
                            <input type="number" class="form-control form-control-sm" id="modal_to_percentage" 
                                   name="to_percentage" min="0" max="100" step="0.01" required style="height: 32px;">
                        </div>

                        <!-- For Test -->
                        <div class="col-md-6">
                            <label for="modal_for_test" class="form-label fs-12 fw-semibold" style="color: #003471;">For Test <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="modal_for_test" name="for_test" required style="height: 32px;">
                                <option value="">Select Test</option>
                                @foreach($tests as $testName)
                                    <option value="{{ $testName }}" {{ $filterTest == $testName ? 'selected' : '' }}>{{ $testName }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Class -->
                        <div class="col-md-6">
                            <label for="modal_class" class="form-label fs-12 fw-semibold" style="color: #003471;">Class <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="modal_class" name="class" required style="height: 32px;">
                                <option value="">Select Class</option>
                                @foreach($classes as $className)
                                    <option value="{{ $className }}" {{ $filterClass == $className ? 'selected' : '' }}>{{ $className }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Section -->
                        <div class="col-md-6">
                            <label for="modal_section" class="form-label fs-12 fw-semibold" style="color: #003471;">Section</label>
                            <select class="form-select form-select-sm" id="modal_section" name="section" style="height: 32px;">
                                <option value="">Select Section</option>
                                @if($filterClass)
                                    @foreach($sections as $sectionName)
                                        <option value="{{ $sectionName }}" {{ $filterSection == $sectionName ? 'selected' : '' }}>{{ $sectionName }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>

                        <!-- Subject -->
                        <div class="col-md-6">
                            <label for="modal_subject" class="form-label fs-12 fw-semibold" style="color: #003471;">Subject</label>
                            <select class="form-select form-select-sm" id="modal_subject" name="subject" style="height: 32px;">
                                <option value="">Select Subject</option>
                                @foreach($subjects as $subjectName)
                                    <option value="{{ $subjectName }}" {{ $filterSubject == $subjectName ? 'selected' : '' }}>{{ $subjectName }}</option>
                                @endforeach
                            </select>
                        </div>

                        <!-- Session -->
                        <div class="col-md-6">
                            <label for="modal_session" class="form-label fs-12 fw-semibold" style="color: #003471;">Session <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm" id="modal_session" name="session" required style="height: 32px;">
                                <option value="">Select Session</option>
                                @foreach($sessions as $sessionName)
                                    <option value="{{ $sessionName }}">{{ $sessionName }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">
                        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">save</span>
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

.add-grade-btn {
    background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
    color: white;
    border: none;
    padding: 8px 20px;
    border-radius: 8px;
    transition: all 0.3s ease;
}

.add-grade-btn:hover {
    background: linear-gradient(135deg, #20c997 0%, #28a745 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
    color: white;
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

.save-grade-btn:hover {
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.2);
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Load sections when class changes
    const classSelect = document.getElementById('filter_class');
    const sectionSelect = document.getElementById('filter_section');
    const modalClassSelect = document.getElementById('modal_class');
    const modalSectionSelect = document.getElementById('modal_section');

    if (classSelect && sectionSelect) {
        function loadSections(selectedClass, targetSelect) {
            if (selectedClass) {
                if (targetSelect) targetSelect.disabled = false;
                if (targetSelect) targetSelect.innerHTML = '<option value="">Loading...</option>';
                
                fetch(`{{ route('test.assign-grades.get-sections-by-class') }}?class=${encodeURIComponent(selectedClass)}`)
                    .then(response => response.json())
                    .then(data => {
                        if (targetSelect) {
                            targetSelect.innerHTML = '<option value="">All Sections</option>';
                            data.sections.forEach(section => {
                                const option = document.createElement('option');
                                option.value = section;
                                option.textContent = section;
                                @if($filterSection)
                                if (section === '{{ $filterSection }}') {
                                    option.selected = true;
                                }
                                @endif
                                targetSelect.appendChild(option);
                            });
                        }
                        
                        // Load subjects after sections are loaded
                        loadSubjects();
                    })
                    .catch(error => {
                        console.error('Error loading sections:', error);
                        if (targetSelect) targetSelect.innerHTML = '<option value="">Error loading sections</option>';
                        loadSubjects();
                    });
            } else {
                if (targetSelect) {
                    targetSelect.disabled = true;
                    targetSelect.innerHTML = '<option value="">All Sections</option>';
                }
                loadSubjects();
            }
        }

        if (classSelect) {
            classSelect.addEventListener('change', function() {
                loadSections(this.value, sectionSelect);
            });
        }

        if (modalClassSelect) {
            modalClassSelect.addEventListener('change', function() {
                loadSections(this.value, modalSectionSelect);
            });
        }

        // Load sections on page load if class is already selected
        @if($filterClass)
        loadSections('{{ $filterClass }}', sectionSelect);
        @endif
    }
    
    // Function to load subjects dynamically
    const campusSelect = document.getElementById('filter_campus');
    const subjectSelect = document.getElementById('filter_subject');
    const modalCampusSelect = document.getElementById('modal_campus');
    const modalSubjectSelect = document.getElementById('modal_subject');
    
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
            
            fetch(`{{ route('test.assign-grades.get-subjects') }}?${params.toString()}`)
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
    
    // Load subjects when class changes
    if (classSelect) {
        classSelect.addEventListener('change', function() {
            // Wait a bit for sections to load first
            setTimeout(loadSubjects, 500);
        });
    }
    
    // Load subjects when section changes
    if (sectionSelect) {
        sectionSelect.addEventListener('change', function() {
            loadSubjects();
        });
    }
    
    // Load subjects when campus changes
    if (campusSelect) {
        campusSelect.addEventListener('change', function() {
            loadSubjects();
        });
    }
    
    // Load subjects on page load if filters are already selected
    @if($filterClass)
    setTimeout(loadSubjects, 1000);
    @endif

});
</script>
@endsection
