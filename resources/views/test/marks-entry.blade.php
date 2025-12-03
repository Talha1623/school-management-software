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
            <form action="{{ route('test.marks-entry') }}" method="GET" id="filterForm">
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
                        <select class="form-select form-select-sm" id="filter_subject" name="filter_subject" style="height: 32px;" {{ !$filterClass ? 'disabled' : '' }}>
                            <option value="">All Subjects</option>
                            @if($filterClass)
                                @foreach($subjects as $subjectName)
                                    <option value="{{ $subjectName }}" {{ $filterSubject == $subjectName ? 'selected' : '' }}>{{ $subjectName }}</option>
                                @endforeach
                            @endif
                        </select>
                    </div>

                    <!-- Test -->
                    <div class="col-md-2">
                        <label for="filter_test" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Test</label>
                        <select class="form-select form-select-sm" id="filter_test" name="filter_test" style="height: 32px;" {{ !$filterClass ? 'disabled' : '' }}>
                            <option value="">All Tests</option>
                            @if($filterClass)
                                @foreach($tests as $testName)
                                    <option value="{{ $testName }}" {{ $filterTest == $testName ? 'selected' : '' }}>{{ $testName }}</option>
                                @endforeach
                            @endif
                        </select>
                        <small id="testInfoMsg" class="text-muted d-block mt-1" style="font-size: 11px; display: none;">
                            <span class="material-symbols-outlined" style="font-size: 12px; vertical-align: middle;">info</span>
                            No tests with declared results found. Please declare result in Test List first.
                        </small>
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
            @if(request()->hasAny(['filter_campus', 'filter_class', 'filter_section', 'filter_test', 'filter_subject']))
            <div class="mt-3">
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
                        <form id="marksForm" method="POST" action="{{ route('test.marks-entry.save') }}">
                            @csrf
                            <input type="hidden" name="test_id" value="{{ request('filter_test') }}">
                            <input type="hidden" name="campus" value="{{ request('filter_campus') }}">
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
                                            <input type="number" name="marks[{{ $student->id }}][obtained]" class="form-control form-control-sm marks-obtained" placeholder="0" min="0" step="0.01" style="width: 100px;">
                                        </td>
                                        <td>
                                            <input type="number" name="marks[{{ $student->id }}][total]" class="form-control form-control-sm marks-total" placeholder="0" min="0" step="0.01" style="width: 100px;">
                                        </td>
                                        <td>
                                            <input type="number" name="marks[{{ $student->id }}][passing]" class="form-control form-control-sm marks-passing" placeholder="0" min="0" step="0.01" style="width: 100px;">
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
                <p class="text-muted mt-3 mb-0">Please apply filters to view students for marks entry</p>
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

.default-table-area table {
    margin-bottom: 0;
    border-spacing: 0;
    border-collapse: collapse;
    border: 1px solid #dee2e6;
    border-radius: 8px;
    overflow: hidden;
}

