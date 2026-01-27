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
                                <select class="form-select form-select-sm" name="accountant" id="accountant" style="height: 38px;">
                                    <option value="">Select Accountant</option>
                                    @foreach($accountants as $accountant)
                                        <option value="{{ $accountant->name }}">{{ $accountant->name }}</option>
                                    @endforeach
                                </select>
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
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="mb-0 fw-semibold" style="color: #003471;">Select Students</h6>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="loadStudentsForIncrement()">
                            Load Students
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm table-hover mb-0">
                            <thead>
                                <tr>
                                    <th style="width: 40px;">
                                        <input type="checkbox" id="select_all_students" onclick="toggleSelectAllStudents(this)">
                                    </th>
                                    <th>Code</th>
                                    <th>Student</th>
                                    <th>Parent</th>
                                    <th>Class</th>
                                    <th>Section</th>
                                    <th>Current Fee</th>
                                    <th>New Fee</th>
                                </tr>
                            </thead>
                            <tbody id="studentSelectionBody">
                                <tr>
                                    <td colspan="8" class="text-center text-muted">Select filters and click "Load Students".</td>
                                </tr>
                            </tbody>
                        </table>
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

    campusSelect.addEventListener('change', function() {
        loadClassesByCampus(this.value);
    });

    classSelect.addEventListener('change', function() {
        loadSectionsByClass(campusSelect.value, this.value);
    });

    increaseInput.addEventListener('input', function() {
        updateIncrementPreview();
    });
});
</script>

<script>
function loadStudentsForIncrement() {
    const campus = document.getElementById('campus').value || '';
    const className = document.getElementById('class').value || '';
    const section = document.getElementById('section').value || '';
    const tbody = document.getElementById('studentSelectionBody');

    tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Loading...</td></tr>';

    const params = new URLSearchParams({
        campus,
        class: className,
        section
    });

    fetch(`{{ route('accounting.fee-increment.percentage.get-students') }}?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            const students = data.students || [];
            if (students.length === 0) {
                tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">No students found.</td></tr>';
                return;
            }
            tbody.innerHTML = '';
            students.forEach(student => {
                const currentFee = parseFloat(student.monthly_fee || 0);
                const row = document.createElement('tr');
                row.innerHTML = `
                    <td>
                        <input type="checkbox" name="selected_students[]" value="${student.id}" class="student-select" checked>
                    </td>
                    <td>${student.student_code || 'N/A'}</td>
                    <td>${student.student_name || 'N/A'}</td>
                    <td>${student.father_name || 'N/A'}</td>
                    <td>${student.class || 'N/A'}</td>
                    <td>${student.section || 'N/A'}</td>
                    <td class="current-fee" data-fee="${currentFee}">${currentFee.toFixed(2)}</td>
                    <td class="new-fee">0.00</td>
                `;
                tbody.appendChild(row);
            });
            updateIncrementPreview();
        })
        .catch(() => {
            tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted">Error loading students.</td></tr>';
        });
}

function updateIncrementPreview() {
    const increaseValue = parseFloat(document.getElementById('increase').value || 0);
    const rows = document.querySelectorAll('#studentSelectionBody tr');
    rows.forEach(row => {
        const currentFeeCell = row.querySelector('.current-fee');
        const newFeeCell = row.querySelector('.new-fee');
        if (!currentFeeCell || !newFeeCell) {
            return;
        }
        const currentFee = parseFloat(currentFeeCell.dataset.fee || 0);
        const newFee = currentFee + (currentFee * (increaseValue / 100));
        newFeeCell.textContent = newFee.toFixed(2);
    });
}

function toggleSelectAllStudents(source) {
    const checkboxes = document.querySelectorAll('.student-select');
    checkboxes.forEach(cb => {
        cb.checked = source.checked;
    });
}
</script>

<script>
// Form is now connected to database, no need for preventDefault
// Form will submit normally to the backend
</script>
@endsection
