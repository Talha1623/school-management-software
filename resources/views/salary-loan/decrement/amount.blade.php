@extends('layouts.app')

@section('title', 'Decrement By Amount')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Decrement By Amount</h4>
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

            <form action="{{ route('salary-loan.decrement.amount.store') }}" method="POST" id="decrementAmountForm" onsubmit="return validateForm()">
                @csrf
                
                <div class="row g-3">
                    <!-- Campus -->
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-3 mb-2">
                            <h5 class="mb-2 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -12px -12px 12px -12px; background-color: #003471;">Campus</h5>
                            
                            <label for="campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm decrement-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">location_on</span>
                                </span>
                                <select class="form-control decrement-input" name="campus" id="campus" required onchange="loadStaffByCampus()" style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0; height: 36px;">
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        <option value="{{ $campus }}">{{ $campus }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Decrease -->
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-3 mb-2">
                            <h5 class="mb-2 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -12px -12px 12px -12px; background-color: #003471;">Decrease</h5>
                            
                            <label for="decrease" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Decrease (Amount) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm decrement-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">currency_rupee</span>
                                </span>
                                <input type="number" class="form-control decrement-input" name="decrease" id="decrease" placeholder="Enter amount" step="0.01" min="0" required style="height: 36px;">
                            </div>
                        </div>
                    </div>

                    <!-- Accountant -->
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-3 mb-2">
                            <h5 class="mb-2 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -12px -12px 12px -12px; background-color: #003471;">Accountant</h5>
                            
                            <label for="accountant" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Accountant <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm decrement-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">person</span>
                                </span>
                                <input type="text" class="form-control decrement-input" name="accountant" id="accountant" value="{{ $loggedInUserName }}" required readonly style="background-color: #f8f9fa; cursor: not-allowed; border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0; height: 36px;">
                            </div>
                        </div>
                    </div>

                    <!-- Date -->
                    <div class="col-md-6">
                        <div class="card bg-light border-0 rounded-10 p-3 mb-2">
                            <h5 class="mb-2 py-2 px-3 text-white rounded-3 fw-semibold fs-15" style="margin: -12px -12px 12px -12px; background-color: #003471;">Date</h5>
                            
                            <label for="date" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Date <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm decrement-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">calendar_today</span>
                                </span>
                                <input type="date" class="form-control decrement-input" name="date" id="date" value="{{ $currentDate }}" required style="height: 36px;">
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
                            <button type="submit" class="btn btn-sm py-2 px-4 rounded-8 decrement-submit-btn">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
                                Save Decrement
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Decrement Form Styling */
    .decrement-input-group {
        height: 36px;
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s ease;
        border: 1px solid #dee2e6;
    }
    
    .decrement-input-group:focus-within {
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
        border-color: #003471;
    }
    
    .decrement-input {
        height: 36px;
        font-size: 13px;
        padding: 0.5rem 0.75rem;
        border: none;
        border-left: 1px solid #e0e7ff;
        border-radius: 0;
        transition: all 0.3s ease;
    }
    
    .decrement-input:focus {
        border-left-color: #003471;
        box-shadow: none;
        outline: none;
    }
    
    .decrement-input-group .input-group-text {
        height: 36px;
        padding: 0 0.75rem;
        display: flex;
        align-items: center;
        border: none;
        border-right: 1px solid #e0e7ff;
        border-radius: 8px 0 0 8px;
        transition: all 0.3s ease;
    }
    
    .decrement-input-group:focus-within .input-group-text {
        background-color: #003471 !important;
        color: white !important;
        border-right-color: #003471;
    }
    
    .decrement-input-group select.decrement-input {
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
    
    .decrement-submit-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 52, 113, 0.25);
    }
    
    .decrement-submit-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 52, 113, 0.35);
        color: white;
    }
    
    .decrement-submit-btn:active {
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
// Reset form
function resetForm() {
    document.getElementById('decrementAmountForm').reset();
    // Set current date as default
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('date').value = today;
    // Set logged in user name
    document.getElementById('accountant').value = '{{ $loggedInUserName }}';
    // Clear staff list
    document.getElementById('staffListContainer').style.display = 'none';
    document.getElementById('selectCampusMessage').style.display = 'block';
    document.getElementById('staffList').innerHTML = '';
    document.getElementById('selectAllStaff').checked = false;
}

// Load staff by campus
function loadStaffByCampus() {
    const campus = document.getElementById('campus').value;
    const staffListContainer = document.getElementById('staffListContainer');
    const selectCampusMessage = document.getElementById('selectCampusMessage');
    const staffList = document.getElementById('staffList');
    const noStaffMessage = document.getElementById('noStaffMessage');
    
    if (!campus) {
        staffListContainer.style.display = 'none';
        selectCampusMessage.style.display = 'block';
        staffList.innerHTML = '';
        document.getElementById('selectAllStaff').checked = false;
        return;
    }
    
    // Show loading
    staffList.innerHTML = '<div class="text-center py-4"><div class="spinner-border text-primary" role="status"><span class="visually-hidden">Loading...</span></div></div>';
    staffListContainer.style.display = 'block';
    selectCampusMessage.style.display = 'none';
    noStaffMessage.style.display = 'none';
    
    fetch(`{{ route('salary-loan.decrement.amount.get-staff-by-campus') }}?campus=${encodeURIComponent(campus)}`, {
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
                html += `
                    <div class="col-md-6 col-lg-4">
                        <div class="card border p-2 mb-2" style="border-radius: 8px;">
                            <div class="form-check">
                                <input class="form-check-input staff-checkbox" type="checkbox" name="selected_staff[]" value="${staff.id}" id="staff_${staff.id}" onchange="updateSelectAllState()" style="width: 18px; height: 18px; cursor: pointer;">
                                <label class="form-check-label w-100" for="staff_${staff.id}" style="cursor: pointer;">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div>
                                            <strong style="color: #003471; font-size: 13px;">${escapeHtml(staff.name)}</strong>
                                            ${staff.emp_id ? `<br><small class="text-muted" style="font-size: 11px;">ID: ${escapeHtml(staff.emp_id)}</small>` : ''}
                                            ${staff.designation ? `<br><small class="text-muted" style="font-size: 11px;">${escapeHtml(staff.designation)}</small>` : ''}
                                        </div>
                                        <div class="text-end">
                                            <small class="text-success fw-bold" style="font-size: 12px;">₹${currentSalary.toFixed(2)}</small>
                                            <div id="new_salary_${staff.id}" class="text-danger fw-bold" style="font-size: 11px; display: none;"></div>
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
document.getElementById('decrease').addEventListener('input', function() {
    updateSalaryPreview();
});

// Update salary preview
function updateSalaryPreview() {
    const amount = parseFloat(document.getElementById('decrease').value || 0);
    const checkboxes = document.querySelectorAll('.staff-checkbox:checked');
    
    if (amount > 0) {
        checkboxes.forEach(checkbox => {
            const staffId = checkbox.value;
            const staffCard = checkbox.closest('.card');
            const currentSalaryText = staffCard.querySelector('.text-success');
            const currentSalary = parseFloat(currentSalaryText.textContent.replace('₹', '').replace(',', ''));
            const newSalary = Math.max(0, currentSalary - amount);
            const previewDiv = document.getElementById(`new_salary_${staffId}`);
            if (previewDiv) {
                previewDiv.textContent = `→ ₹${newSalary.toFixed(2)}`;
                previewDiv.style.display = 'block';
            }
        });
    } else {
        document.querySelectorAll('[id^="new_salary_"]').forEach(div => {
            div.style.display = 'none';
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
