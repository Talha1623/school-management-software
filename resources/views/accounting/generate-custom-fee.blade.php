@extends('layouts.app')

@section('title', 'Generate Custom Fee')

@section('content')
@php
    $months = $months ?? [
        'January', 'February', 'March', 'April', 'May', 'June',
        'July', 'August', 'September', 'October', 'November', 'December',
    ];
    $currentYear = $currentYear ?? (int) date('Y');
    $years = $years ?? range($currentYear - 2, $currentYear + 5);
@endphp
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3">
            <h3 class="mb-3 fw-semibold" style="color: #003471;">Generate Custom Fee</h3>
            
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
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
            
            <form action="{{ route('accounting.generate-custom-fee.store') }}" method="POST" id="custom-fee-form">
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
                                        <option value="{{ $campus->campus_name ?? $campus }}" @selected(old('campus') === (string)($campus->campus_name ?? $campus))>{{ $campus->campus_name ?? $campus }}</option>
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
                                <select class="form-select form-select-sm py-1" id="class" name="class" required style="height: 32px;" disabled>
                                    <option value="">Select Campus first</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-4">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Section</h5>
                            
                            <div class="mb-1">
                                <label for="section" class="form-label mb-0 fs-13 fw-medium">Section (Optional)</label>
                                <select class="form-select form-select-sm py-1" id="section" name="section" style="height: 32px;" disabled>
                                    <option value="">Select Class First</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Fee Month & Year -->
                <div class="row mb-2">
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Fee Month</h5>
                            <div class="mb-1">
                                <label for="fee_month" class="form-label mb-0 fs-13 fw-medium">Fee Month <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm py-1" id="fee_month" name="fee_month" required style="height: 32px;">
                                    <option value="">Select Month</option>
                                    @foreach($months as $month)
                                        <option value="{{ $month }}" @selected(old('fee_month', date('F')) === $month)>{{ $month }}</option>
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
                                        <option value="{{ $year }}" @selected((string) old('fee_year', $currentYear) === (string) $year)>{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Second Row: Fee Type, Amount -->
                <div class="row mb-2">
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Fee Type</h5>
                            
                            <div class="mb-1">
                                <label for="fee_type" class="form-label mb-0 fs-13 fw-medium">Fee Type <span class="text-danger">*</span></label>
                                <select class="form-select form-select-sm py-1" id="fee_type" name="fee_type" required style="height: 32px;">
                                    <option value="">Select Campus first</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-2 mb-2">
                            <h5 class="mb-1 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -8px -8px 8px -8px; background-color: #003471;">Amount</h5>
                            
                            <div class="mb-1">
                                <label for="amount" class="form-label mb-0 fs-13 fw-medium">Amount <span class="text-danger">*</span></label>
                                <input type="number" class="form-control form-control-sm py-1" id="amount" name="amount" value="{{ old('amount') }}" placeholder="Enter amount" step="0.01" min="0" required style="height: 32px;">
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
const customFeeRestoreCampus = @json(old('campus'));
const customFeeOldFeeType = @json(old('fee_type'));
const customFeeOldClass = @json(old('class'));

document.addEventListener('DOMContentLoaded', function() {
    const classSelect = document.getElementById('class');
    const sectionSelect = document.getElementById('section');
    const campusSelect = document.getElementById('campus');

    function resetClassAndSection() {
        if (classSelect) {
            classSelect.innerHTML = '<option value="">Select Campus First</option>';
            classSelect.disabled = true;
        }
        if (sectionSelect) {
            sectionSelect.innerHTML = '<option value="">Select Class First</option>';
            sectionSelect.disabled = true;
        }
    }

    // Fee types from Fee Type master: this campus + global (null/blank campus) heads
    function loadFeeTypesByCampus(campus) {
        const feeTypeSelect = document.getElementById('fee_type');
        if (!feeTypeSelect) {
            return;
        }

        if (!campus || campus === '') {
            feeTypeSelect.innerHTML = '<option value="">Select Campus first</option>';
            const amountEl = document.getElementById('amount');
            if (amountEl) {
                amountEl.value = '';
            }
            return;
        }

        feeTypeSelect.innerHTML = '<option value="">Select Fee Type</option>';
        const amountClear = document.getElementById('amount');
        if (amountClear && String(campus).trim() !== String(customFeeRestoreCampus || '').trim()) {
            amountClear.value = '';
        }

        fetch(`{{ route('accounting.custom-fee.get-fee-types-by-campus') }}?campus=${encodeURIComponent(campus)}&master_only=1`)
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => {
                const names = (data.fee_types && data.fee_types.length > 0) ? data.fee_types : [];
                names.forEach(feeType => {
                    const option = document.createElement('option');
                    option.value = feeType;
                    option.textContent = feeType;
                    feeTypeSelect.appendChild(option);
                });

                const reuseOld = customFeeRestoreCampus && customFeeOldFeeType
                    && String(customFeeRestoreCampus).trim() === String(campus).trim();
                if (reuseOld && String(customFeeOldFeeType).trim() !== '') {
                    const lowerOld = String(customFeeOldFeeType).toLowerCase().trim();
                    const hasOld = names.some(function (ft) {
                        return String(ft).toLowerCase().trim() === lowerOld;
                    });
                    if (!hasOld) {
                        const option = document.createElement('option');
                        option.value = customFeeOldFeeType;
                        option.textContent = customFeeOldFeeType;
                        feeTypeSelect.appendChild(option);
                    }
                    feeTypeSelect.value = customFeeOldFeeType;
                }
            })
            .catch(error => {
                console.error('Error loading fee types:', error);
            });
    }
    
    function loadClassesForCampus(opts) {
        opts = opts || {};
        const fromCampusPicker = opts.fromCampusPicker !== false;
        const campus = campusSelect ? campusSelect.value : '';

        if (fromCampusPicker) {
            resetClassAndSection();
        } else if (sectionSelect) {
            sectionSelect.innerHTML = '<option value="">Select Class First</option>';
            sectionSelect.disabled = true;
        }
        loadStudents();

        if (!campus || !classSelect) {
            return;
        }

        classSelect.innerHTML = '<option value="">Loading classes...</option>';
        classSelect.disabled = false;

        fetch(`{{ route('accounting.custom-fee.get-classes-by-campus') }}?campus=${encodeURIComponent(campus)}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            classSelect.innerHTML = '<option value="">Select Class</option>';

            if (data.classes && data.classes.length > 0) {
                data.classes.forEach(className => {
                    const option = document.createElement('option');
                    option.value = className;
                    option.textContent = className;
                    classSelect.appendChild(option);
                });
                classSelect.disabled = false;
                if (!fromCampusPicker && customFeeOldClass) {
                    classSelect.value = customFeeOldClass;
                    if (classSelect.value === customFeeOldClass) {
                        classSelect.dispatchEvent(new Event('change'));
                    }
                }
            } else {
                classSelect.innerHTML = '<option value="">No classes found</option>';
            }
        })
        .catch(error => {
            console.error('Error fetching classes:', error);
            classSelect.innerHTML = '<option value="">Error loading classes</option>';
        });
    }
    
    // Function to load students
    function loadStudents() {
        const campus = campusSelect ? campusSelect.value : '';
        const classValue = classSelect ? classSelect.value : '';
        const section = sectionSelect ? sectionSelect.value : '';
        
        const studentSection = document.getElementById('student-selection-section');
        const studentsList = document.getElementById('students-list');
        const noStudentsMessage = document.getElementById('no-students-message');
        
        // Check if elements exist
        if (!studentSection || !studentsList) {
            console.error('Student section elements not found');
            return;
        }
        
        // Hide student section if required fields are not filled
        if (!campus || !classValue) {
            if (studentSection) studentSection.style.display = 'none';
            if (studentsList) studentsList.innerHTML = '';
            return;
        }
        
        // Show loading state
        studentsList.innerHTML = '<tr><td colspan="4" class="text-center">Loading students...</td></tr>';
        studentSection.style.display = 'block';
        if (noStudentsMessage) noStudentsMessage.style.display = 'none';
        
        // Build query parameters
        const params = new URLSearchParams({
            campus: campus,
            class: classValue
        });
        if (section) {
            params.append('section', section);
        }
        
        // Fetch students
        const url = `{{ route('accounting.custom-fee.get-students') }}?${params.toString()}`;
        console.log('Fetching students from:', url);
        
        fetch(url, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            console.log('Students data received:', data);
            studentsList.innerHTML = '';
            
            if (data.students && data.students.length > 0) {
                data.students.forEach(student => {
                    const row = document.createElement('tr');
                    
                    row.innerHTML = `
                        <td>
                            <input type="checkbox" 
                                   name="selected_students[]" 
                                   value="${student.id}" 
                                   class="student-checkbox"
                                   data-student-id="${student.id}">
                        </td>
                        <td>${student.student_code || 'N/A'}</td>
                        <td style="color: #dc3545; font-weight: 500;">${student.student_name || 'N/A'}</td>
                        <td>${student.parent_name || 'N/A'}</td>
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
            studentsList.innerHTML = '<tr><td colspan="4" class="text-center text-danger">Error loading students</td></tr>';
        });
    }
    
    // Initialize: no campus → reset; campus pre-filled (e.g. validation) → load lists without wiping campus
    if (campusSelect && !campusSelect.value) {
        resetClassAndSection();
    } else if (campusSelect && campusSelect.value) {
        loadFeeTypesByCampus(campusSelect.value);
        loadClassesForCampus({ fromCampusPicker: false });
    }

    // Event listeners
    if (classSelect) {
        classSelect.addEventListener('change', function() {
            const selectedClass = this.value;
            
            if (!sectionSelect) return;
            
            // Reset section dropdown
            sectionSelect.innerHTML = '<option value="">Loading sections...</option>';
            sectionSelect.disabled = true;
            
            if (!selectedClass) {
                sectionSelect.innerHTML = '<option value="">Select Class First</option>';
                const amtEl = document.getElementById('amount');
                if (amtEl) {
                    amtEl.value = '';
                }
                loadStudents();
                return;
            }
        
        // Fetch sections for selected class
        const campus = campusSelect ? campusSelect.value : '';
        fetch(`{{ route('accounting.custom-fee.get-sections-by-class') }}?class=${encodeURIComponent(selectedClass)}&campus=${encodeURIComponent(campus)}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.json();
        })
        .then(data => {
            sectionSelect.innerHTML = '<option value="">All Sections</option>';
            
            if (data.sections && data.sections.length > 0) {
                data.sections.forEach(section => {
                    const option = document.createElement('option');
                    option.value = section.name;
                    option.textContent = section.name;
                    sectionSelect.appendChild(option);
                });
                sectionSelect.disabled = false;
            } else {
                sectionSelect.innerHTML = '<option value="">All Sections</option>';
                sectionSelect.disabled = false;
            }
            
            loadStudents();
        })
        .catch(error => {
            console.error('Error fetching sections:', error);
            if (sectionSelect) {
                sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
            }
            loadStudents();
        });
        });
    }
    
    if (sectionSelect) {
        sectionSelect.addEventListener('change', function () {
            loadStudents();
        });
    }

    if (campusSelect) {
        campusSelect.addEventListener('change', function() {
            loadClassesForCampus({ fromCampusPicker: true });
            loadFeeTypesByCampus(this.value);
        });
    }
    
    // Load students on page load if all fields are already filled
    setTimeout(function() {
        const campus = campusSelect ? campusSelect.value : '';
        const classValue = classSelect ? classSelect.value : '';
        const section = sectionSelect ? sectionSelect.value : '';
        
        if (campus && classValue) {
            loadStudents();
        }
    }, 500);
    
    // Also handle form reset event
    document.getElementById('custom-fee-form').addEventListener('reset', function() {
        setTimeout(function() {
            resetClassAndSection();
            document.getElementById('student-selection-section').style.display = 'none';
            document.getElementById('students-list').innerHTML = '';
        }, 10);
    });
});

// Global functions
function resetForm() {
    const sectionSelect = document.getElementById('section');
    const classSelect = document.getElementById('class');
    if (sectionSelect) {
        sectionSelect.innerHTML = '<option value="">Select Class First</option>';
        sectionSelect.disabled = true;
    }
    if (classSelect) {
        classSelect.innerHTML = '<option value="">Select Campus First</option>';
        classSelect.disabled = true;
    }
    document.getElementById('student-selection-section').style.display = 'none';
    document.getElementById('students-list').innerHTML = '';
    document.getElementById('custom-fee-form').reset();
}

function selectAllStudents() {
    const checkboxes = document.querySelectorAll('.student-checkbox');
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
    const checkboxes = document.querySelectorAll('.student-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = checkbox.checked;
    });
}

function updateSelectAllCheckbox() {
    const checkboxes = document.querySelectorAll('.student-checkbox');
    const checkedBoxes = document.querySelectorAll('.student-checkbox:checked');
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
