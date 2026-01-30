@extends('layouts.app')

@section('title', 'Increment By Amount')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Increment By Amount</h4>
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

            <form action="{{ route('salary-loan.increment.amount.store') }}" method="POST" id="incrementAmountForm" onsubmit="return validateForm()">
                @csrf
                
                <div class="row g-3">
                    <!-- Campus -->
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-3 mb-2">
                            <h5 class="mb-2 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -12px -12px 12px -12px; background-color: #003471;">Campus</h5>
                            
                            <label for="campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm increment-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">location_on</span>
                                </span>
                                <select class="form-control increment-input" name="campus" id="campus" required onchange="loadStaffByCampus()" style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0; height: 36px;">
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus }}">{{ $campus }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Salary Type -->
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-3 mb-2">
                            <h5 class="mb-2 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -12px -12px 12px -12px; background-color: #003471;">Salary Type</h5>
                            
                            <label for="salary_type" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Salary Type <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm increment-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">work</span>
                                </span>
                                <select class="form-control increment-input" name="salary_type" id="salary_type" required onchange="toggleFeesIncrementFields(); loadStaffByCampus();" style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0; height: 36px;">
                                    <option value="">Select Salary Type</option>
                                    <option value="full time">Full Time</option>
                                    <option value="per hour">Per Hour</option>
                                    <option value="lecture">Per Lecture</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Increase -->
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-3 mb-2">
                            <h5 class="mb-2 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -12px -12px 12px -12px; background-color: #003471;">Increase</h5>
                            
                            <label for="increase" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Salary Increase (Amount) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm increment-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">currency_rupee</span>
                                </span>
                                <input type="number" class="form-control increment-input" name="increase" id="increase" placeholder="Enter amount" step="0.01" min="0" required style="height: 36px;">
                            </div>
                        </div>
                    </div>
                    
                    <!-- Fees Increment (Optional - Only for Full Time) -->
                    <div class="col-md-6" id="feesIncrementContainer" style="display: none;">
                        <div class="card bg-light border-0 rounded-10 p-3 mb-2">
                            <h5 class="mb-2 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -12px -12px 12px -12px; background-color: #003471;">Fees Increment (Optional)</h5>
                            
                            <label for="absent_fees_increment" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Absent Fees Increment</label>
                            <div class="input-group input-group-sm increment-input-group mb-2">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">currency_rupee</span>
                                </span>
                                <input type="number" class="form-control increment-input" name="absent_fees_increment" id="absent_fees_increment" placeholder="Enter amount (optional)" step="0.01" min="0" style="height: 36px;">
                            </div>
                            
                            <label for="late_fees_increment" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Late Fees Increment</label>
                            <div class="input-group input-group-sm increment-input-group mb-2">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">currency_rupee</span>
                                </span>
                                <input type="number" class="form-control increment-input" name="late_fees_increment" id="late_fees_increment" placeholder="Enter amount (optional)" step="0.01" min="0" style="height: 36px;">
                            </div>
                            
                            <label for="early_exit_fees_increment" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Early Exit Fees Increment</label>
                            <div class="input-group input-group-sm increment-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">currency_rupee</span>
                                </span>
                                <input type="number" class="form-control increment-input" name="early_exit_fees_increment" id="early_exit_fees_increment" placeholder="Enter amount (optional)" step="0.01" min="0" style="height: 36px;">
                            </div>
                            <small class="text-muted" style="font-size: 10px;">Only apply if amount is entered, otherwise fees will not be incremented</small>
                        </div>
                    </div>

                    <!-- Accountant -->
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-3 mb-2">
                            <h5 class="mb-2 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -12px -12px 12px -12px; background-color: #003471;">Accountant</h5>
                            
                            <label for="accountant" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Accountant <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm increment-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">person</span>
                                </span>
                                <input type="text" class="form-control increment-input" name="accountant" id="accountant" value="{{ $loggedInUserName }}" required readonly style="background-color: #f8f9fa; cursor: not-allowed; border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0; height: 36px;">
                            </div>
                        </div>
                    </div>

                    <!-- Date -->
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-3 mb-2">
                            <h5 class="mb-2 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -12px -12px 12px -12px; background-color: #003471;">Date</h5>
                            
                            <label for="date" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Date <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm increment-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">calendar_today</span>
                                </span>
                                <input type="date" class="form-control increment-input" name="date" id="date" value="{{ $currentDate }}" required style="height: 36px;">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Staff Selection -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="card bg-light border-0 rounded-10 p-3 mb-2">
                            <h5 class="mb-2 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -12px -12px 12px -12px; background-color: #003471;">Select Staff</h5>
                            
                            <div id="staffListContainer" style="display: none;">
                                <div class="mb-3">
                                    <label class="form-check-label d-flex align-items-center gap-2" style="cursor: pointer;">
                                        <input type="checkbox" id="selectAllStaff" class="form-check-input" onchange="toggleSelectAll()" style="width: 18px; height: 18px; cursor: pointer;">
                                        <strong style="color: #003471; font-size: 14px;">Select All Staff</strong>
                                    </label>
                                </div>
                                <div id="staffList" style="max-height: 400px; overflow-y: auto; border: 1px solid #dee2e6; border-radius: 8px; padding: 15px;">
                                    <!-- Staff list will be loaded here -->
                                </div>
                                <div id="noStaffMessage" class="text-muted text-center py-4" style="display: none;">
                                    <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">people</span>
                                    <p class="mt-2 mb-0">No staff found for this campus.</p>
                                </div>
                            </div>
                            <div id="selectCampusMessage" class="text-muted text-center py-4">
                                <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">location_on</span>
                                <p class="mt-2 mb-0">Please select a campus to view staff list.</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="row mt-4">
                    <div class="col-12">
                        <div class="d-flex justify-content-end gap-2">
                            <button type="button" class="btn btn-sm py-2 px-4 rounded-8" onclick="resetForm()" style="background-color: #6c757d; color: white; border: none; transition: all 0.3s ease;">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">refresh</span>
                                Reset
                            </button>
                            <button type="submit" class="btn btn-sm py-2 px-4 rounded-8 increment-submit-btn">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
                                Save Increment
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Increment Form Styling */
    .increment-input-group {
        height: 36px;
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s ease;
        border: 1px solid #dee2e6;
    }
    
    .increment-input-group:focus-within {
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
        border-color: #003471;
    }
    
    .increment-input {
        height: 36px;
        font-size: 13px;
        padding: 0.5rem 0.75rem;
        border: none;
        border-left: 1px solid #e0e7ff;
        border-radius: 0;
        transition: all 0.3s ease;
    }
    
    .increment-input:focus {
        border-left-color: #003471;
        box-shadow: none;
        outline: none;
    }
    
    .increment-input-group .input-group-text {
        height: 36px;
        padding: 0 0.75rem;
        display: flex;
        align-items: center;
        border: none;
        border-right: 1px solid #e0e7ff;
        border-radius: 8px 0 0 8px;
        transition: all 0.3s ease;
    }
    
    .increment-input-group:focus-within .input-group-text {
        background-color: #003471 !important;
        color: white !important;
        border-right-color: #003471;
    }
    
    .increment-input-group select.increment-input {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23003471' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        padding-right: 2.5rem;
    }
    
    .form-label {
        margin-bottom: 0.4rem;
        line-height: 1.3;
    }
    
    .increment-submit-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 52, 113, 0.25);
    }
    
    .increment-submit-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 52, 113, 0.35);
        color: white;
    }
    
    .increment-submit-btn:active {
        transform: translateY(0);
    }
    
    .rounded-8 {
        border-radius: 8px;
    }
    
    .rounded-10 {
        border-radius: 10px;
    }
