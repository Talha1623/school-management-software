@extends('layouts.accountant')

@section('title', 'Generate Monthly Fee - Accountant')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3">
            <h3 class="mb-3 fw-semibold" style="color: #003471;">Generate Monthly Fee</h3>
            
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            
            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            
            <form action="{{ route('accountant.generate-monthly-fee.store') }}" method="POST" id="monthly-fee-form">
                @csrf
                
                <!-- First Row: Campus, Class, Section -->
                <div class="row mb-2">
                    <div class="col-md-4">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Campus</h5>
                            
                            <div class="mb-1">
                                <label for="campus" class="form-label mb-0 fs-13 fw-medium">Campus <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm py-1" id="campus" name="campus" required style="height: 32px;">
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus->campus_name ?? $campus }}">{{ $campus->campus_name ?? $campus }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Class</h5>
                            
                            <div class="mb-1">
                                <label for="class" class="form-label mb-0 fs-13 fw-medium">Class <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm py-1" id="class" name="class" required style="height: 32px;">
                                    <option value="">Select Class</option>
                                    @foreach($classes as $classItem)
                                        <option value="{{ $classItem->class_name ?? $classItem }}">{{ $classItem->class_name ?? $classItem }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Section</h5>
                            
                            <div class="mb-1">
                                <label for="section" class="form-label mb-0 fs-13 fw-medium">Section <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm py-1" id="section" name="section" required style="height: 32px;" disabled>
                                    <option value="">Select Class First</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Second Row: Fee Month, Fee Year -->
                <div class="row mb-2">
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Fee Month</h5>
                            
                            <div class="mb-1">
                                <label for="fee_month" class="form-label mb-0 fs-13 fw-medium">Fee Month <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm py-1" id="fee_month" name="fee_month" required style="height: 32px;">
                                    <option value="">Select Month</option>
                                    @foreach($months as $month)
                                        <option value="{{ $month }}" {{ $month == date('F') ? 'selected' : '' }}>{{ $month }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Fee Year</h5>
                            
                            <div class="mb-1">
                                <label for="fee_year" class="form-label mb-0 fs-13 fw-medium">Fee Year <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm py-1" id="fee_year" name="fee_year" required style="height: 32px;">
                                    <option value="">Select Year</option>
                                    @foreach($years as $year)
                                        <option value="{{ $year }}" {{ $year == $currentYear ? 'selected' : '' }}>{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Third Row: Due Date, Late Fee -->
                <div class="row mb-2">
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Due Date</h5>
                            
                            <div class="mb-1">
                                <label for="due_date" class="form-label mb-0 fs-13 fw-medium">Due Date <span class="text-danger">*</span></label>
                                <input type="date" class="form-control form-control-sm py-1" id="due_date" name="due_date" required style="height: 32px;">
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Late Fee</h5>
                            
                            <div class="mb-1">
                                <label for="late_fee" class="form-label mb-0 fs-13 fw-medium">Late Fee</label>
                                <input type="number" class="form-control form-control-sm py-1" id="late_fee" name="late_fee" placeholder="Enter late fee amount" step="0.01" min="0" style="height: 32px;">
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Student Selection Section -->
                <div class="row mb-3" id="student-selection-section" style="display: none;">
                    <div class="col-12">
                        <div class="card bg-light border-0 rounded-10 p-3">
                            <h5 class="mb-3 fw-semibold" style="color: #003471;">Student Selection</h5>
                            
                            <div class="mb-2 d-flex gap-2">
                                <button type="button" class="btn btn-sm btn-success" onclick="selectAllStudents()">
                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">check_box</span>
                                    Select All
                                </button>
                                <button type="button" class="btn btn-sm btn-danger" onclick="selectNoneStudents()">
                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">check_box_outline_blank</span>
                                    Select None
                                </button>
                            </div>
                            
                            <div class="table-responsive" style="max-height: 400px; overflow-y: auto;">
                                <table class="table table-sm table-hover">
                                    <thead style="position: sticky; top: 0; background-color: #f8f9fa; z-index: 10;">
                                        <tr>
                                            <th style="width: 50px;">
                                                <input type="checkbox" id="select-all-checkbox" onchange="toggleAllStudents(this)">
                                            </th>
                                            <th>Roll</th>
                                            <th style="color: #dc3545;">Student</th>
                                            <th>Parent</th>
                                            <th style="color: #28a745;">Status</th>
                                        </tr>
                                    </thead>
                                    <tbody id="students-list">
                                        <!-- Students will be loaded here dynamically -->
                                    </tbody>
                                </table>
                            </div>
                            
                            <div id="no-students-message" class="text-center text-muted py-3" style="display: none;">
                                No students found for the selected criteria.
                            </div>
                        </div>
                    </div>
        </div>

                <!-- Submit Button -->
                <div class="row mb-2">
                    <div class="col-12">
                        <div class="d-flex justify-content-end gap-2">
                            <button type="reset" class="btn btn-sm btn-secondary px-4 py-2" onclick="resetForm()">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">refresh</span>
                                Reset
                            </button>
                            <button type="submit" class="btn btn-sm px-4 py-2" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white; border: none;" id="generate-fee-btn">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">payments</span>
                                Generate Fee
                            </button>
                        </div>
                    </div>
            </div>
            </form>
        </div>
    </div>
</div>

<style>
    .card {
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        transition: all 0.3s ease;
    }
    
    .card:hover {
        box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    }
    
    .form-control:focus,
    .form-select:focus {
        border-color: #003471;
        box-shadow: 0 0 0 0.2rem rgba(0, 52, 113, 0.25);
    }
    
    .form-label {
        color: #495057;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const classSelect = document.getElementById('class');
    const sectionSelect = document.getElementById('section');
    const campusSelect = document.getElementById('campus');
    const feeMonthSelect = document.getElementById('fee_month');
    const feeYearSelect = document.getElementById('fee_year');
    
    // Function to load students
    function loadStudents() {
        const campus = campusSelect.value;
        const classValue = classSelect.value;
        const section = sectionSelect.value;
        const feeMonth = feeMonthSelect.value;
        const feeYear = feeYearSelect.value;
        
        const studentSection = document.getElementById('student-selection-section');
        const studentsList = document.getElementById('students-list');
        const noStudentsMessage = document.getElementById('no-students-message');
        
        // Hide student section if required fields are not filled
        if (!campus || !classValue || !section) {
            studentSection.style.display = 'none';
            studentsList.innerHTML = '';
            return;
        }
        
        // Show loading state
        studentsList.innerHTML = '<tr><td colspan="5" class="text-center">Loading students...</td></tr>';
        studentSection.style.display = 'block';
        noStudentsMessage.style.display = 'none';
        
        // Build query parameters
        const params = new URLSearchParams({
            campus: campus,
            class: classValue,
            section: section
        });
        
        if (feeMonth) {
            params.append('fee_month', feeMonth);
        }
        
        if (feeYear) {
            params.append('fee_year', feeYear);
        }
        
        // Fetch students
        fetch(`{{ route('accountant.get-students-with-fee-status') }}?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            studentsList.innerHTML = '';
            
            if (data.students && data.students.length > 0) {
                data.students.forEach(student => {
                    const row = document.createElement('tr');
                    const isGenerated = student.has_fee_generated;
                    const statusClass = isGenerated ? 'text-success' : 'text-warning';
                    const statusText = isGenerated ? 'Generated' : 'Ready';
                    
                    row.innerHTML = `
                        <td>
                            <input type="checkbox" 
                                   name="selected_students[]" 
                                   value="${student.id}" 
                                   class="student-checkbox"
                                   ${isGenerated ? 'disabled' : ''}
                                   data-student-id="${student.id}">
                        </td>
                        <td>${student.student_code || 'N/A'}</td>
                        <td style="color: #dc3545; font-weight: 500;">${student.student_name || 'N/A'}</td>
                        <td>${student.parent_name || 'N/A'}</td>
                        <td class="${statusClass}" style="font-weight: 500;">${statusText}</td>
                    `;
                    studentsList.appendChild(row);
                });
                
                // Update select all checkbox
                updateSelectAllCheckbox();
                noStudentsMessage.style.display = 'none';
            } else {
                studentsList.innerHTML = '';
                noStudentsMessage.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error fetching students:', error);
            studentsList.innerHTML = '<tr><td colspan="5" class="text-center text-danger">Error loading students</td></tr>';
        });
    }
    
    // Event listeners
    classSelect.addEventListener('change', function() {
        const selectedClass = this.value;
        
        // Reset section dropdown
        sectionSelect.innerHTML = '<option value="">Loading sections...</option>';
        sectionSelect.disabled = true;
        
        if (!selectedClass) {
            sectionSelect.innerHTML = '<option value="">Select Class First</option>';
            loadStudents();
            return;
        }
        
        // Fetch sections for selected class
        fetch(`{{ route('accountant.get-sections-by-class') }}?class=${encodeURIComponent(selectedClass)}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            sectionSelect.innerHTML = '<option value="">Select Section</option>';
            
            if (data.sections && data.sections.length > 0) {
                data.sections.forEach(section => {
                    const option = document.createElement('option');
                    option.value = section.name;
                    option.textContent = section.name;
                    sectionSelect.appendChild(option);
                });
                sectionSelect.disabled = false;
            } else {
                sectionSelect.innerHTML = '<option value="">No sections found</option>';
            }
            
            loadStudents();
        })
        .catch(error => {
            console.error('Error fetching sections:', error);
            sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
            loadStudents();
        });
    });
    
    sectionSelect.addEventListener('change', loadStudents);
    campusSelect.addEventListener('change', loadStudents);
    feeMonthSelect.addEventListener('change', loadStudents);
    feeYearSelect.addEventListener('change', loadStudents);
    
    // Load students on page load if all required fields are already filled
    setTimeout(function() {
        const campus = campusSelect ? campusSelect.value : '';
        const classValue = classSelect ? classSelect.value : '';
        const section = sectionSelect ? sectionSelect.value : '';
        
        if (campus && classValue && section) {
            loadStudents();
        }
    }, 500);
    
    // Also handle form reset event
    document.getElementById('monthly-fee-form').addEventListener('reset', function() {
        setTimeout(function() {
            sectionSelect.innerHTML = '<option value="">Select Class First</option>';
            sectionSelect.disabled = true;
            document.getElementById('student-selection-section').style.display = 'none';
            document.getElementById('students-list').innerHTML = '';
        }, 10);
    });
});

