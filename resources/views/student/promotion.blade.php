@extends('layouts.app')

@section('title', 'Student Promotion')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Student Promotion</h4>
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
            <form method="GET" action="{{ route('student.promotion') }}" class="mb-3" id="filterForm">
                <div class="row g-2 align-items-end">
                    <div class="col-md-2">
                        <label for="campus" class="form-label mb-0 fs-13 fw-medium">Campus</label>
                        <select class="form-select form-select-sm" id="campus" name="campus" style="height: 32px;">
                            <option value="">All Campuses</option>
                            @foreach($campuses as $campus)
                                <option value="{{ $campus }}" {{ request('campus') == $campus ? 'selected' : '' }}>{{ $campus }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="from_class" class="form-label mb-0 fs-13 fw-medium">Promotion From Class <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" id="from_class" name="from_class" style="height: 32px;" onchange="loadSectionsForFilter(this.value)" {{ request('campus') ? '' : 'disabled' }}>
                            <option value="">{{ request('campus') ? 'Select Class' : 'Select Campus First' }}</option>
                            @foreach($classes as $class)
                                <option value="{{ $class }}" {{ request('from_class') == $class ? 'selected' : '' }}>{{ $class }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="from_section" class="form-label mb-0 fs-13 fw-medium">Section</label>
                        <select class="form-select form-select-sm" id="from_section" name="from_section" style="height: 32px;">
                            <option value="">All Sections</option>
                            @foreach($sections as $section)
                                <option value="{{ $section }}" {{ request('from_section') == $section ? 'selected' : '' }}>{{ $section }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="to_class" class="form-label mb-0 fs-13 fw-medium">Promotion To Class <span class="text-danger">*</span></label>
                        <select class="form-select form-select-sm" id="to_class" name="to_class" style="height: 32px;" onchange="loadSectionsForToClass(this.value)" {{ request('campus') ? '' : 'disabled' }}>
                            <option value="">{{ request('campus') ? 'Select Class' : 'Select Campus First' }}</option>
                            @foreach($classes as $class)
                                <option value="{{ $class }}" {{ request('to_class') == $class ? 'selected' : '' }}>{{ $class }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <label for="to_section" class="form-label mb-0 fs-13 fw-medium">Section</label>
                        <select class="form-select form-select-sm" id="to_section" name="to_section" style="height: 32px;">
                            <option value="">All Sections</option>
                            @foreach($sections as $section)
                                <option value="{{ $section }}" {{ request('to_section') == $section ? 'selected' : '' }}>{{ $section }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm w-100" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white; height: 32px; border: none;">
                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">filter_list</span>
                            Filter
                        </button>
                    </div>
                    @if(request('campus') || request('from_class') || request('from_section'))
                    <div class="col-md-2">
                        <a href="{{ route('student.promotion') }}" class="btn btn-sm btn-outline-secondary w-100" style="height: 32px;">
                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">clear</span>
                            Clear
                        </a>
                    </div>
                    @endif
                </div>
            </form>

            @if($hasFilters && isset($students))
            <!-- Table Toolbar -->
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-3 p-3 rounded-8" style="background-color: #f8f9fa; border: 1px solid #e9ecef;">
                <!-- Left Side -->
                <div class="d-flex align-items-center gap-3 flex-wrap">
                    <div class="d-flex align-items-center gap-2">
                        <label for="entriesPerPage" class="mb-0 fs-13 fw-medium text-dark">Show:</label>
                        <select id="entriesPerPage" class="form-select form-select-sm" style="width: auto; min-width: 70px;" onchange="updateEntriesPerPage(this.value)">
                            <option value="10" {{ request('per_page', 10) == 10 ? 'selected' : '' }}>10</option>
                            <option value="25" {{ request('per_page', 25) == 25 ? 'selected' : '' }}>25</option>
                            <option value="50" {{ request('per_page', 50) == 50 ? 'selected' : '' }}>50</option>
                            <option value="100" {{ request('per_page', 100) == 100 ? 'selected' : '' }}>100</option>
                        </select>
                    </div>
                    <h5 class="mb-0 fs-14 fw-semibold text-dark">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; color: #003471;">info</span>
                        {{ $students->total() }} {{ Str::plural('student', $students->total()) }} found
                    </h5>
                </div>
            </div>

            <!-- Students Table -->
            <div class="default-table-area" style="margin-top: 0;">
                <div class="table-responsive" style="max-height: none; overflow: visible; overflow-x: auto;">
                    <table class="table table-sm table-hover" style="margin-bottom: 0; white-space: nowrap;">
                        <thead>
                            <tr>
                                <th style="padding: 8px 12px; font-size: 13px; text-align: center;">
                                    <input type="checkbox" id="select_all_students" style="transform: scale(1.1);">
                                </th>
                                <th style="padding: 8px 12px; font-size: 13px;">#</th>
                                <th style="padding: 8px 12px; font-size: 13px;">Student Name</th>
                                <th style="padding: 8px 12px; font-size: 13px;">Student Code</th>
                                <th style="padding: 8px 12px; font-size: 13px;">Father Name</th>
                                <th style="padding: 8px 12px; font-size: 13px;">Phone</th>
                                <th style="padding: 8px 12px; font-size: 13px;">Class</th>
                                <th style="padding: 8px 12px; font-size: 13px;">Section</th>
                                <th style="padding: 8px 12px; font-size: 13px;">Gender</th>
                                <th style="padding: 8px 12px; font-size: 13px;">Date of Birth</th>
                                <th style="padding: 8px 12px; font-size: 13px;">Admission Date</th>
                                <th style="padding: 8px 12px; font-size: 13px; text-align: center;">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $student)
                                <tr>
                                    <td style="padding: 8px 12px; font-size: 13px; text-align: center;">
                                        <input type="checkbox" class="student-checkbox" value="{{ $student->id }}" style="transform: scale(1.1);">
                                    </td>
                                    <td style="padding: 8px 12px; font-size: 13px;">{{ $loop->iteration + (($students->currentPage() - 1) * $students->perPage()) }}</td>
                                    <td style="padding: 8px 12px; font-size: 13px;">
                                        <strong class="text-primary">{{ $student->student_name }}</strong>
                                        @if($student->surname_caste)
                                            <span class="text-muted">({{ $student->surname_caste }})</span>
                                        @endif
                                    </td>
                                    <td style="padding: 8px 12px; font-size: 13px;">
                                        @if($student->student_code)
                                            <span class="badge bg-info text-white" style="font-size: 11px;">{{ $student->student_code }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td style="padding: 8px 12px; font-size: 13px;">{{ $student->father_name ?? 'N/A' }}</td>
                                    <td style="padding: 8px 12px; font-size: 13px;">
                                        <span class="badge bg-light text-dark" style="font-size: 11px;">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">phone</span>
                                            {{ $student->father_phone ?? $student->whatsapp_number ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td style="padding: 8px 12px; font-size: 13px;">
                                        <span class="badge bg-primary text-white" style="font-size: 11px;">{{ $student->class ?? 'N/A' }}</span>
                                    </td>
                                    <td style="padding: 8px 12px; font-size: 13px;">
                                        @if($student->section)
                                            <span class="badge bg-secondary text-white" style="font-size: 11px;">{{ $student->section }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td style="padding: 8px 12px; font-size: 13px;">
                                        @php
                                            $genderClass = match($student->gender) {
                                                'male' => 'bg-info',
                                                'female' => 'bg-danger',
                                                default => 'bg-secondary'
                                            };
                                        @endphp
                                        <span class="badge {{ $genderClass }} text-white text-capitalize" style="font-size: 11px;">
                                            {{ $student->gender ?? 'N/A' }}
                                        </span>
                                    </td>
                                    <td style="padding: 8px 12px; font-size: 13px;">
                                        <span class="text-muted">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">calendar_today</span>
                                            {{ $student->date_of_birth ? $student->date_of_birth->format('d M Y') : 'N/A' }}
                                        </span>
                                    </td>
                                    <td style="padding: 8px 12px; font-size: 13px;">
                                        <span class="text-muted">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">event</span>
                                            {{ $student->admission_date ? $student->admission_date->format('d M Y') : 'N/A' }}
                                        </span>
                                    </td>
                                    <td style="padding: 8px 12px; font-size: 13px; text-align: center;">
                                        <button type="button" class="btn btn-sm btn-primary px-2 py-1" onclick="viewStudent({{ $student->id }})" title="View Details">
                                            <span class="material-symbols-outlined" style="font-size: 14px; color: white;">visibility</span>
                                        </button>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="12" class="text-center text-muted py-5">
                                        <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">school</span>
                                        <p class="mt-2 mb-0">No students found.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($students->hasPages())
                <div class="mt-3">
                    {{ $students->links() }}
                </div>
            @endif

            <!-- Promotion Form -->
            @if($hasFilters && $students->count() > 0)
            <div class="card border-0 shadow-sm mt-4" style="border-radius: 8px; overflow: hidden;">
                <div class="card-body p-4">
                    <h5 class="mb-3 fw-semibold" style="color: #003471;">
                        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">trending_up</span>
                        Promote Selected Students
                    </h5>
                    <form action="{{ route('student.promotion.promote') }}" method="POST" id="promotionForm">
                        @csrf
                        <input type="hidden" name="campus" value="{{ request('campus') }}">
                        <input type="hidden" name="from_class" value="{{ request('from_class') }}">
                        <input type="hidden" name="from_section" value="{{ request('from_section') }}">
                        <div class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Promotion To Class <span class="text-danger">*</span>
                                </label>
                                <select class="form-select form-select-sm" name="to_class" id="promotion_to_class" required style="height: 32px;" onchange="loadSectionsForPromotionForm(this.value)" {{ request('campus') ? '' : 'disabled' }}>
                                    <option value="">{{ request('campus') ? 'Select Class' : 'Select Campus First' }}</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class }}" {{ request('to_class') == $class ? 'selected' : '' }}>{{ $class }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Section
                                </label>
                                <select class="form-select form-select-sm" name="to_section" id="promotion_to_section" style="height: 32px;">
                                    <option value="">All Sections</option>
                                    @foreach($sections as $section)
                                        <option value="{{ $section }}" {{ request('to_section') == $section ? 'selected' : '' }}>{{ $section }}</option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-md-4">
                                <button type="submit" class="btn btn-primary w-100 promotion-btn" style="height: 32px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">trending_up</span>
                                    <span style="font-size: 12px;">Promote Students</span>
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
            @endif
            @else
            <!-- Message when no filters applied -->
            <div class="text-center py-5">
                <span class="material-symbols-outlined" style="font-size: 64px; color: #dee2e6; opacity: 0.5;">filter_list</span>
                <h5 class="mt-3 text-muted">Apply Filters to View Students</h5>
                <p class="text-muted mb-0">Please select Campus, Class, or Section and click Filter to view students list.</p>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
// Update entries per page
function updateEntriesPerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

// Load sections based on class selection for "From Class"
function loadSectionsForFilter(selectedClass) {
    const sectionSelect = document.getElementById('from_section');
    
    if (!selectedClass) {
        sectionSelect.innerHTML = '<option value="">All Sections</option>';
        sectionSelect.disabled = false;
        return;
    }
    
    // Clear existing options
    sectionSelect.innerHTML = '<option value="">Loading...</option>';
    
    // Fetch sections for the selected class
    const params = new URLSearchParams();
    params.append('class', selectedClass);
    const campusValue = document.getElementById('campus')?.value;
    if (campusValue) {
        params.append('campus', campusValue);
    }
    fetch(`{{ route('student.promotion.get-sections') }}?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            sectionSelect.innerHTML = '<option value="">All Sections</option>';
            
            if (data.sections && data.sections.length > 0) {
                data.sections.forEach(section => {
                    const option = document.createElement('option');
                    option.value = section;
                    option.textContent = section;
                    // Preserve selected value if it matches
                    const selectedSection = '{{ request('from_section') }}';
                    if (selectedSection && selectedSection === section) {
                        option.selected = true;
                    }
                    sectionSelect.appendChild(option);
                });
            }
            sectionSelect.disabled = false;
        })
        .catch(error => {
            console.error('Error loading sections:', error);
            sectionSelect.innerHTML = '<option value="">All Sections</option>';
            sectionSelect.disabled = false;
        });
}

// Load sections based on class selection for "To Class"
function loadSectionsForToClass(selectedClass) {
    const sectionSelect = document.getElementById('to_section');
    
    if (!selectedClass) {
        sectionSelect.innerHTML = '<option value="">All Sections</option>';
        sectionSelect.disabled = false;
        return;
    }
    
    // Clear existing options
    sectionSelect.innerHTML = '<option value="">Loading...</option>';
    
    // Fetch sections for the selected class
    const params = new URLSearchParams();
    params.append('class', selectedClass);
    const campusValue = document.getElementById('campus')?.value;
    if (campusValue) {
        params.append('campus', campusValue);
    }
    fetch(`{{ route('student.promotion.get-sections') }}?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            sectionSelect.innerHTML = '<option value="">All Sections</option>';
            
            if (data.sections && data.sections.length > 0) {
                data.sections.forEach(section => {
                    const option = document.createElement('option');
                    option.value = section;
                    option.textContent = section;
                    // Preserve selected value if it matches
                    const selectedSection = '{{ request('to_section') }}';
                    if (selectedSection && selectedSection === section) {
                        option.selected = true;
                    }
                    sectionSelect.appendChild(option);
                });
            }
            sectionSelect.disabled = false;
        })
        .catch(error => {
            console.error('Error loading sections:', error);
            sectionSelect.innerHTML = '<option value="">All Sections</option>';
            sectionSelect.disabled = false;
        });
}

// View student details
function viewStudent(studentId) {
    window.location.href = '{{ route("student.view", ":id") }}'.replace(':id', studentId);
}

// Handle select all
const selectAllCheckbox = document.getElementById('select_all_students');
const studentCheckboxes = () => Array.from(document.querySelectorAll('.student-checkbox'));

selectAllCheckbox?.addEventListener('change', function() {
    studentCheckboxes().forEach(cb => {
        cb.checked = selectAllCheckbox.checked;
    });
});

// Keep select-all in sync when individual checkbox changes
document.addEventListener('change', function(e) {
    if (!e.target.classList.contains('student-checkbox')) {
        return;
    }
    const checkboxes = studentCheckboxes();
    const allChecked = checkboxes.length > 0 && checkboxes.every(cb => cb.checked);
    if (selectAllCheckbox) {
        selectAllCheckbox.checked = allChecked;
    }
});

// Form validation for promotion + attach selected students
document.getElementById('promotionForm')?.addEventListener('submit', function(e) {
    const fromClass = '{{ request('from_class') }}';
    const toClass = document.getElementById('promotion_to_class').value;
    
    if (!toClass) {
        e.preventDefault();
        alert('Please select Promotion To Class');
        return false;
    }
    
    if (fromClass === toClass) {
        e.preventDefault();
        alert('Promotion From Class and Promotion To Class cannot be the same');
        return false;
    }

    const selectedStudents = studentCheckboxes().filter(cb => cb.checked).map(cb => cb.value);
    if (selectedStudents.length === 0) {
        e.preventDefault();
        alert('Please select at least one student to promote');
        return false;
    }

    // Remove any previous hidden inputs
    document.querySelectorAll('#promotionForm input[name="student_ids[]"]').forEach(el => el.remove());
    
    // Add selected student ids
    selectedStudents.forEach(id => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'student_ids[]';
        input.value = id;
        document.getElementById('promotionForm').appendChild(input);
    });
    
    if (!confirm('Are you sure you want to promote ' + selectedStudents.length + ' student(s)? This action cannot be undone.')) {
        e.preventDefault();
        return false;
    }
});

// Load sections for promotion form (below table)
function loadSectionsForPromotionForm(selectedClass) {
    const sectionSelect = document.getElementById('promotion_to_section');
    
    if (!selectedClass) {
        sectionSelect.innerHTML = '<option value="">All Sections</option>';
        sectionSelect.disabled = false;
        return;
    }
    
    // Clear existing options
    sectionSelect.innerHTML = '<option value="">Loading...</option>';
    
    // Fetch sections for the selected class
    const params = new URLSearchParams();
    params.append('class', selectedClass);
    const campusValue = document.getElementById('campus')?.value;
    if (campusValue) {
        params.append('campus', campusValue);
    }
    fetch(`{{ route('student.promotion.get-sections') }}?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            sectionSelect.innerHTML = '<option value="">All Sections</option>';
            
            if (data.sections && data.sections.length > 0) {
                data.sections.forEach(section => {
                    const option = document.createElement('option');
                    option.value = section;
                    option.textContent = section;
                    sectionSelect.appendChild(option);
                });
            }
            sectionSelect.disabled = false;
        })
        .catch(error => {
            console.error('Error loading sections:', error);
            sectionSelect.innerHTML = '<option value="">All Sections</option>';
            sectionSelect.disabled = false;
        });
}

// Load sections on page load if class is already selected
document.addEventListener('DOMContentLoaded', function() {
    const campusSelect = document.getElementById('campus');
    const fromClassSelect = document.getElementById('from_class');
    const toClassSelect = document.getElementById('to_class');
    const promotionToClassSelect = document.getElementById('promotion_to_class');

    const selectedFromClass = '{{ request('from_class') }}';
    const selectedToClass = '{{ request('to_class') }}';
    const selectedPromotionToClass = document.getElementById('promotion_to_class')?.value;

    function setSelectDisabled(selectEl, placeholder) {
        if (!selectEl) return;
        selectEl.innerHTML = `<option value="">${placeholder}</option>`;
        selectEl.disabled = true;
    }

    function populateClassSelect(selectEl, classes, selectedValue) {
        if (!selectEl) return;
        selectEl.innerHTML = '<option value="">Select Class</option>';
        classes.forEach(className => {
            const option = document.createElement('option');
            option.value = className;
            option.textContent = className;
            if (selectedValue && selectedValue === className) {
                option.selected = true;
            }
            selectEl.appendChild(option);
        });
        selectEl.disabled = classes.length === 0;
    }

    function loadClassesForCampus(campusValue) {
        if (!campusValue) {
            setSelectDisabled(fromClassSelect, 'Select Campus First');
            setSelectDisabled(toClassSelect, 'Select Campus First');
            setSelectDisabled(promotionToClassSelect, 'Select Campus First');
            setSelectDisabled(document.getElementById('from_section'), 'All Sections');
            setSelectDisabled(document.getElementById('to_section'), 'All Sections');
            setSelectDisabled(document.getElementById('promotion_to_section'), 'All Sections');
            return Promise.resolve();
        }

        return fetch(`{{ route('student.promotion.get-classes-by-campus') }}?campus=${encodeURIComponent(campusValue)}`)
            .then(response => response.json())
            .then(data => {
                const classes = Array.isArray(data.classes) ? data.classes : [];
                populateClassSelect(fromClassSelect, classes, selectedFromClass);
                populateClassSelect(toClassSelect, classes, selectedToClass);
                populateClassSelect(promotionToClassSelect, classes, selectedPromotionToClass);
            })
            .catch(error => {
                console.error('Error loading classes:', error);
                setSelectDisabled(fromClassSelect, 'Error loading classes');
                setSelectDisabled(toClassSelect, 'Error loading classes');
                setSelectDisabled(promotionToClassSelect, 'Error loading classes');
            });
    }

    loadClassesForCampus(campusSelect?.value).then(() => {
        if (selectedFromClass) {
            loadSectionsForFilter(selectedFromClass);
        }
        if (selectedToClass) {
            loadSectionsForToClass(selectedToClass);
        }
        if (selectedPromotionToClass) {
            loadSectionsForPromotionForm(selectedPromotionToClass);
        }
    });

    campusSelect?.addEventListener('change', function() {
        loadClassesForCampus(this.value).then(() => {
            const fromSection = document.getElementById('from_section');
            const toSection = document.getElementById('to_section');
            const promotionToSection = document.getElementById('promotion_to_section');
            if (fromSection) fromSection.innerHTML = '<option value="">All Sections</option>';
            if (toSection) toSection.innerHTML = '<option value="">All Sections</option>';
            if (promotionToSection) promotionToSection.innerHTML = '<option value="">All Sections</option>';
        });
    });

});
</script>

<style>
    .form-select-sm {
        font-size: 13px;
        padding: 6px 12px;
        border-radius: 6px;
        border: 1px solid #dee2e6;
        transition: all 0.3s ease;
    }
    
    .form-select-sm:focus {
        border-color: #003471;
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
        outline: none;
    }
    
    .promotion-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        padding: 4px 12px;
        font-size: 12px;
        transition: all 0.3s ease;
        box-shadow: 0 2px 6px rgba(0, 52, 113, 0.2);
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
    }
    
    .promotion-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 52, 113, 0.3);
        color: white;
    }
    
    .promotion-btn:active {
        transform: translateY(0);
    }
</style>
@endsection
