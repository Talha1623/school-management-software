@extends('layouts.app')

@section('title', 'Student Transfer')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Student Transfer</h4>
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

            <!-- Transfer Form -->
            <div class="card border-0 shadow-sm" style="border-radius: 8px; overflow: hidden;">
                <div class="card-body p-4">
                    <form action="{{ route('student.transfer.store') }}" method="POST" id="transferForm">
                        @csrf
                        <div class="row g-3 align-items-end">
                            <!-- From Campus -->
                            <div class="col-md-3">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    From Campus
                                </label>
                                <select class="form-select form-select-sm" name="from_campus" id="from_campus" onchange="loadStudents('campus')">
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus }}">{{ $campus }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- To Campus -->
                            <div class="col-md-3">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    To Campus <span class="text-danger">*</span>
                                </label>
                                <select class="form-select form-select-sm" name="to_campus" id="to_campus" required onchange="loadClassesByToCampus(this.value)">
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus }}">{{ $campus }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Class -->
                            <div class="col-md-3">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Class <span class="text-danger">*</span>
                                </label>
                                <select class="form-select form-select-sm" name="class" id="class" required onchange="loadStudents('class')" disabled>
                                    <option value="">Select Campus First</option>
                                    @foreach($classes as $class)
                                        <option value="{{ $class }}">{{ $class }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <!-- Student Code -->
                            <div class="col-md-3 position-relative">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Student Code <span class="text-danger">*</span>
                                </label>
                                <select class="form-select form-select-sm" name="student_code" id="student_code" required onchange="selectStudentByCode(this.value)">
                                    <option value="">Select Student Code</option>
                                </select>
                                <input type="hidden" name="student_id" id="student_id">
                                <div id="student-info" class="mt-1" style="display: none;">
                                    <small class="text-success">
                                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">check_circle</span>
                                        <span id="student-name-display"></span>
                                    </small>
                                </div>
                            </div>
                        </div>

                        <!-- Additional Options Row -->
                        <div class="row g-3 mt-2 align-items-end">
                            <!-- Also Move Dues -->
                            <div class="col-md-4">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Also Move Dues
                                </label>
                                <select class="form-select form-select-sm" name="move_dues" id="move_dues">
                                    <option value="no">No</option>
                                    <option value="yes">Yes</option>
                                </select>
                            </div>

                            <!-- Also Move Payments -->
                            <div class="col-md-4">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Also Move Payments
                                </label>
                                <select class="form-select form-select-sm" name="move_payments" id="move_payments">
                                    <option value="no">No</option>
                                    <option value="yes">Yes</option>
                                </select>
                            </div>

                            <!-- Notify Parent -->
                            <div class="col-md-4">
                                <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                                    Notify Parent
                                </label>
                                <select class="form-select form-select-sm" name="notify_parent" id="notify_parent">
                                    <option value="yes">Yes</option>
                                    <option value="no">No</option>
                                </select>
                            </div>
                        </div>

                        <!-- Transfer Button -->
                        <div class="row mt-4">
                            <div class="col-12">
                                <div class="d-flex justify-content-end">
                                    <button type="submit" class="btn btn-primary transfer-btn">
                                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">swap_horiz</span>
                                        <span style="font-size: 12px;">Transfer Student</span>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .rounded-8 {
        border-radius: 8px;
    }
    
    /* Form Styling */
    .form-select-sm,
    .form-control-sm {
        font-size: 13px;
        padding: 6px 12px;
        border-radius: 6px;
        border: 1px solid #dee2e6;
        transition: all 0.3s ease;
        height: 32px;
    }
    
    .form-select-sm:focus,
    .form-control-sm:focus {
        border-color: #003471;
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
        outline: none;
    }
    
    .form-label {
        margin-bottom: 0.4rem;
        line-height: 1.3;
    }

    /* Transfer Button Styling */
    .transfer-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        padding: 4px 12px;
        font-size: 12px;
        height: 32px;
        line-height: 1.4;
        transition: all 0.3s ease;
        box-shadow: 0 2px 6px rgba(0, 52, 113, 0.2);
        border-radius: 6px;
        display: flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
    }
    
    .transfer-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 52, 113, 0.3);
        color: white;
    }
    
    .transfer-btn:active {
        transform: translateY(0);
    }

    /* Card Styling */
    .card {
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }
</style>

<script>
let searchTimeout;

function resetStudentSelection() {
    const studentCodeInput = document.getElementById('student_code');
    const studentIdInput = document.getElementById('student_id');
    const studentInfoDiv = document.getElementById('student-info');
    const studentNameDisplay = document.getElementById('student-name-display');

    if (studentCodeInput) studentCodeInput.value = '';
    if (studentIdInput) studentIdInput.value = '';
    if (studentNameDisplay) studentNameDisplay.textContent = '';
    if (studentInfoDiv) studentInfoDiv.style.display = 'none';
}

