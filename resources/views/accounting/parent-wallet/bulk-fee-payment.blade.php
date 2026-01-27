@extends('layouts.app')

@section('title', 'Bulk Fee Payment')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Bulk Fee Payment</h4>
            </div>

            <!-- Filters -->
            <div class="row g-2 mb-3 align-items-end">
                <div class="col-md-3">
                    <label for="filter_campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                    <select class="form-select form-select-sm" id="filter_campus" style="height: 32px;">
                        <option value="">All Campuses</option>
                        @foreach($campuses as $campus)
                            <option value="{{ $campus }}">{{ $campus }}</option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter_class" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class</label>
                    <select class="form-select form-select-sm" id="filter_class" style="height: 32px;" disabled>
                        <option value="">Select Campus First</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter_section" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Section</label>
                    <select class="form-select form-select-sm" id="filter_section" style="height: 32px;" disabled>
                        <option value="">Select Class First</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <label for="filter_fee_type" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Fee Type</label>
                    <select class="form-select form-select-sm" id="filter_fee_type" style="height: 32px;">
                        <option value="">All</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-sm py-1 px-3 rounded-8 filter-btn w-100" style="height: 32px;" onclick="loadBulkFees()">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">filter_alt</span>
                        <span style="font-size: 12px;">Filter</span>
                    </button>
                </div>
            </div>

            <div class="default-table-area" style="margin-top: 0;">
                <div class="table-responsive">
                    <table class="table table-sm table-hover" id="bulkFeeTable">
                        <thead>
                            <tr>
                                <th>Student Code</th>
                                <th>Name</th>
                                <th>Parent</th>
                                <th>Title</th>
                                <th>Amount</th>
                                <th>Late Fee</th>
                                <th>Total Due</th>
                                <th>Payment</th>
                                <th>Discount</th>
                                <th>Date</th>
                                <th>Fully Paid?</th>
                            </tr>
                        </thead>
                        <tbody id="bulkFeeBody">
                            <tr>
                                <td colspan="11" class="text-center text-muted">Apply filters to load fees.</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
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

.default-table-area table tbody td {
    padding: 8px 12px;
    font-size: 13px;
    vertical-align: middle;
    line-height: 1.4;
    border: 1px solid #dee2e6;
}

.bulk-input {
    height: 28px;
    font-size: 12px;
    padding: 2px 6px;
}

.bulk-select {
    height: 28px;
    font-size: 12px;
    padding: 2px 6px;
}

.total-due-input {
    background-color: #f1f3f5;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const campusSelect = document.getElementById('filter_campus');
    const classSelect = document.getElementById('filter_class');
    const sectionSelect = document.getElementById('filter_section');

    function resetClassesAndSections() {
        classSelect.innerHTML = '<option value="">Select Campus First</option>';
        classSelect.disabled = true;
        sectionSelect.innerHTML = '<option value="">Select Class First</option>';
        sectionSelect.disabled = true;
    }

    function loadClassesForCampus() {
        const campus = campusSelect.value;

        resetClassesAndSections();

        if (!campus) {
            return;
        }

        classSelect.innerHTML = '<option value="">Loading classes...</option>';

        fetch(`{{ route('accounting.get-classes-by-campus') }}?campus=${encodeURIComponent(campus)}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            classSelect.innerHTML = '<option value="">All Classes</option>';
            if (data.classes && data.classes.length > 0) {
                data.classes.forEach(className => {
                    const option = document.createElement('option');
                    option.value = className;
                    option.textContent = className;
                    classSelect.appendChild(option);
                });
                classSelect.disabled = false;
            } else {
                classSelect.innerHTML = '<option value="">No classes found</option>';
            }
        })
        .catch(() => {
            classSelect.innerHTML = '<option value="">Error loading classes</option>';
        });
    }

    campusSelect.addEventListener('change', loadClassesForCampus);

    classSelect.addEventListener('change', function() {
        const selectedClass = this.value;
        const selectedCampus = campusSelect.value;
        sectionSelect.innerHTML = '<option value="">Select Class First</option>';
        sectionSelect.disabled = true;
        if (!selectedClass) {
            return;
        }

        const params = new URLSearchParams({
            class: selectedClass,
            campus: selectedCampus || ''
        });
        fetch(`{{ route('accounting.get-sections-by-class') }}?${params.toString()}`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
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
            }
        })
        .catch(() => {
            sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
            sectionSelect.disabled = true;
        });
    });
});

function loadBulkFees() {
    const params = new URLSearchParams({
        campus: document.getElementById('filter_campus').value || '',
        class: document.getElementById('filter_class').value || '',
        section: document.getElementById('filter_section').value || '',
        fee_type: document.getElementById('filter_fee_type').value || ''
    });

    const tbody = document.getElementById('bulkFeeBody');
    tbody.innerHTML = '<tr><td colspan="11" class="text-center text-muted">Loading...</td></tr>';

    fetch(`{{ route('accounting.parent-wallet.bulk-fee-payment.data') }}?${params.toString()}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        renderBulkFees(data.items || []);
    })
    .catch(() => {
        tbody.innerHTML = '<tr><td colspan="11" class="text-center text-danger">Error loading data</td></tr>';
    });
}