.default-table-area table thead {
    border-bottom: 1px solid #dee2e6;
    background-color: #f8f9fa;
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

.default-table-area tbody td {
    font-size: 13px;
    padding: 8px 12px;
    vertical-align: middle;
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

.badge {
    font-size: 11px;
    padding: 4px 8px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const campusSelect = document.getElementById('filter_campus');
    const classSelect = document.getElementById('filter_class');
    const sectionSelect = document.getElementById('filter_section');
    const testSelect = document.getElementById('filter_test');
    const subjectSelect = document.getElementById('filter_subject');

    // On page load, reload subjects if class is already selected (to get latest filtered subjects)
    // This ensures subjects are filtered correctly based on current Class and Section
    if (classSelect && classSelect.value) {
        // Small delay to ensure all elements are ready
        setTimeout(function() {
            loadSubjects();
        }, 100);
    }

    // Load sections when class changes
    if (classSelect) {
        classSelect.addEventListener('change', function() {
            const selectedClass = this.value;
            loadSections(selectedClass);
            // Enable/disable and reload tests and subjects when class changes
            if (selectedClass) {
                if (subjectSelect) {
                    subjectSelect.disabled = false;
                }
                if (testSelect) {
                    testSelect.disabled = false;
                }
                loadTests();
                loadSubjects();
            } else {
                // Disable and clear if no class selected
                if (subjectSelect) {
                    subjectSelect.disabled = true;
                    subjectSelect.innerHTML = '<option value="">All Subjects</option>';
                }
                if (testSelect) {
                    testSelect.disabled = true;
                    testSelect.innerHTML = '<option value="">All Tests</option>';
                    const testInfoMsg = document.getElementById('testInfoMsg');
                    if (testInfoMsg) {
                        testInfoMsg.style.display = 'none';
                    }
                }
            }
        });
    }

    // Load tests and subjects when campus, section, or subject changes (only if class is already selected)
    if (campusSelect) {
        campusSelect.addEventListener('change', function() {
            const classValue = classSelect ? classSelect.value : '';
            if (classValue) {
                loadTests();
                loadSubjects();
            }
        });
    }

    if (sectionSelect) {
        sectionSelect.addEventListener('change', function() {
            const classValue = classSelect ? classSelect.value : '';
            if (classValue) {
                loadTests();
                loadSubjects();
            }
        });
    }

    if (subjectSelect) {
        subjectSelect.addEventListener('change', function() {
            const classValue = classSelect ? classSelect.value : '';
            if (classValue) {
                loadTests();
            }
        });
    }

    function loadSections(selectedClass) {
        if (!sectionSelect) return;
        
        if (selectedClass) {
            sectionSelect.innerHTML = '<option value="">Loading...</option>';
            
            fetch(`{{ route('test.marks-entry.get-sections') }}?class=${encodeURIComponent(selectedClass)}`)
                .then(response => response.json())
                .then(data => {
                    sectionSelect.innerHTML = '<option value="">All Sections</option>';
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
                })
                .catch(error => {
                    console.error('Error loading sections:', error);
                    sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
                });
        } else {
            sectionSelect.innerHTML = '<option value="">All Sections</option>';
        }
    }

    function loadTests() {
        if (!testSelect) return;
        
        const campus = campusSelect ? campusSelect.value : '';
        const classValue = classSelect ? classSelect.value : '';
        const section = sectionSelect ? sectionSelect.value : '';
        const subject = subjectSelect ? subjectSelect.value : '';
        
        // Only load if class is selected (required)
        if (!classValue) {
            testSelect.disabled = true;
            testSelect.innerHTML = '<option value="">All Tests</option>';
            const testInfoMsg = document.getElementById('testInfoMsg');
            if (testInfoMsg) {
                testInfoMsg.style.display = 'none';
            }
            return;
        }
        
        testSelect.disabled = false;
        testSelect.innerHTML = '<option value="">Loading...</option>';
        
        const params = new URLSearchParams();
        if (campus) params.append('campus', campus);
        if (classValue) params.append('class', classValue);
        if (section) params.append('section', section);
        if (subject) params.append('subject', subject);
        
        fetch(`{{ route('test.marks-entry.get-tests') }}?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                testSelect.innerHTML = '<option value="">All Tests</option>';
                const testInfoMsg = document.getElementById('testInfoMsg');
                if (data.tests && data.tests.length > 0) {
                    data.tests.forEach(test => {
                        const option = document.createElement('option');
                        option.value = test;
                        option.textContent = test;
                        // Preserve selected test if it exists
                        @if($filterTest)
                        if (test === '{{ $filterTest }}') {
                            option.selected = true;
                        }
                        @endif
                        testSelect.appendChild(option);
                    });
                    if (testInfoMsg) {
                        testInfoMsg.style.display = 'none';
                    }
                } else {
                    // Show message if no tests found
                    if (testInfoMsg) {
                        testInfoMsg.style.display = 'block';
                    }
                }
            })
            .catch(error => {
                console.error('Error loading tests:', error);
                testSelect.innerHTML = '<option value="">Error loading tests</option>';
                const testInfoMsg = document.getElementById('testInfoMsg');
                if (testInfoMsg) {
                    testInfoMsg.style.display = 'none';
                }
            });
    }

    function loadSubjects() {
        if (!subjectSelect) return;
        
        const campus = campusSelect ? campusSelect.value : '';
        const classValue = classSelect ? classSelect.value : '';
        const section = sectionSelect ? sectionSelect.value : '';
        
        // Only load if class is selected (required)
        if (!classValue) {
            subjectSelect.disabled = true;
            subjectSelect.innerHTML = '<option value="">All Subjects</option>';
            return;
        }
        
        subjectSelect.disabled = false;
        subjectSelect.innerHTML = '<option value="">Loading...</option>';
        
        const params = new URLSearchParams();
        if (campus) params.append('campus', campus);
        if (classValue) params.append('class', classValue);
        if (section) params.append('section', section);
        
        fetch(`{{ route('test.marks-entry.get-subjects') }}?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                subjectSelect.innerHTML = '<option value="">All Subjects</option>';
                if (data.subjects && data.subjects.length > 0) {
                    data.subjects.forEach(subject => {
                        const option = document.createElement('option');
                        option.value = subject;
                        option.textContent = subject;
                        // Preserve selected subject if it exists
                        @if($filterSubject)
                        if (subject === '{{ $filterSubject }}') {
                            option.selected = true;
                        }
                        @endif
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
    if (initialClass) {
        loadSections(initialClass);
        // Enable and load subjects and tests if class is selected
        if (subjectSelect) {
            subjectSelect.disabled = false;
        }
        if (testSelect) {
            testSelect.disabled = false;
        }
        loadSubjects();
        loadTests();
    } else {
        // Disable subjects and tests if no class selected
        if (subjectSelect) {
            subjectSelect.disabled = true;
        }
        if (testSelect) {
            testSelect.disabled = true;
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
