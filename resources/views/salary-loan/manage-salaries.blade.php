@extends('layouts.app')

@section('title', 'Manage Salaries')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Manage Salaries</h4>
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
                </div>

                <!-- Right Side -->
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <!-- Export Buttons -->
                    <div class="d-flex gap-2">
                        <a href="{{ route('salary-loan.manage-salaries.export', ['format' => 'excel']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn excel-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                            <span>Excel</span>
                        </a>
                        <a href="{{ route('salary-loan.manage-salaries.export', ['format' => 'pdf']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn pdf-btn" target="_blank">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">picture_as_pdf</span>
                            <span>PDF</span>
                        </a>
                        <button type="button" class="btn btn-sm px-2 py-1 export-btn print-btn" onclick="printTable()">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">print</span>
                            <span>Print</span>
                        </button>
                    </div>
                    
                    <!-- Search -->
                    <div class="d-flex align-items-center gap-2">
                        <label for="searchInput" class="mb-0 fs-13 fw-medium text-dark">Search:</label>
                        <div class="input-group input-group-sm search-input-group" style="width: 280px;">
                            <span class="input-group-text bg-light border-end-0" style="background-color: #f0f4ff !important; border-color: #e0e7ff; padding: 4px 8px;">
                                <span class="material-symbols-outlined" style="font-size: 14px; color: #003471;">search</span>
                            </span>
                            <input type="text" id="searchInput" class="form-control border-start-0 border-end-0" placeholder="Search salaries..." value="{{ request('search') }}" onkeypress="handleSearchKeyPress(event)" oninput="handleSearchInput(event)" style="padding: 4px 8px; font-size: 13px;">
                            @if(request('search'))
                                <button class="btn btn-outline-secondary border-start-0 border-end-0" type="button" onclick="clearSearch()" title="Clear search" style="padding: 4px 8px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">close</span>
                                </button>
                            @endif
                            <button class="btn btn-sm search-btn" type="button" onclick="performSearch()" title="Search" style="padding: 4px 10px;">
                                <span class="material-symbols-outlined" style="font-size: 14px;">search</span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Table Header -->
            <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                    <span class="material-symbols-outlined" style="font-size: 18px;">list</span>
                    <span>Salaries List</span>
                </h5>
            </div>

            <!-- Search Results Info -->
            @if(request('search'))
                <div class="search-results-info">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; color: #003471;">search</span>
                    <strong>Search Results:</strong> Showing results for "<strong>{{ request('search') }}</strong>"
                    @if(isset($salaries))
                        ({{ $salaries->total() }} {{ Str::plural('result', $salaries->total()) }} found)
                    @endif
                    <a href="{{ route('salary-loan.manage-salaries') }}" class="text-decoration-none ms-2" style="color: #003471;">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">close</span>
                        Clear
                    </a>
                </div>
            @endif

            <div class="default-table-area" style="margin-top: 0;">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Photo</th>
                                <th>Name</th>
                                <th>Salary Month</th>
                                <th>Present</th>
                                <th>Absent</th>
                                <th>Late</th>
                                <th>Basic</th>
                                <th>Salary Generated</th>
                                <th>Amount Paid</th>
                                <th>Loan Repayment</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($salaries) && $salaries->count() > 0)
                                @forelse($salaries as $salary)
                                    <tr>
                                        <td>{{ $loop->iteration + (($salaries->currentPage() - 1) * $salaries->perPage()) }}</td>
                                        <td>
                                            @if($salary->staff && $salary->staff->photo)
                                                <img src="{{ asset('storage/' . $salary->staff->photo) }}" alt="Photo" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                            @else
                                                <div style="width: 40px; height: 40px; border-radius: 50%; background-color: #e0e7ff; display: flex; align-items: center; justify-content: center;">
                                                    <span class="material-symbols-outlined" style="font-size: 20px; color: #003471;">person</span>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <strong class="text-primary">{{ $salary->staff->name ?? 'N/A' }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-info text-white">{{ $salary->salary_month }} {{ $salary->year }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-success text-white">{{ $salary->present }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-danger text-white">{{ $salary->absent }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-warning text-dark">{{ $salary->late }}</span>
                                        </td>
                                        <td>
                                            <strong class="text-primary">₹{{ number_format($salary->basic, 2) }}</strong>
                                        </td>
                                        <td>
                                            <strong class="text-success">₹{{ number_format($salary->salary_generated, 2) }}</strong>
                                        </td>
                                        <td>
                                            <strong class="text-info">₹{{ number_format($salary->amount_paid, 2) }}</strong>
                                        </td>
                                        <td>
                                            <strong class="text-warning">₹{{ number_format($salary->loan_repayment, 2) }}</strong>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <button type="button" class="btn btn-sm btn-success px-2 py-0" title="Make Payment" onclick="openPaymentModal({{ $salary->id }})">
                                                    <span class="material-symbols-outlined" style="font-size: 14px; color: white;">payments</span>
                                                </button>
                                                <div class="dropdown">
                                                    <button class="btn btn-sm btn-secondary px-2 py-0 dropdown-toggle" type="button" id="statusDropdown{{ $salary->id }}" data-bs-toggle="dropdown" aria-expanded="false" title="Status">
                                                        <span class="material-symbols-outlined" style="font-size: 14px; color: white;">more_vert</span>
                                                    </button>
                                                    <ul class="dropdown-menu" aria-labelledby="statusDropdown{{ $salary->id }}">
                                                        <li>
                                                            <form action="{{ route('salary-loan.manage-salaries.status', $salary->id) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                @method('PUT')
                                                                <input type="hidden" name="status" value="Pending">
                                                                <button type="submit" class="dropdown-item {{ $salary->status == 'Pending' ? 'active' : '' }}">Pending</button>
                                                            </form>
                                                        </li>
                                                        <li>
                                                            <form action="{{ route('salary-loan.manage-salaries.status', $salary->id) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                @method('PUT')
                                                                <input type="hidden" name="status" value="Paid">
                                                                <button type="submit" class="dropdown-item {{ $salary->status == 'Paid' ? 'active' : '' }}">Paid</button>
                                                            </form>
                                                        </li>
                                                        <li>
                                                            <form action="{{ route('salary-loan.manage-salaries.status', $salary->id) }}" method="POST" class="d-inline">
                                                                @csrf
                                                                @method('PUT')
                                                                <input type="hidden" name="status" value="Partial">
                                                                <button type="submit" class="dropdown-item {{ $salary->status == 'Partial' ? 'active' : '' }}">Partial</button>
                                                            </form>
                                                        </li>
                                                    </ul>
                                                </div>
                                                <button type="button" class="btn btn-sm btn-danger px-2 py-0" title="Delete" onclick="if(confirm('Are you sure you want to delete this salary record?')) { document.getElementById('delete-form-{{ $salary->id }}').submit(); }">
                                                    <span class="material-symbols-outlined" style="font-size: 14px; color: white;">delete</span>
                                                </button>
                                                <form id="delete-form-{{ $salary->id }}" action="{{ route('salary-loan.manage-salaries.destroy', $salary->id) }}" method="POST" class="d-none">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="12" class="text-center text-muted py-5">
                                            <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                            <p class="mt-2 mb-0">No salaries found.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            @else
                                <tr>
                                    <td colspan="12" class="text-center text-muted py-5">
                                        <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                        <p class="mt-2 mb-0">No salaries found.</p>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            @if(isset($salaries) && $salaries->hasPages())
                <div class="mt-3">
                    {{ $salaries->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Make Payment Modal -->
<div class="modal fade" id="paymentModal" tabindex="-1" aria-labelledby="paymentModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <div class="modal-header" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border-radius: 12px 12px 0 0; border: none; padding: 20px;">
                <h5 class="modal-title fs-15 fw-semibold mb-0 d-flex align-items-center gap-2" id="paymentModalLabel" style="color: white !important;">
                    <span class="material-symbols-outlined" style="font-size: 20px; color: white !important;">payments</span>
                    <span style="color: white !important;">Make Payment</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="paymentForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4" style="background-color: #f8f9fa;">
                    <div class="row g-3">
                        <!-- Campus -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Campus</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">location_on</span>
                                </span>
                                <input type="text" class="form-control" id="payment_campus" name="campus" readonly style="background-color: #f8f9fa; cursor: not-allowed;">
                            </div>
                        </div>

                        <!-- Employee -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Employee</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">person</span>
                                </span>
                                <input type="text" class="form-control" id="payment_employee" name="employee" readonly style="background-color: #f8f9fa; cursor: not-allowed;">
                            </div>
                        </div>

                        <!-- Month -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Month</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">calendar_month</span>
                                </span>
                                <input type="text" class="form-control" id="payment_month" name="month" readonly style="background-color: #f8f9fa; cursor: not-allowed;">
                            </div>
                        </div>

                        <!-- Generated Salary -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Generated Salary</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">currency_rupee</span>
                                </span>
                                <input type="text" class="form-control" id="payment_generated_salary" name="generated_salary" readonly style="background-color: #f8f9fa; cursor: not-allowed;">
                            </div>
                        </div>

                        <!-- Amount Paid -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Amount Paid <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">payments</span>
                                </span>
                                <input type="number" class="form-control" id="payment_amount_paid" name="amount_paid" step="0.01" min="0" required>
                            </div>
                        </div>

                        <!-- Loan Repayment -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Loan Repayment</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">account_balance</span>
                                </span>
                                <input type="number" class="form-control" id="payment_loan_repayment" name="loan_repayment" step="0.01" min="0" value="0">
                            </div>
                        </div>

                        <!-- Bonus Title -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Bonus Title</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">stars</span>
                                </span>
                                <input type="text" class="form-control" id="payment_bonus_title" name="bonus_title" placeholder="Enter bonus title">
                            </div>
                        </div>

                        <!-- Bonus Amount -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Bonus Amount</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">currency_rupee</span>
                                </span>
                                <input type="number" class="form-control" id="payment_bonus_amount" name="bonus_amount" step="0.01" min="0" value="0">
                            </div>
                        </div>

                        <!-- Deduction Title -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Deduction Title</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">remove_circle</span>
                                </span>
                                <input type="text" class="form-control" id="payment_deduction_title" name="deduction_title" placeholder="Enter deduction title">
                            </div>
                        </div>

                        <!-- Deduction Amount -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Deduction Amount</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">currency_rupee</span>
                                </span>
                                <input type="number" class="form-control" id="payment_deduction_amount" name="deduction_amount" step="0.01" min="0" value="0">
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Payment Method <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">payment</span>
                                </span>
                                <select class="form-control" id="payment_method" name="payment_method" required>
                                    <option value="">Select Payment Method</option>
                                    <option value="Bank">Bank</option>
                                    <option value="Wallet">Wallet</option>
                                    <option value="Transfer">Transfer</option>
                                    <option value="Card">Card</option>
                                    <option value="Check">Check</option>
                                    <option value="Deposit">Deposit</option>
                                    <option value="Cash">Cash</option>
                                </select>
                            </div>
                        </div>

                        <!-- Fully Paid? -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Fully Paid?</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">check_circle</span>
                                </span>
                                <select class="form-control" id="payment_fully_paid" name="fully_paid">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                        </div>

                        <!-- Payment Date -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Payment Date <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">calendar_today</span>
                                </span>
                                <input type="date" class="form-control" id="payment_date" name="payment_date" required value="{{ date('Y-m-d') }}">
                            </div>
                        </div>

                        <!-- Notify Employee -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Notify Employee</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">notifications</span>
                                </span>
                                <select class="form-control" id="payment_notify_employee" name="notify_employee">
                                    <option value="0">No</option>
                                    <option value="1">Yes</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="background-color: #f8f9fa; border-top: 1px solid #dee2e6; border-radius: 0 0 12px 12px; padding: 15px 20px;">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" style="border-radius: 6px;">Cancel</button>
                    <button type="submit" class="btn btn-sm" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white; border: none; border-radius: 6px; padding: 6px 20px;">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
                        Save Payment
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .rounded-8 {
        border-radius: 8px;
    }
    
    /* Export Buttons Styling */
    .export-btn {
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        border-radius: 6px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        height: 32px;
        font-size: 13px;
    }
    
    .excel-btn {
        background-color: #28a745;
        color: white;
    }
    
    .excel-btn:hover {
        background-color: #218838;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
    }
    
    .pdf-btn {
        background-color: #dc3545;
        color: white;
    }
    
    .pdf-btn:hover {
        background-color: #c82333;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
    }
    
    .print-btn {
        background-color: #2196f3;
        color: white;
    }
    
    .print-btn:hover {
        background-color: #0b7dda;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(33, 150, 243, 0.3);
    }
    
    .export-btn:active {
        transform: translateY(0);
    }
    
    /* Search Input Group Styling */
    .search-input-group {
        border-radius: 8px;
        overflow: hidden;
        border: 1px solid #dee2e6;
        transition: all 0.3s ease;
        height: 32px;
    }
    
    .search-input-group:focus-within {
        border-color: #003471;
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
    }
    
    .search-input-group .form-control {
        border: none;
        font-size: 13px;
        height: 32px;
        line-height: 1.4;
    }
    
    .search-input-group .form-control:focus {
        box-shadow: none;
        border: none;
    }
    
    .search-input-group .input-group-text {
        height: 32px;
        padding: 4px 8px;
        display: flex;
        align-items: center;
    }
    
    .search-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        padding: 4px 10px;
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }
    
    .search-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(0, 52, 113, 0.3);
    }
    
    .search-btn:disabled {
        opacity: 0.7;
        cursor: not-allowed;
    }
    
    /* Search Results Info */
    .search-results-info {
        padding: 8px 12px;
        background-color: #e7f3ff;
        border-left: 3px solid #003471;
        border-radius: 4px;
        margin-bottom: 15px;
        font-size: 13px;
    }
    
    /* Table Compact Styling */
    .default-table-area table {
        margin-bottom: 0;
        border-spacing: 0;
        border-collapse: collapse;
        border: 1px solid #dee2e6;
    }
    
    .default-table-area table thead {
        border-bottom: 1px solid #dee2e6;
    }
    
    .default-table-area table thead th {
        padding: 5px 10px;
        font-size: 12px;
        font-weight: 600;
        vertical-align: middle;
        line-height: 1.3;
        height: 32px;
        white-space: nowrap;
        border: 1px solid #dee2e6;
        background-color: #f8f9fa;
    }
    
    .default-table-area table thead th:first-child {
        border-left: 1px solid #dee2e6;
    }
    
    .default-table-area table thead th:last-child {
        border-right: 1px solid #dee2e6;
    }
    
    .default-table-area table tbody td {
        padding: 5px 10px;
        font-size: 12px;
        vertical-align: middle;
        line-height: 1.4;
        border: 1px solid #dee2e6;
    }
    
    .default-table-area table tbody td:first-child {
        border-left: 1px solid #dee2e6;
    }
    
    .default-table-area table tbody td:last-child {
        border-right: 1px solid #dee2e6;
    }
    
    .default-table-area table tbody tr:last-child td {
        border-bottom: 1px solid #dee2e6;
    }
    
    .default-table-area table thead th:first-child,
    .default-table-area table tbody td:first-child {
        padding-left: 10px;
    }
    
    .default-table-area table thead th:last-child,
    .default-table-area table tbody td:last-child {
        padding-right: 10px;
    }
    
    .default-table-area table tbody tr {
        height: 36px;
    }
    
    .default-table-area table tbody tr:first-child td {
        border-top: none;
    }
    
    .default-table-area .table-responsive {
        padding: 0;
        margin-top: 0;
    }
    
    .default-table-area {
        margin-top: 0 !important;
    }
    
    .default-table-area table tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    .default-table-area .badge {
        font-size: 11px;
        padding: 3px 6px;
        font-weight: 500;
    }
    
    .default-table-area .material-symbols-outlined {
        font-size: 13px !important;
    }
    
    .default-table-area .btn-sm {
        font-size: 11px;
        line-height: 1.2;
        min-height: 26px;
    }
    
    .default-table-area .btn-sm .material-symbols-outlined {
        font-size: 14px !important;
        vertical-align: middle;
        color: white !important;
    }
    
    .default-table-area .btn-primary .material-symbols-outlined,
    .default-table-area .btn-danger .material-symbols-outlined,
    .default-table-area .btn-secondary .material-symbols-outlined {
        color: white !important;
    }
    
    .dropdown-item.active {
        background-color: #003471;
        color: white;
    }
</style>

<script>
// Search functionality
function performSearch() {
    const searchInput = document.getElementById('searchInput');
    const searchValue = searchInput.value.trim();
    const url = new URL(window.location.href);
    
    if (searchValue) {
        url.searchParams.set('search', searchValue);
    } else {
        url.searchParams.delete('search');
    }
    
    url.searchParams.set('page', '1');
    
    searchInput.disabled = true;
    const searchBtn = document.querySelector('.search-btn');
    if (searchBtn) {
        searchBtn.disabled = true;
        searchBtn.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span>';
    }
    
    window.location.href = url.toString();
}

function handleSearchKeyPress(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        performSearch();
    }
}

function handleSearchInput(event) {
    // Auto-clear if input is empty
}

function clearSearch() {
    const url = new URL(window.location.href);
    url.searchParams.delete('search');
    url.searchParams.set('page', '1');
    
    const searchInput = document.getElementById('searchInput');
    searchInput.disabled = true;
    
    window.location.href = url.toString();
}

// Update entries per page
function updateEntriesPerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

// Print table
function printTable() {
    const printContents = document.querySelector('.default-table-area').innerHTML;
    const originalContents = document.body.innerHTML;
    
    document.body.innerHTML = `
        <div style="padding: 20px;">
            <h3 style="text-align: center; margin-bottom: 20px; color: #003471;">Salaries List</h3>
            ${printContents}
        </div>
    `;
    
    window.print();
    document.body.innerHTML = originalContents;
    window.location.reload();
}

// Open Payment Modal
function openPaymentModal(salaryId) {
    // Fetch salary data
    fetch(`{{ url('/salary-loan/manage-salaries') }}/${salaryId}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
    .then(response => response.json())
    .then(data => {
        // Set form action
        document.getElementById('paymentForm').action = `{{ url('/salary-loan/manage-salaries') }}/${salaryId}/payment`;
        
        // Populate form fields
        document.getElementById('payment_campus').value = data.staff?.campus || 'N/A';
        document.getElementById('payment_employee').value = data.staff?.name || 'N/A';
        document.getElementById('payment_month').value = `${data.salary_month} ${data.year}`;
        document.getElementById('payment_generated_salary').value = `₹${parseFloat(data.salary_generated || 0).toFixed(2)}`;
        document.getElementById('payment_amount_paid').value = data.amount_paid || 0;
        document.getElementById('payment_loan_repayment').value = data.loan_repayment || 0;
        document.getElementById('payment_bonus_title').value = '';
        document.getElementById('payment_bonus_amount').value = 0;
        document.getElementById('payment_deduction_title').value = '';
        document.getElementById('payment_deduction_amount').value = 0;
        document.getElementById('payment_method').value = '';
        document.getElementById('payment_fully_paid').value = data.status === 'Paid' ? '1' : '0';
        document.getElementById('payment_date').value = new Date().toISOString().split('T')[0];
        document.getElementById('payment_notify_employee').value = '0';
        
        // Show modal
        new bootstrap.Modal(document.getElementById('paymentModal')).show();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error loading salary data');
    });
}
</script>
@endsection