</style>

<script>
// Toggle fees increment fields based on salary type
function toggleFeesIncrementFields() {
    const salaryType = document.getElementById('salary_type').value;
    const feesContainer = document.getElementById('feesIncrementContainer');
    
    if (feesContainer) {
        if (salaryType === 'full time') {
            feesContainer.style.display = 'block';
        } else {
            feesContainer.style.display = 'none';
            // Clear fees increment fields when hidden
            const absentField = document.getElementById('absent_fees_increment');
            const lateField = document.getElementById('late_fees_increment');
            const earlyExitField = document.getElementById('early_exit_fees_increment');
            if (absentField) absentField.value = '';
            if (lateField) lateField.value = '';
            if (earlyExitField) earlyExitField.value = '';
            updateSalaryPreview();
        }
    }
}

// Reset form
function resetForm() {
    document.getElementById('incrementAmountForm').reset();
    // Set current date as default
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('date').value = today;
    // Set logged in user name
    document.getElementById('accountant').value = '{{ $loggedInUserName }}';
    // Clear staff list
    document.getElementById('staffListContainer').style.display = 'none';
    document.getElementById('selectCampusMessage').style.display = 'block';
    document.getElementById('selectCampusMessage').innerHTML = '<span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">location_on</span><p class="mt-2 mb-0">Please select a campus and salary type to view staff list.</p>';
    document.getElementById('staffList').innerHTML = '';
    document.getElementById('selectAllStaff').checked = false;
    // Hide fees increment fields
    toggleFeesIncrementFields();
}