// Global functions
function resetForm() {
    const sectionSelect = document.getElementById('section');
    if (sectionSelect) {
        sectionSelect.innerHTML = '<option value="">Select Class First</option>';
        sectionSelect.disabled = true;
    }
    document.getElementById('student-selection-section').style.display = 'none';
    document.getElementById('students-list').innerHTML = '';
    document.getElementById('monthly-fee-form').reset();
}

function selectAllStudents() {
    const checkboxes = document.querySelectorAll('.student-checkbox:not(:disabled)');
    checkboxes.forEach(checkbox => {
        checkbox.checked = true;
    });
    updateSelectAllCheckbox();
}

function selectNoneStudents() {
    const checkboxes = document.querySelectorAll('.student-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
    updateSelectAllCheckbox();
}

function toggleAllStudents(checkbox) {
    const checkboxes = document.querySelectorAll('.student-checkbox:not(:disabled)');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
}

function updateSelectAllCheckbox() {
    const checkboxes = document.querySelectorAll('.student-checkbox:not(:disabled)');
    const checkedBoxes = document.querySelectorAll('.student-checkbox:not(:disabled):checked');
    const selectAllCheckbox = document.getElementById('select-all-checkbox');
    
    if (selectAllCheckbox) {
        if (checkboxes.length === 0) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        } else if (checkedBoxes.length === checkboxes.length) {
            selectAllCheckbox.checked = true;
            selectAllCheckbox.indeterminate = false;
        } else if (checkedBoxes.length > 0) {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = true;
        } else {
            selectAllCheckbox.checked = false;
            selectAllCheckbox.indeterminate = false;
        }
    }
}

// Update select all checkbox when individual checkboxes change
document.addEventListener('change', function(e) {
    if (e.target.classList.contains('student-checkbox')) {
        updateSelectAllCheckbox();
    }
});
</script>
@endsection