function renderBulkFees(items) {
    const tbody = document.getElementById('bulkFeeBody');
    tbody.innerHTML = '';

    if (!items.length) {
        tbody.innerHTML = '<tr><td colspan="11" class="text-center text-muted">No records found.</td></tr>';
        return;
    }

    items.forEach(item => {
        const row = document.createElement('tr');
        row.innerHTML = `
            <td>${escapeHtml(item.student_code)}</td>
            <td class="text-danger fw-semibold">${escapeHtml(item.student_name)}</td>
            <td>${escapeHtml(item.parent_name)}</td>
            <td>${escapeHtml(item.payment_title)}</td>
            <td><input type="number" class="form-control form-control-sm bulk-input" value="${formatNumber(item.amount)}" disabled></td>
            <td><input type="number" class="form-control form-control-sm bulk-input" value="${formatNumber(item.late_fee)}"></td>
            <td><input type="number" class="form-control form-control-sm bulk-input total-due-input" value="${formatNumber(item.total_due)}" readonly></td>
            <td><input type="number" class="form-control form-control-sm bulk-input payment-input" value="${formatNumber(item.payment)}"></td>
            <td><input type="number" class="form-control form-control-sm bulk-input discount-input" value="${formatNumber(item.discount)}"></td>
            <td><input type="date" class="form-control form-control-sm bulk-input" value="${item.payment_date}"></td>
            <td>
                <select class="form-select form-select-sm bulk-select fully-paid-select">
                    <option value="Yes" ${item.fully_paid === 'Yes' ? 'selected' : ''}>Yes</option>
                    <option value="No" ${item.fully_paid === 'No' ? 'selected' : ''}>No</option>
                </select>
            </td>
        `;
        tbody.appendChild(row);
    });
}

function formatNumber(value) {
    const num = parseFloat(value);
    return Number.isNaN(num) ? '0' : num;
}

function escapeHtml(value) {
    return String(value ?? '')
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}

document.addEventListener('input', function(event) {
    const row = event.target.closest('tr');
    if (!row) {
        return;
    }

    if (
        !event.target.classList.contains('payment-input') &&
        !event.target.classList.contains('discount-input') &&
        !event.target.classList.contains('bulk-input')
    ) {
        return;
    }

    const amountInput = row.querySelector('input[disabled]');
    const lateFeeInput = row.querySelector('td:nth-child(6) input');
    const totalDueInput = row.querySelector('.total-due-input');
    const paymentInput = row.querySelector('.payment-input');
    const discountInput = row.querySelector('.discount-input');
    const fullyPaidSelect = row.querySelector('.fully-paid-select');

    const amount = parseFloat(amountInput?.value || 0) || 0;
    const lateFee = parseFloat(lateFeeInput?.value || 0) || 0;
    const payment = parseFloat(paymentInput?.value || 0) || 0;
    const discount = parseFloat(discountInput?.value || 0) || 0;

    const totalDue = Math.max(amount + lateFee - payment - discount, 0);
    totalDueInput.value = totalDue.toFixed(2);

    if (fullyPaidSelect) {
        fullyPaidSelect.value = totalDue <= 0 ? 'Yes' : 'No';
    }
});
</script>
@endsection