// Load staff by campus and salary type
function loadStaffByCampus() {
    const campus = document.getElementById('campus').value;
    const salaryType = document.getElementById('salary_type').value;
    const staffListContainer = document.getElementById('staffListContainer');
    const selectCampusMessage = document.getElementById('selectCampusMessage');
    const staffList = document.getElementById('staffList');
    const noStaffMessage = document.getElementById('noStaffMessage');
    
    // Toggle fees increment fields
    toggleFeesIncrementFields();
    
    if (!campus || !salaryType) {
        staffListContainer.style.display = 'none';
        selectCampusMessage.style.display = 'block';
        if (!campus) {
            selectCampusMessage.innerHTML = '<span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">location_on</span><p class="mt-2 mb-0">Please select a campus to view staff list.</p>';
        } else {
            selectCampusMessage.innerHTML = '<span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">work</span><p class="mt-2 mb-0">Please select a salary type to view staff list.</p>';
        }
        staffList.innerHTML = '';
        document.getElementById('selectAllStaff').checked = false;
        return;
    }
    
    // Show loading
    staffList.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    staffListContainer.style.display = 'block';
    selectCampusMessage.style.display = 'none';
    noStaffMessage.style.display = 'none';
    
    fetch(`{{ route('salary-loan.increment.amount.get-staff-by-campus') }}?campus=${encodeURIComponent(campus)}&salary_type=${encodeURIComponent(salaryType)}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.staff && data.staff.length > 0) {
            let html = '<div class="row g-2">';
            data.staff.forEach(staff => {
                const currentSalary = parseFloat(staff.salary || 0);
                const absentFees = parseFloat(staff.absent_fees || 0);
                const lateFees = parseFloat(staff.late_fees || 0);
                const earlyExitFees = parseFloat(staff.early_exit_fees || 0);
                
                let feesHtml = '';
                if (absentFees > 0 || lateFees > 0 || earlyExitFees > 0) {
                    feesHtml = '<div class="mt-2 pt-2" style="border-top: 1px solid #dee2e6;">';
                    if (absentFees > 0) {
                        feesHtml += `<small class="d-block text-muted" style="font-size: 10px;" data-original-absent="${absentFees}"><strong>Absent:</strong> ₹${absentFees.toFixed(2)}<span id="new_absent_${staff.id}" class="text-primary" style="display: none;"></span></small>`;
                    }
                    if (lateFees > 0) {
                        feesHtml += `<small class="d-block text-muted" style="font-size: 10px;" data-original-late="${lateFees}"><strong>Late:</strong> ₹${lateFees.toFixed(2)}<span id="new_late_${staff.id}" class="text-primary" style="display: none;"></span></small>`;
                    }
                    if (earlyExitFees > 0) {
                        feesHtml += `<small class="d-block text-muted" style="font-size: 10px;" data-original-early-exit="${earlyExitFees}"><strong>Early Exit:</strong> ₹${earlyExitFees.toFixed(2)}<span id="new_early_exit_${staff.id}" class="text-primary" style="display: none;"></span></small>`;
                    }
                    feesHtml += '</div>';
                }
                
                html += `
                    <div class="col-md-6 col-lg-4">
                        <div class="card border p-2 mb-2" style="border-radius: 8px;">
                            <div class="form-check">
                                <input class="form-check-input staff-checkbox" type="checkbox" name="selected_staff[]" value="${staff.id}" id="staff_${staff.id}" onchange="updateSelectAllState()" style="width: 18px; height: 18px; cursor: pointer;">
                                <label class="form-check-label w-100" for="staff_${staff.id}" style="cursor: pointer;">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <strong style="color: #003471; font-size: 13px;">${escapeHtml(staff.name)}</strong>
                                            ${staff.emp_id ? `<br><small class="text-muted" style="font-size: 11px;">ID: ${escapeHtml(staff.emp_id)}</small>` : ''}
                                            ${staff.designation ? `<br><small class="text-muted" style="font-size: 11px;">${escapeHtml(staff.designation)}</small>` : ''}
                                            ${feesHtml}
                                        </div>
                                        <div class="text-end ms-2">
                                            <small class="text-success fw-bold" style="font-size: 12px;">₹${currentSalary.toFixed(2)}</small>
                                            <div id="new_salary_${staff.id}" class="text-primary fw-bold" style="font-size: 11px; display: none;"></div>
                                        </div>
                                    </div>
                                </label>
                            </div>
                        </div>
                    </div>
                `;
            });
            html += '</div>';
            staffList.innerHTML = html;
            noStaffMessage.style.display = 'none';
        } else {
            staffList.innerHTML = '';
            noStaffMessage.style.display = 'block';
        }
        document.getElementById('selectAllStaff').checked = false;
    })
    .catch(error => {
        console.error('Error:', error);
        staffList.innerHTML = '<div class="alert alert-danger">Error loading staff. Please try again.</div>';
    });
}

// Toggle select all
function toggleSelectAll() {
    const selectAll = document.getElementById('selectAllStaff').checked;
    const checkboxes = document.querySelectorAll('.staff-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = selectAll;
    });
    updateSalaryPreview();
}

// Update select all state
function updateSelectAllState() {
    const checkboxes = document.querySelectorAll('.staff-checkbox');
    const checkedBoxes = document.querySelectorAll('.staff-checkbox:checked');
    const selectAll = document.getElementById('selectAllStaff');
    selectAll.checked = checkboxes.length > 0 && checkedBoxes.length === checkboxes.length;
    updateSalaryPreview();
}

// Update salary preview when amount changes
document.getElementById('increase').addEventListener('input', function() {
    updateSalaryPreview();
});

// Update preview when fees increment fields change
document.getElementById('absent_fees_increment').addEventListener('input', function() {
    updateSalaryPreview();
});
document.getElementById('late_fees_increment').addEventListener('input', function() {
    updateSalaryPreview();
});
document.getElementById('early_exit_fees_increment').addEventListener('input', function() {
    updateSalaryPreview();
});

// Update salary preview
function updateSalaryPreview() {
    const amount = parseFloat(document.getElementById('increase').value || 0);
    const absentFeesIncrementInput = document.getElementById('absent_fees_increment');
    const lateFeesIncrementInput = document.getElementById('late_fees_increment');
    const earlyExitFeesIncrementInput = document.getElementById('early_exit_fees_increment');
    
    const absentFeesIncrement = absentFeesIncrementInput && absentFeesIncrementInput.value ? parseFloat(absentFeesIncrementInput.value) : null;
    const lateFeesIncrement = lateFeesIncrementInput && lateFeesIncrementInput.value ? parseFloat(lateFeesIncrementInput.value) : null;
    const earlyExitFeesIncrement = earlyExitFeesIncrementInput && earlyExitFeesIncrementInput.value ? parseFloat(earlyExitFeesIncrementInput.value) : null;
    
    const checkboxes = document.querySelectorAll('.staff-checkbox:checked');
    
    if (amount > 0) {
        checkboxes.forEach(checkbox => {
            const staffId = checkbox.value;
            const staffCard = checkbox.closest('.card');
            const currentSalaryText = staffCard.querySelector('.text-success');
            const currentSalary = parseFloat(currentSalaryText.textContent.replace('₹', '').replace(',', ''));
            const newSalary = currentSalary + amount;
            const previewDiv = document.getElementById(`new_salary_${staffId}`);
            if (previewDiv) {
                previewDiv.textContent = `→ ₹${newSalary.toFixed(2)}`;
                previewDiv.style.display = 'block';
            }
            
            // Update fees preview - only show if manual increment amount is provided
            const absentFeesSpan = document.getElementById(`new_absent_${staffId}`);
            if (absentFeesSpan && absentFeesIncrement !== null && absentFeesIncrement > 0) {
                const absentParent = absentFeesSpan.parentElement;
                const originalAbsent = parseFloat(absentParent.getAttribute('data-original-absent') || 0);
                if (originalAbsent > 0) {
                    const newAbsent = originalAbsent + absentFeesIncrement;
                    absentFeesSpan.textContent = ` → ₹${newAbsent.toFixed(2)}`;
                    absentFeesSpan.style.display = 'inline';
                }
            } else if (absentFeesSpan) {
                absentFeesSpan.style.display = 'none';
                absentFeesSpan.textContent = '';
            }
            
            const lateFeesSpan = document.getElementById(`new_late_${staffId}`);
            if (lateFeesSpan && lateFeesIncrement !== null && lateFeesIncrement > 0) {
                const lateParent = lateFeesSpan.parentElement;
                const originalLate = parseFloat(lateParent.getAttribute('data-original-late') || 0);
                if (originalLate > 0) {
                    const newLate = originalLate + lateFeesIncrement;
                    lateFeesSpan.textContent = ` → ₹${newLate.toFixed(2)}`;
                    lateFeesSpan.style.display = 'inline';
                }
            } else if (lateFeesSpan) {
                lateFeesSpan.style.display = 'none';
                lateFeesSpan.textContent = '';
            }
            
            const earlyExitFeesSpan = document.getElementById(`new_early_exit_${staffId}`);
            if (earlyExitFeesSpan && earlyExitFeesIncrement !== null && earlyExitFeesIncrement > 0) {
                const earlyExitParent = earlyExitFeesSpan.parentElement;
                const originalEarlyExit = parseFloat(earlyExitParent.getAttribute('data-original-early-exit') || 0);
                if (originalEarlyExit > 0) {
                    const newEarlyExit = originalEarlyExit + earlyExitFeesIncrement;
                    earlyExitFeesSpan.textContent = ` → ₹${newEarlyExit.toFixed(2)}`;
                    earlyExitFeesSpan.style.display = 'inline';
                }
            } else if (earlyExitFeesSpan) {
                earlyExitFeesSpan.style.display = 'none';
                earlyExitFeesSpan.textContent = '';
            }
        });
    } else {
        document.querySelectorAll('[id^="new_salary_"]').forEach(div => {
            div.style.display = 'none';
        });
        document.querySelectorAll('[id^="new_absent_"], [id^="new_late_"], [id^="new_early_exit_"]').forEach(span => {
            span.style.display = 'none';
            span.textContent = '';
        });
    }
}

// Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Validate form before submit
function validateForm() {
    const selectedStaff = document.querySelectorAll('.staff-checkbox:checked');
    if (selectedStaff.length === 0) {
        alert('Please select at least one staff member.');
        return false;
    }
    return true;
}
</script>
@endsection
