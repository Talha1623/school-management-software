@extends('layouts.app')

@section('title', 'Increment By Percentage')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3">
            <h3 class="mb-3 fw-semibold" style="color: #003471;">Increment By Percentage</h3>
            
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

            <form id="feeIncrementPercentageForm" method="POST" action="{{ route('accounting.fee-increment.percentage.store') }}">
                @csrf
                
                <div class="payment-row mb-3 p-3 border rounded" style="background-color: #f8f9fa;">
                    <div class="row g-3">
                        <!-- Campus -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-13 fw-medium">Campus</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">home</span>
                                </span>
                                <select class="form-select form-select-sm" name="campus" id="campus" style="height: 38px;">
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus }}">{{ $campus }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>

                        <!-- Class -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-13 fw-medium">Class</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">class</span>
                                </span>
                                <select class="form-select form-select-sm" name="class" id="class" style="height: 38px;">
                                    <option value="">Select Class</option>
                                </select>
                            </div>
                        </div>

                        <!-- Section -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-13 fw-medium">Section</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">group</span>
                                </span>
                                <select class="form-select form-select-sm" name="section" id="section" style="height: 38px;">
                                    <option value="">Select Section</option>
                                </select>
                            </div>
                        </div>

                        <!-- Increase -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-13 fw-medium">Increase (%)</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">trending_up</span>
                                </span>
                                <input type="number" step="0.01" min="0" class="form-control form-control-sm" name="increase" id="increase" placeholder="Enter Increase Percentage" required style="height: 38px;">
                            </div>
                        </div>

                        <!-- Accountant -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-13 fw-medium">Accountant</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">person</span>
                                </span>
                                <input type="text" class="form-control form-control-sm" name="accountant" id="accountant" value="{{ $loggedInUserName ?? '' }}" required readonly style="background-color: #f8f9fa; cursor: not-allowed; height: 38px;">
                            </div>
                        </div>

                        <!-- Date -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-13 fw-medium">Date</label>
                            <div class="input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff;">
                                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">calendar_today</span>
                                </span>
                                <input type="date" class="form-control form-control-sm" name="date" id="date" value="{{ date('Y-m-d') }}" required style="height: 38px;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Student Selection -->
                <div class="payment-row mb-3 p-3 border rounded" style="background-color: #f8f9fa;">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="mb-0 fw-semibold" style="color: #003471;">Select Students</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" id="loadStudentsBtn" onclick="loadStudentsForIncrement()" disabled>
                            Load Students
                        </button>
                    </div>
                    
                    <div id="studentListContainer" style="display: none;">
                        <div class="mb-3">
                            <label class="form-check-label d-flex align-items-center gap-2" style="cursor: pointer;">
                                <input type="checkbox" id="select_all_students" class="form-check-input" onchange="toggleSelectAllStudents(this)" style="width: 18px; height: 18px; cursor: pointer;">
                                <strong style="color: #003471; font-size: 14px;">Select All Students</strong>
                            </label>
                        </div>
                        <div id="studentList" style="max-height: 500px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 8px; padding: 15px;">
                            <!-- Student list will be loaded here -->
                        </div>
                        <div id="noStudentMessage" class="text-muted text-center py-4" style="display: none;">
                            <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">school</span>
                            <p class="mt-2 mb-0">No students found.</p>
                        </div>
                    </div>
                    <div id="selectFiltersMessage" class="text-muted text-center py-4">
                        <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">filter_list</span>
                        <p class="mt-2 mb-0">Please select Campus, Class, and Section to view students.</p>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="text-center mt-4">
                    <button type="submit" class="btn btn-success px-5 py-2" style="background-color: #28a745; border: none; color: white;">
                        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle; color: white;">thumb_up</span>
                        <span style="color: white;">Submit</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .payment-row {
        border: 1px solid #dee2e6 !important;
        transition: all 0.3s ease;
    }
    
    .payment-row:hover {
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
    }
    
    .input-group-text {
        border-right: none;
    }
    
    .form-select,
    .form-control {
        border-left: none;
    }
    
    .form-select:focus,
    .form-control:focus {
        border-color: #003471;
        box-shadow: 0 0 0 0.2rem rgba(0, 52, 113, 0.25);
    }
    
    .input-group:focus-within .input-group-text {
        background-color: #003471 !important;
        color: white !important;
        border-color: #003471;
    }
    
    .input-group:focus-within .material-symbols-outlined {
        color: white !important;
    }
    
    .btn-success {
        color: white !important;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .btn-success .material-symbols-outlined {
        color: white !important;
    }
    
    .btn-success:hover {
        background-color: #218838 !important;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const campusSelect = document.getElementById('campus');
    const classSelect = document.getElementById('class');
    const sectionSelect = document.getElementById('section');
    const increaseInput = document.getElementById('increase');

    function resetSelect(selectEl, placeholder) {
        selectEl.innerHTML = `<option value="">${placeholder}</option>`;
    }

    function loadClassesByCampus(campus) {
        resetSelect(classSelect, 'Select Class');
        resetSelect(sectionSelect, 'Select Section');

        if (!campus) {
            return;
        }

        fetch(`{{ route('accounting.fee-increment.percentage.get-classes-by-campus') }}?campus=${encodeURIComponent(campus)}`)
            .then(response => response.json())
            .then(classes => {
                classes.forEach(className => {
                    const option = document.createElement('option');
                    option.value = className;
                    option.textContent = className;
                    classSelect.appendChild(option);
                });
            })
            .catch(() => {});
    }

    function loadSectionsByClass(campus, className) {
        resetSelect(sectionSelect, 'Select Section');

        if (!className) {
            return;
        }

        const params = new URLSearchParams();
        if (campus) {
            params.append('campus', campus);
        }
        params.append('class', className);

        fetch(`{{ route('accounting.fee-increment.percentage.get-sections-by-class') }}?${params.toString()}`)
            .then(response => response.json())
            .then(sections => {
                sections.forEach(sectionName => {
                    const option = document.createElement('option');
                    option.value = sectionName;
                    option.textContent = sectionName;
                    sectionSelect.appendChild(option);
                });
            })
            .catch(() => {});
    }

    function updateLoadState() {
        const campus = campusSelect.value || '';
        const className = classSelect.value || '';
        const section = sectionSelect.value || '';
        const loadBtn = document.getElementById('loadStudentsBtn');
        const enable = !!(campus && className && section);
        if (loadBtn) loadBtn.disabled = !enable;
        
        // Auto-load students when all filters are selected
        if (enable) {
            loadStudentsForIncrement();
        } else {
            // Reset student list when filters are cleared
            const studentListContainer = document.getElementById('studentListContainer');
            const selectFiltersMessage = document.getElementById('selectFiltersMessage');
            if (studentListContainer) studentListContainer.style.display = 'none';
            if (selectFiltersMessage) selectFiltersMessage.style.display = 'block';
        }
    }

    campusSelect.addEventListener('change', function() {
        loadClassesByCampus(this.value);
        updateLoadState();
    });

    classSelect.addEventListener('change', function() {
        loadSectionsByClass(campusSelect.value, this.value);
        updateLoadState();
    });

    sectionSelect.addEventListener('change', function() {
        updateLoadState();
    });

    increaseInput.addEventListener('input', function() {
        updateIncrementPreview();
    });
    
    // Update preview when students are selected/deselected
    document.addEventListener('change', function(e) {
        if (e.target.classList.contains('student-checkbox')) {
            updateSelectAllState();
        }
    });
});
</script>

