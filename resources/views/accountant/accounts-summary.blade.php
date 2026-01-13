@extends('layouts.accountant')

@section('title', 'Accounts Summary Reports')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Accounts Summary Reports</h4>
                <!-- Add Campus Button -->
                <button type="button" class="btn btn-sm px-3 py-1 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white; border: none;" data-bs-toggle="modal" data-bs-target="#addCampusModal">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">add</span>
                    <span>Add Campus</span>
                </button>
            </div>

            <!-- Filter Form -->
            <form action="{{ route('accountant.accounts-summary') }}" method="GET" id="filterForm">
                <div class="row g-2 mb-3 align-items-end">
                    <!-- Campus -->
                    <div class="col-md-2">
                        <label for="filter_campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                        <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px;">
                            <option value="">All Campuses</option>
                            @foreach($campuses as $campus)
                                <option value="{{ $campus->campus_name ?? $campus }}" data-campus-id="{{ $campus->id ?? '' }}" {{ $filterCampus == ($campus->campus_name ?? $campus) ? 'selected' : '' }}>{{ $campus->campus_name ?? $campus }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Type -->
                    <div class="col-md-2">
                        <label for="filter_type" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Type</label>
                        <select class="form-select form-select-sm" id="filter_type" name="filter_type" style="height: 32px;">
                            <option value="">All Types</option>
                            @if(isset($typeOptions))
                                @foreach($typeOptions as $key => $label)
                                    <option value="{{ $key }}" {{ $filterType == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            @else
                                <option value="day_by_day" {{ $filterType == 'day_by_day' ? 'selected' : '' }}>Day by Day</option>
                                <option value="month_by_month" {{ $filterType == 'month_by_month' ? 'selected' : '' }}>Month by Month</option>
                            @endif
                        </select>
                    </div>

                    <!-- Month -->
                    <div class="col-md-2">
                        <label for="filter_month" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Month</label>
                        <select class="form-select form-select-sm" id="filter_month" name="filter_month" style="height: 32px;">
                            <option value="">All Months</option>
                            @foreach($months as $monthValue => $monthName)
                                <option value="{{ $monthValue }}" {{ $filterMonth == $monthValue ? 'selected' : '' }}>{{ $monthName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Year -->
                    <div class="col-md-2">
                        <label for="filter_year" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Year</label>
                        <select class="form-select form-select-sm" id="filter_year" name="filter_year" style="height: 32px;">
                            <option value="">All Years</option>
                            @foreach($years as $year)
                                <option value="{{ $year }}" {{ $filterYear == $year ? 'selected' : '' }}>{{ $year }}</option>
                            @endforeach
                        </select>
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
            @if(request()->hasAny(['filter_campus', 'filter_type', 'filter_month', 'filter_year']))
            <div class="mt-3">
                <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 18px;">list</span>
                        <span>Accounts Summary Reports</span>
                    </h5>
                </div>

                <div class="default-table-area" style="margin-top: 0;">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Type</th>
                                    <th>Category</th>
                                    <th>Campus</th>
                                    @if($filterType == 'day_by_day')
                                    <th>Date</th>
                                    @endif
                                    <th>Month</th>
                                    <th>Year</th>
                                    <th>Total Amount</th>
                                    <th>Discount</th>
                                    <th>Net Amount</th>
                                    <th>Count</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($summaryRecords as $index => $record)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        @if($record['type'] == 'Income')
                                            <span class="badge bg-success">Income</span>
                                        @else
                                            <span class="badge bg-danger">Expense</span>
                                        @endif
                                    </td>
                                    <td>{{ $record['category'] }}</td>
                                    <td>{{ $record['campus'] }}</td>
                                    @if($filterType == 'day_by_day')
                                    <td>{{ $record['date'] ?? 'N/A' }}</td>
                                    @endif
                                    <td>{{ $record['month'] }}</td>
                                    <td>{{ $record['year'] }}</td>
                                    <td>{{ number_format($record['total_amount'], 2) }}</td>
                                    <td>{{ number_format($record['discount'], 2) }}</td>
                                    <td class="fw-semibold">
                                        @if($record['type'] == 'Income')
                                            <span class="text-success">{{ number_format($record['net_amount'], 2) }}</span>
                                        @else
                                            <span class="text-danger">{{ number_format($record['net_amount'], 2) }}</span>
                                        @endif
                                    </td>
                                    <td>{{ $record['count'] }}</td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="{{ $filterType == 'day_by_day' ? '11' : '10' }}" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <span class="material-symbols-outlined text-muted" style="font-size: 48px;">inbox</span>
                                            <p class="text-muted mt-2 mb-0">No records found</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if($summaryRecords->count() > 0)
                            <tfoot>
                                <tr class="fw-bold" style="background-color: #f8f9fa;">
                                    <td colspan="{{ $filterType == 'day_by_day' ? '7' : '6' }}" class="text-end">Total Income:</td>
                                    <td>{{ number_format($summaryRecords->where('type', 'Income')->sum('total_amount'), 2) }}</td>
                                    <td>{{ number_format($summaryRecords->where('type', 'Income')->sum('discount'), 2) }}</td>
                                    <td class="text-success">{{ number_format($summaryRecords->where('type', 'Income')->sum('net_amount'), 2) }}</td>
                                    <td>{{ $summaryRecords->where('type', 'Income')->sum('count') }}</td>
                                </tr>
                                <tr class="fw-bold" style="background-color: #f8f9fa;">
                                    <td colspan="{{ $filterType == 'day_by_day' ? '7' : '6' }}" class="text-end">Total Expense:</td>
                                    <td>{{ number_format($summaryRecords->where('type', 'Expense')->sum('total_amount'), 2) }}</td>
                                    <td>{{ number_format($summaryRecords->where('type', 'Expense')->sum('discount'), 2) }}</td>
                                    <td class="text-danger">{{ number_format($summaryRecords->where('type', 'Expense')->sum('net_amount'), 2) }}</td>
                                    <td>{{ $summaryRecords->where('type', 'Expense')->sum('count') }}</td>
                                </tr>
                                <tr class="fw-bold" style="background-color: #e3f2fd;">
                                    <td colspan="{{ $filterType == 'day_by_day' ? '7' : '6' }}" class="text-end">Net Balance:</td>
                                    <td>
                                        @php
                                            $totalIncome = $summaryRecords->where('type', 'Income')->sum('net_amount');
                                            $totalExpense = $summaryRecords->where('type', 'Expense')->sum('net_amount');
                                            $netBalance = $totalIncome - $totalExpense;
                                        @endphp
                                        {{ number_format($totalIncome - $totalExpense, 2) }}
                                    </td>
                                    <td></td>
                                    <td>
                                        @if($netBalance >= 0)
                                            <span class="text-success">+{{ number_format($netBalance, 2) }}</span>
                                        @else
                                            <span class="text-danger">{{ number_format($netBalance, 2) }}</span>
                                        @endif
                                    </td>
                                    <td></td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
            @else
            <div class="text-center py-5">
                <span class="material-symbols-outlined text-muted" style="font-size: 64px;">filter_alt</span>
                <p class="text-muted mt-3 mb-0">Please apply filters to view accounts summary</p>
            </div>
            @endif
        </div>
    </div>
</div>

<!-- Add Campus Modal -->
<div class="modal fade" id="addCampusModal" tabindex="-1" aria-labelledby="addCampusModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addCampusModalLabel">Add New Campus</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="addCampusForm">
                @csrf
                <div class="modal-body">
                    <div class="mb-3">
                        <label for="campus_name" class="form-label">Campus Name <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="campus_name" name="campus_name" required>
                        <div class="text-danger mt-1" id="campus_name_error"></div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white; border: none;">Add Campus</button>
                </div>
            </form>
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

.default-table-area tbody td {
    font-size: 13px;
    padding: 12px;
    vertical-align: middle;
}

.default-table-area tbody tr:hover {
    background-color: #f8f9fa;
}

.badge {
    font-size: 11px;
    padding: 4px 8px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // Add Campus Form Submission
    document.getElementById('addCampusForm').addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const campusName = formData.get('campus_name');
        const errorDiv = document.getElementById('campus_name_error');
        errorDiv.textContent = '';
        
        fetch('{{ route("accountant.accounts-summary.campus.store") }}', {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                'Accept': 'application/json',
            },
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                // Add new option to select dropdown
                const select = document.getElementById('filter_campus');
                const option = document.createElement('option');
                option.value = data.campus.campus_name;
                option.setAttribute('data-campus-id', data.campus.id);
                option.textContent = data.campus.campus_name;
                select.appendChild(option);
                
                // Close modal and reset form
                const modal = bootstrap.Modal.getInstance(document.getElementById('addCampusModal'));
                modal.hide();
                this.reset();
                
                // Show success message
                alert('Campus added successfully!');
            } else {
                if (data.errors && data.errors.campus_name) {
                    errorDiv.textContent = data.errors.campus_name[0];
                } else {
                    errorDiv.textContent = data.message || 'Error adding campus';
                }
            }
        })
        .catch(error => {
            console.error('Error:', error);
            errorDiv.textContent = 'An error occurred. Please try again.';
        });
    });
    
    // Delete Campus functionality
    const campusSelect = document.getElementById('filter_campus');
    campusSelect.addEventListener('change', function() {
        const selectedOption = this.options[this.selectedIndex];
        const campusId = selectedOption.getAttribute('data-campus-id');
        
        // Remove any existing delete button
        const existingBtn = document.querySelector('.delete-campus-btn');
        if (existingBtn) {
            existingBtn.remove();
        }
        
        // Add delete button if a campus is selected and has an ID
        if (campusId && this.value) {
            const deleteBtn = document.createElement('button');
            deleteBtn.type = 'button';
            deleteBtn.className = 'btn btn-sm btn-danger delete-campus-btn ms-2';
            deleteBtn.innerHTML = '<span class="material-symbols-outlined" style="font-size: 14px;">delete</span>';
            deleteBtn.title = 'Delete Campus';
            deleteBtn.onclick = function() {
                if (confirm('Are you sure you want to delete this campus?')) {
                    fetch(`{{ route("accountant.accounts-summary.campus.destroy", ":id") }}`.replace(':id', campusId), {
                        method: 'DELETE',
                        headers: {
                            'X-CSRF-TOKEN': '{{ csrf_token() }}',
                            'Accept': 'application/json',
                        }
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            // Remove option from select
                            selectedOption.remove();
                            campusSelect.value = '';
                            deleteBtn.remove();
                            alert('Campus deleted successfully!');
                        } else {
                            alert(data.message || 'Error deleting campus');
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred. Please try again.');
                    });
                }
            };
            
            // Insert delete button after the select
            this.parentElement.appendChild(deleteBtn);
        }
    });
    
    // Trigger change event on page load if a campus is selected
    if (campusSelect.value) {
        campusSelect.dispatchEvent(new Event('change'));
    }
});
</script>
@endsection