function loadClassesByCampus(campusValue) {
    if (!classSelect) return;

    if (!campusValue) {
        classSelect.innerHTML = '<option value="">Select Campus First</option>';
        classSelect.disabled = true;
        resetStudentSelection();
        return;
    }

    classSelect.innerHTML = '<option value="">Loading classes...</option>';
    classSelect.disabled = true;

    fetch(`{{ route('student.transfer.get-classes-by-campus') }}?campus=${encodeURIComponent(campusValue)}`)
        .then(response => response.json())
        .then(data => {
            const classes = Array.isArray(data.classes) ? data.classes : [];
            classSelect.innerHTML = '<option value="">Select Class</option>';

            if (classes.length > 0) {
                classes.forEach(className => {
                    const option = document.createElement('option');
                    option.value = className;
                    option.textContent = className;
                    classSelect.appendChild(option);
                });
                classSelect.disabled = false;
            } else {
                classSelect.innerHTML = '<option value="">No classes found</option>';
                classSelect.disabled = true;
            }
            loadStudentsByClass();
        })
        .catch(error => {
            console.error('Error loading classes:', error);
            classSelect.innerHTML = '<option value="">Error loading classes</option>';
            classSelect.disabled = true;
        });
}

function loadClassesByToCampus(campusValue) {
    const classSelect = document.getElementById('class');
    if (!classSelect) return;

    if (!campusValue) {
        classSelect.innerHTML = '<option value="">Select Campus First</option>';
        classSelect.disabled = true;
        return;
    }

    classSelect.innerHTML = '<option value="">Loading classes...</option>';
    classSelect.disabled = true;

    fetch(`{{ route('student.transfer.get-classes-by-campus') }}?campus=${encodeURIComponent(campusValue)}`)
        .then(response => response.json())
        .then(data => {
            const classes = Array.isArray(data.classes) ? data.classes : [];
            classSelect.innerHTML = '<option value="">Select Class</option>';

            if (classes.length > 0) {
                classes.forEach(className => {
                    const option = document.createElement('option');
                    option.value = className;
                    option.textContent = className;
                    classSelect.appendChild(option);
                });
                classSelect.disabled = false;
            } else {
                classSelect.innerHTML = '<option value="">No classes found</option>';
                classSelect.disabled = true;
            }
        })
        .catch(error => {
            console.error('Error loading classes:', error);
            classSelect.innerHTML = '<option value="">Error loading classes</option>';
            classSelect.disabled = true;
        });
}

function loadStudents(source) {
    const campusValue = document.getElementById('from_campus')?.value;
    if (source === 'campus') {
        loadStudentsByClass();
        return;
    }
    if (source === 'class') {
        return;
    }
    loadStudentsByClass();
}

function loadStudentsByClass() {
    const campusValue = document.getElementById('from_campus')?.value;
    const studentCodeSelect = document.getElementById('student_code');

    resetStudentSelection();

    if (!campusValue || !studentCodeSelect) {
        return;
    }

    studentCodeSelect.innerHTML = '<option value="">Loading...</option>';

    const params = new URLSearchParams();
    params.append('campus', campusValue);

    fetch(`{{ route('student.transfer.get-students') }}?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            const students = Array.isArray(data.students) ? data.students : [];
            studentCodeSelect.innerHTML = '<option value="">Select Student Code</option>';
            students.forEach(student => {
                const option = document.createElement('option');
                option.value = student.code;
                option.textContent = `${student.code} - ${student.name}`;
                option.dataset.studentId = student.id;
                option.dataset.studentName = student.name;
                option.dataset.studentClass = student.class || '';
                studentCodeSelect.appendChild(option);
            });
        })
        .catch(error => {
            console.error('Error loading students:', error);
            studentCodeSelect.innerHTML = '<option value="">Error loading students</option>';
        });
}

function selectStudentByCode(code) {
    const studentCodeSelect = document.getElementById('student_code');
    const studentIdInput = document.getElementById('student_id');
    const studentInfoDiv = document.getElementById('student-info');
    const studentNameDisplay = document.getElementById('student-name-display');

    if (!studentCodeSelect || !studentIdInput || !studentInfoDiv || !studentNameDisplay) {
        return;
    }

    const selectedOption = studentCodeSelect.options[studentCodeSelect.selectedIndex];
    const studentId = selectedOption?.dataset?.studentId || '';
    const studentName = selectedOption?.dataset?.studentName || '';

    studentIdInput.value = studentId;
    studentNameDisplay.textContent = studentName;
    studentInfoDiv.style.display = studentId ? 'block' : 'none';
}

document.addEventListener('DOMContentLoaded', function() {
    const campusValue = document.getElementById('from_campus')?.value;
    if (campusValue) {
        loadClassesByCampus(campusValue);
        loadStudentsByClass();
    }
});

// Form validation
document.getElementById('transferForm').addEventListener('submit', function(e) {
    const toCampus = document.getElementById('to_campus').value;
    const classValue = document.getElementById('class').value;
    const studentId = document.getElementById('student_id').value;
    
    if (!toCampus || !classValue) {
        e.preventDefault();
        alert('Please fill in all required fields (marked with *)');
        return false;
    }
    
    if (!studentId) {
        e.preventDefault();
        alert('Please select a student from the list.');
        return false;
    }
    
    if (!confirm('Are you sure you want to transfer this student? This action cannot be undone.')) {
        e.preventDefault();
        return false;
    }
});
</script>
@endsection