<script>
function loadStudentsForIncrement() {
    const campus = document.getElementById('campus').value || '';
    const className = document.getElementById('class').value || '';
    const section = document.getElementById('section').value || '';
    const studentListContainer = document.getElementById('studentListContainer');
    const selectFiltersMessage = document.getElementById('selectFiltersMessage');
    const studentList = document.getElementById('studentList');
    const noStudentMessage = document.getElementById('noStudentMessage');

    if (!campus || !className || !section) {
        studentListContainer.style.display = 'none';
        selectFiltersMessage.style.display = 'block';
        studentList.innerHTML = '';
        const selectAll = document.getElementById('select_all_students');
        if (selectAll) {
            selectAll.checked = false;
        }
        return;
    }

    // Show loading
    studentList.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    studentListContainer.style.display = 'block';
    selectFiltersMessage.style.display = 'none';
    noStudentMessage.style.display = 'none';

    const params = new URLSearchParams({
        campus,
        class: className,
        section
    });

    fetch(`{{ route('accounting.fee-increment.percentage.get-students') }}?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            const students = data.students || [];
            if (students.length > 0) {
                let html = '<div class="row g-2">';
                students.forEach(student => {
                    const currentFee = parseFloat(student.monthly_fee || 0);
                    html += `
                        <div class="col-md-6 col-lg-4">
                            <div class="card border p-2 mb-2" style="border-radius: 8px;">
                                <div class="form-check">
                                    <input class="form-check-input student-checkbox" type="checkbox" name="selected_students[]" value="${student.id}" id="student_${student.id}" onchange="updateSelectAllState()" checked style="width: 18px; height: 18px; cursor: pointer;">
                                    <label class="form-check-label w-100" for="student_${student.id}" style="cursor: pointer;">
                                        <div class="d-flex justify-content-between align-items-start">
                                            <div class="flex-grow-1">
                                                <strong style="color: #003471; font-size: 13px;">${escapeHtml(student.student_name || 'N/A')}</strong>
                                                ${student.student_code ? `<br><small class="text-muted" style="font-size: 11px;">Code: ${escapeHtml(student.student_code)}</small>` : ''}
                                                ${student.father_name ? `<br><small class="text-muted" style="font-size: 11px;">Parent: ${escapeHtml(student.father_name)}</small>` : ''}
                                                ${student.class ? `<br><small class="text-muted" style="font-size: 11px;">${escapeHtml(student.class)} - ${escapeHtml(student.section || 'N/A')}</small>` : ''}
                                            </div>
                                            <div class="text-end ms-2">
                                                <small class="text-success fw-bold" style="font-size: 12px;">₹${currentFee.toFixed(2)}</small>
                                                <div id="new_fee_${student.id}" class="text-primary fw-bold" style="font-size: 11px; display: none;"></div>
                                            </div>
                                        </div>
                                    </label>
                                </div>
                            </div>
                        </div>
                    `;
                });
                html += '</div>';
                studentList.innerHTML = html;
                noStudentMessage.style.display = 'none';
                
                const selectAll = document.getElementById('select_all_students');
                if (selectAll) {
                    selectAll.checked = true;
                }
                updateIncrementPreview();
            } else {
                studentList.innerHTML = '';
                noStudentMessage.style.display = 'block';
                const selectAll = document.getElementById('select_all_students');
                if (selectAll) {
                    selectAll.checked = false;
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            studentList.innerHTML = '<div class="alert alert-danger">Error loading students. Please try again.</div>';
            const selectAll = document.getElementById('select_all_students');
            if (selectAll) {
                selectAll.checked = false;
            }
        });
}

function updateIncrementPreview() {
    const increaseValue = parseFloat(document.getElementById('increase').value || 0);
    const checkboxes = document.querySelectorAll('.student-checkbox:checked');
    
    if (increaseValue > 0) {
        checkboxes.forEach(checkbox => {
            const studentId = checkbox.value;
            const studentCard = checkbox.closest('.card');
            const currentFeeText = studentCard.querySelector('.text-success');
            const currentFee = parseFloat(currentFeeText.textContent.replace('₹', '').replace(',', ''));
            const newFee = currentFee + (currentFee * (increaseValue / 100));
            const previewDiv = document.getElementById(`new_fee_${studentId}`);
            if (previewDiv) {
                previewDiv.textContent = `→ ₹${newFee.toFixed(2)}`;
                previewDiv.style.display = 'block';
            }
        });
    } else {
        document.querySelectorAll('[id^="new_fee_"]').forEach(div => {
            div.style.display = 'none';
        });
    }
}

function toggleSelectAllStudents(source) {
    const checkboxes = document.querySelectorAll('.student-checkbox');
    checkboxes.forEach(cb => {
        cb.checked = source.checked;
    });
    updateSelectAllState();
    updateIncrementPreview();
}

function updateSelectAllState() {
    const checkboxes = document.querySelectorAll('.student-checkbox');
    const checkedBoxes = document.querySelectorAll('.student-checkbox:checked');
    const selectAll = document.getElementById('select_all_students');
    if (selectAll) {
        selectAll.checked = checkboxes.length > 0 && checkedBoxes.length === checkboxes.length;
    }
    updateIncrementPreview();
}

// Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>

<script>
// Form is now connected to database, no need for preventDefault
// Form will submit normally to the backend
</script>
@endsection
