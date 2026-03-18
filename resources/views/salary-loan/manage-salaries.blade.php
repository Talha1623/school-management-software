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
                        <label for="campusFilter" class="mb-0 fs-13 fw-medium text-dark">Campus:</label>
                        <select id="campusFilter" class="form-select form-select-sm" style="width: auto; min-width: 150px;" onchange="filterByCampus(this.value)">
                            <option value="">All Campuses</option>
                            @if(isset($campuses) && $campuses->count() > 0)
                                @foreach($campuses as $campus)
                                    <option value="{{ $campus->campus_name ?? $campus }}" {{ request('campus') == ($campus->campus_name ?? $campus) ? 'selected' : '' }}>
                                        {{ $campus->campus_name ?? $campus }}
                                    </option>
                                @endforeach
                            @endif
                        </select>
                    </div>
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
                        @php
                            $exportParams = '';
                            if (request('campus')) {
                                $exportParams .= (strpos($exportParams, '?') === false ? '?' : '&') . 'campus=' . urlencode(request('campus'));
                            }
                            if (request('search')) {
                                $exportParams .= (strpos($exportParams, '?') === false ? '?' : '&') . 'search=' . urlencode(request('search'));
                            }
                        @endphp
                        <a href="{{ route('salary-loan.manage-salaries.export', ['format' => 'excel']) }}{{ $exportParams }}" class="btn btn-sm px-2 py-1 export-btn excel-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                            <span>Excel</span>
                        </a>
                        <a href="{{ route('salary-loan.manage-salaries.export', ['format' => 'pdf']) }}{{ $exportParams }}" class="btn btn-sm px-2 py-1 export-btn pdf-btn" target="_blank">
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

            <!-- Filter Results Info -->
            @if(request('search') || request('campus'))
                <div class="search-results-info">
                    @if(request('search'))
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; color: #003471;">search</span>
                        <strong>Search:</strong> "<strong>{{ request('search') }}</strong>"
                    @endif
                    @if(request('campus'))
                        @if(request('search'))
                            <span class="mx-2">|</span>
                        @endif
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; color: #003471;">location_on</span>
                        <strong>Campus:</strong> "<strong>{{ request('campus') }}</strong>"
                    @endif
                    @if(isset($salaries))
                        <span class="mx-2">|</span>
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
                                <th>Early Exit</th>
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
                                            <span class="badge bg-danger text-white">{{ $salary->early_exit ?? 0 }}</span>
                                        </td>
                                        <td>
                                            <strong class="text-primary">{{ number_format($salary->basic, 2) }}</strong>
                                            <div>
                                                <span class="badge bg-light text-dark" style="font-size: 10px;">
                                                    {{ $salary->staff->salary_type ?? 'full time' }}
                                                </span>
                                            </div>
                                            @php
                                                $salaryType = strtolower(trim($salary->staff->salary_type ?? ''));
                                                $isPerHour = $salaryType === 'per hour';
                                            @endphp
                                            @if($isPerHour && isset($salary->total_hours))
                                                @php
                                                    $totalHours = $salary->total_hours ?? 0;
                                                @endphp
                                                <div style="margin-top: 4px; font-size: 11px; color: #003471; line-height: 1.6;">
                                                    <div><strong>Hours:</strong> {{ number_format($totalHours, 2) }} hrs</div>
                                                    <div><strong>Classes:</strong> {{ $salary->total_classes ?? 0 }}</div>
                                                    <div style="margin-top: 3px; padding: 4px 6px; background-color: #e8f5e9; border-radius: 4px; border-left: 3px solid #28a745;">
                                                        <strong style="color: #28a745; font-size: 10px;">
                                                            {{ number_format($salary->basic, 2) }} × {{ number_format($totalHours, 2) }}
                                                            <br>
                                                            = {{ number_format($salary->salary_generated, 2) }}
                                                        </strong>
                                                    </div>
                                                </div>
                                            @endif
                                        </td>
                                        <td>
                                            <strong class="text-success">{{ number_format($salary->salary_generated, 2) }}</strong>
                                        </td>
                                        <td>
                                            <strong class="text-info">{{ number_format($salary->amount_paid, 2) }}</strong>
                                        </td>
                                        <td>
                                            <strong class="text-warning">{{ number_format($salary->loan_repayment, 2) }}</strong>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                @if($salary->status != 'Paid')
                                                    <button type="button" class="btn btn-sm btn-primary px-2 py-0" title="Edit Salary" onclick="openEditModal({{ $salary->id }})">
                                                        <span class="material-symbols-outlined" style="font-size: 14px; color: white;">edit</span>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-success px-2 py-0" title="Make Payment" onclick="openPaymentModal({{ $salary->id }})">
                                                        <span class="material-symbols-outlined" style="font-size: 14px; color: white;">payments</span>
                                                    </button>
                                                @endif
                                                @if($salary->status == 'Pending')
                                                    <form action="{{ route('salary-loan.manage-salaries.status', $salary->id) }}" method="POST" class="d-inline">
                                                        @csrf
                                                        @method('PUT')
                                                        <input type="hidden" name="status" value="Paid">
                                                        <button type="submit" class="btn btn-sm px-2 py-0 btn-warning" title="Click to mark as Paid">
                                                            <span class="badge bg-warning text-dark" style="font-size: 11px; padding: 4px 8px; cursor: pointer;">Pending</span>
                                                        </button>
                                                    </form>
                                                @elseif($salary->status == 'Paid')
                                                    <button type="button" class="btn btn-sm px-2 py-0 btn-success" title="Print Receipt" onclick="printPaymentReceipt({{ $salary->id }})">
                                                        <span class="badge bg-success text-white" style="font-size: 11px; padding: 4px 8px; cursor: pointer;">Paid</span>
                                                    </button>
                                                @else
                                                    <span class="badge bg-info text-white" style="font-size: 11px; padding: 4px 8px;">Issued</span>
                                                @endif
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
                                        <td colspan="13" class="text-center text-muted py-5">
                                            <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                            <p class="mt-2 mb-0">No salaries found.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            @else
                                <tr>
                                    <td colspan="13" class="text-center text-muted py-5">
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

<!-- Edit Salary Modal -->
<div class="modal fade" id="editModal" tabindex="-1" aria-labelledby="editModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content" style="border-radius: 12px; border: none; box-shadow: 0 10px 40px rgba(0,0,0,0.2);">
            <div class="modal-header" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border-radius: 12px 12px 0 0; border: none; padding: 20px;">
                <h5 class="modal-title fs-15 fw-semibold mb-0 d-flex align-items-center gap-2" id="editModalLabel" style="color: white !important;">
                    <span class="material-symbols-outlined" style="font-size: 20px; color: white !important;">edit</span>
                    <span style="color: white !important;">Edit Salary</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form id="editForm" method="POST">
                @csrf
                @method('PUT')
                <div class="modal-body p-4" style="background-color: #f8f9fa;">
                    <div class="row g-3">
                        <!-- Employee Name (Readonly) -->
                        <div class="col-md-12">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Employee</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">person</span>
                                </span>
                                <input type="text" class="form-control" id="edit_employee" name="employee" readonly style="background-color: #f8f9fa; cursor: not-allowed; height: 38px;">
                            </div>
                        </div>

                        <!-- Present Days -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Present Days <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">check_circle</span>
                                </span>
                                <input type="number" class="form-control" id="edit_present" name="present" step="1" min="0" required style="height: 38px;" oninput="calculateEditSalary()">
                            </div>
                        </div>

                        <!-- Absent Days -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Absent Days <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">cancel</span>
                                </span>
                                <input type="number" class="form-control" id="edit_absent" name="absent" step="1" min="0" required style="height: 38px;" oninput="calculateEditSalary()">
                            </div>
                        </div>

                        <!-- Late Days -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Late Days <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">schedule</span>
                                </span>
                                <input type="number" class="form-control" id="edit_late" name="late" step="1" min="0" required style="height: 38px;" oninput="calculateEditSalary()">
                            </div>
                        </div>

                        <!-- Generated Salary -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Generated Salary <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">currency_rupee</span>
                                </span>
                                <input type="number" class="form-control" id="edit_salary_generated" name="salary_generated" step="0.01" min="0" required style="height: 38px;">
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer" style="background-color: #f8f9fa; border-top: 1px solid #dee2e6; border-radius: 0 0 12px 12px; padding: 15px 20px;">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal" style="border-radius: 6px;">Cancel</button>
                    <button type="submit" class="btn btn-sm" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white; border: none; border-radius: 6px; padding: 6px 20px;">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
                        Update Salary
                    </button>
                </div>
            </form>
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
                                <input type="text" class="form-control" id="payment_campus" name="campus" readonly style="background-color: #f8f9fa; cursor: not-allowed; height: 38px;">
                            </div>
                        </div>

                        <!-- Employee -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Employee</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">person</span>
                                </span>
                                <input type="text" class="form-control" id="payment_employee" name="employee" readonly style="background-color: #f8f9fa; cursor: not-allowed; height: 38px;">
                            </div>
                        </div>

                        <!-- Month -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Month</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">calendar_month</span>
                                </span>
                                <input type="text" class="form-control" id="payment_month" name="month" readonly style="background-color: #f8f9fa; cursor: not-allowed; height: 38px;">
                            </div>
                        </div>

                        <!-- Basic -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Basic</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">currency_rupee</span>
                                </span>
                                <input type="text" class="form-control" id="payment_basic" name="basic" readonly style="background-color: #f8f9fa; cursor: not-allowed; height: 38px;" data-basic="0">
                            </div>
                        </div>

                        <!-- Generated Salary -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Generated Salary</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">currency_rupee</span>
                                </span>
                                <input type="text" class="form-control" id="payment_generated_salary" name="generated_salary" readonly style="background-color: #f8f9fa; cursor: not-allowed; height: 38px;">
                            </div>
                        </div>

                        <!-- Amount Paid -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Amount Paid <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">payments</span>
                                </span>
                                <input type="number" class="form-control" id="payment_amount_paid" name="amount_paid" step="0.01" min="0" required style="height: 38px;">
                            </div>
                            <small id="amount_paid_note" class="text-muted" style="font-size: 10px; display: none;">Amount Paid cannot be edited once payment is made</small>
                        </div>

                        <!-- Loan Repayment -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Loan Repayment</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">account_balance</span>
                                </span>
                                <input type="number" class="form-control" id="payment_loan_repayment" name="loan_repayment" step="0.01" min="0" value="0" style="height: 38px;">
                            </div>
                        </div>

                        <!-- Bonus Title -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Bonus Title</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">stars</span>
                                </span>
                                <input type="text" class="form-control" id="payment_bonus_title" name="bonus_title" placeholder="Enter bonus title" style="height: 38px;">
                            </div>
                        </div>

                        <!-- Bonus Amount -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Bonus Amount</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">currency_rupee</span>
                                </span>
                                <input type="number" class="form-control" id="payment_bonus_amount" name="bonus_amount" step="0.01" min="0" value="0" style="height: 38px;" oninput="calculateGeneratedSalary()">
                            </div>
                        </div>

                        <!-- Deduction Title -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Deduction Title</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">remove_circle</span>
                                </span>
                                <input type="text" class="form-control" id="payment_deduction_title" name="deduction_title" placeholder="Enter deduction title" style="height: 38px;">
                            </div>
                        </div>

                        <!-- Deduction Amount -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Deduction Amount</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">currency_rupee</span>
                                </span>
                                <input type="number" class="form-control" id="payment_deduction_amount" name="deduction_amount" step="0.01" min="0" value="0" style="height: 38px;" oninput="calculateGeneratedSalary()">
                            </div>
                        </div>

                        <!-- Late Fees Deduction -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Late Fees Deduction</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">schedule</span>
                                </span>
                                <input type="text" class="form-control" id="payment_late_fees" name="late_fees" readonly style="background-color: #f8f9fa; cursor: not-allowed; height: 38px;">
                            </div>
                        </div>

                        <!-- Absent Fees Deduction -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Absent Fees Deduction</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">money_off</span>
                                </span>
                                <input type="text" class="form-control" id="payment_absent_fees" name="absent_fees" readonly style="background-color: #f8f9fa; cursor: not-allowed; height: 38px;">
                            </div>
                        </div>

                        <!-- Early Exit Fees Deduction -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Early Exit Fees Deduction</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">exit_to_app</span>
                                </span>
                                <input type="text" class="form-control" id="payment_early_exit_fees" name="early_exit_fees" readonly style="background-color: #f8f9fa; cursor: not-allowed; height: 38px;">
                            </div>
                        </div>

                        <!-- Payment Method -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Payment Method <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">payment</span>
                                </span>
                                <select class="form-control" id="payment_method" name="payment_method" required style="height: 38px;">
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
                                <select class="form-control" id="payment_fully_paid" name="fully_paid" style="height: 38px;">
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
                                <input type="date" class="form-control" id="payment_date" name="payment_date" required value="{{ date('Y-m-d') }}" style="height: 38px;">
                            </div>
                        </div>

                        <!-- Notify Employee -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Notify Employee</label>
                            <div class="input-group input-group-sm">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">notifications</span>
                                </span>
                                <select class="form-control" id="payment_notify_employee" name="notify_employee" style="height: 38px;">
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
// Filter by Campus
function filterByCampus(campus) {
    const url = new URL(window.location.href);
    
    if (campus) {
        url.searchParams.set('campus', campus);
    } else {
        url.searchParams.delete('campus');
    }
    
    url.searchParams.set('page', '1');
    
    window.location.href = url.toString();
}

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
    // Keep campus filter when clearing search
    
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

// Calculate Generated Salary dynamically
function calculateGeneratedSalary() {
    const generatedSalaryInput = document.getElementById('payment_generated_salary');
    // Get the base generated salary (already includes fees deductions)
    const baseGeneratedSalary = parseFloat(generatedSalaryInput.getAttribute('data-generated') || 0);
    const bonusAmount = parseFloat(document.getElementById('payment_bonus_amount').value || 0);
    const deductionAmount = parseFloat(document.getElementById('payment_deduction_amount').value || 0);
    
    // New generated salary = base + bonus - additional deduction
    const newGeneratedSalary = baseGeneratedSalary + bonusAmount - deductionAmount;
    generatedSalaryInput.value = `₹${Math.max(0, newGeneratedSalary).toFixed(2)}`;
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
        
        // Store basic salary and set initial values
        const basic = parseFloat(data.basic || 0);
        const basicInput = document.getElementById('payment_basic');
        basicInput.setAttribute('data-basic', basic);
        basicInput.value = `₹${basic.toFixed(2)}`;
        
        // Set generated salary from data and store as base
        const generatedSalary = parseFloat(data.salary_generated || 0);
        const generatedSalaryInput = document.getElementById('payment_generated_salary');
        generatedSalaryInput.setAttribute('data-generated', generatedSalary);
        generatedSalaryInput.value = `₹${generatedSalary.toFixed(2)}`;
        
        const amountPaid = parseFloat(data.amount_paid || 0);
        const amountPaidInput = document.getElementById('payment_amount_paid');
        const amountPaidNote = document.getElementById('amount_paid_note');
        
        // If amount_paid > 0, make it readonly/disabled
        if (amountPaid > 0) {
            amountPaidInput.value = amountPaid;
            amountPaidInput.setAttribute('readonly', 'readonly');
            amountPaidInput.style.backgroundColor = '#f8f9fa';
            amountPaidInput.style.cursor = 'not-allowed';
            amountPaidNote.style.display = 'block';
        } else {
            amountPaidInput.value = 0;
            amountPaidInput.removeAttribute('readonly');
            amountPaidInput.style.backgroundColor = '';
            amountPaidInput.style.cursor = '';
            amountPaidNote.style.display = 'none';
        }
        
        document.getElementById('payment_loan_repayment').value = data.loan_repayment || 0;
        document.getElementById('payment_bonus_title').value = '';
        document.getElementById('payment_bonus_amount').value = 0;
        document.getElementById('payment_deduction_title').value = '';
        document.getElementById('payment_deduction_amount').value = 0;
        document.getElementById('payment_method').value = '';
        document.getElementById('payment_fully_paid').value = data.status === 'Paid' ? '1' : '0';
        document.getElementById('payment_date').value = new Date().toISOString().split('T')[0];
        document.getElementById('payment_notify_employee').value = '0';
        
        // Populate fees fields dynamically
        if (data.fees) {
            const lateFees = parseFloat(data.fees.late_fees || 0);
            const absentFees = parseFloat(data.fees.absent_fees || 0);
            const earlyExitFees = parseFloat(data.fees.early_exit_fees || 0);
            
            document.getElementById('payment_late_fees').value = `₹${lateFees.toFixed(2)}`;
            document.getElementById('payment_absent_fees').value = `₹${absentFees.toFixed(2)}`;
            document.getElementById('payment_early_exit_fees').value = `₹${earlyExitFees.toFixed(2)}`;
        } else {
            document.getElementById('payment_late_fees').value = '₹0.00';
            document.getElementById('payment_absent_fees').value = '₹0.00';
            document.getElementById('payment_early_exit_fees').value = '₹0.00';
        }
        
        // Calculate initial generated salary (Basic + Bonus - Deduction)
        calculateGeneratedSalary();
        
        // Show modal
        const paymentModal = new bootstrap.Modal(document.getElementById('paymentModal'));
        paymentModal.show();
        
        // Reset readonly state when modal is hidden (for next time)
        document.getElementById('paymentModal').addEventListener('hidden.bs.modal', function() {
            const amountPaidInput = document.getElementById('payment_amount_paid');
            const amountPaidNote = document.getElementById('amount_paid_note');
            amountPaidInput.removeAttribute('readonly');
            amountPaidInput.style.backgroundColor = '';
            amountPaidInput.style.cursor = '';
            amountPaidNote.style.display = 'none';
        }, { once: true });
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error loading salary data');
    });
}

// Open Edit Modal
function openEditModal(salaryId) {
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
        document.getElementById('editForm').action = `{{ url('/salary-loan/manage-salaries') }}/${salaryId}`;
        
        // Populate form fields
        document.getElementById('edit_employee').value = data.staff?.name || 'N/A';
        document.getElementById('edit_present').value = data.present || 0;
        document.getElementById('edit_absent').value = data.absent || 0;
        document.getElementById('edit_late').value = data.late || 0;
        document.getElementById('edit_salary_generated').value = parseFloat(data.salary_generated || 0).toFixed(2);
        
        // Store original values for calculation
        const editModal = document.getElementById('editModal');
        editModal.setAttribute('data-basic', data.basic || 0);
        editModal.setAttribute('data-salary-type', data.staff?.salary_type || 'full time');
        editModal.setAttribute('data-late-fees', data.staff?.late_fees || 500);
        editModal.setAttribute('data-absent-fees', data.staff?.absent_fees || null);
        editModal.setAttribute('data-free-absent', data.staff?.free_absent || 0);
        editModal.setAttribute('data-year', data.year || new Date().getFullYear());
        editModal.setAttribute('data-month', data.salary_month || '');
        
        // Show modal
        const modal = new bootstrap.Modal(document.getElementById('editModal'));
        modal.show();
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error loading salary data');
    });
}

// Calculate Generated Salary when editing Present/Absent/Late days
function calculateEditSalary() {
    const editModal = document.getElementById('editModal');
    const basic = parseFloat(editModal.getAttribute('data-basic') || 0);
    const salaryType = (editModal.getAttribute('data-salary-type') || 'full time').toLowerCase();
    const present = parseInt(document.getElementById('edit_present').value || 0);
    const absent = parseInt(document.getElementById('edit_absent').value || 0);
    const late = parseInt(document.getElementById('edit_late').value || 0);
    const lateFeesPerLate = parseFloat(editModal.getAttribute('data-late-fees') || 500);
    const absentFeesPerAbsent = editModal.getAttribute('data-absent-fees');
    const freeAbsent = parseInt(editModal.getAttribute('data-free-absent') || 0);
    const year = parseInt(editModal.getAttribute('data-year') || new Date().getFullYear());
    const monthName = editModal.getAttribute('data-month') || '';
    
    let calculatedSalary = 0;
    
    // Get month number
    const monthNames = {
        'January': 1, 'February': 2, 'March': 3, 'April': 4,
        'May': 5, 'June': 6, 'July': 7, 'August': 8,
        'September': 9, 'October': 10, 'November': 11, 'December': 12
    };
    const month = monthNames[monthName] || new Date().getMonth() + 1;
    
    if (salaryType === 'per hour') {
        // For per hour: present_days * basic
        calculatedSalary = present * basic;
    } else if (salaryType === 'lecture') {
        // For lecture type, salary is based on lectures, not days
        // Keep the current salary_generated as is for lecture type
        calculatedSalary = parseFloat(document.getElementById('edit_salary_generated').value || 0);
    } else {
        // For full time: basic - (absent deductions) - (late deductions)
        // Calculate deductible absents
        const deductibleAbsents = Math.max(0, absent - freeAbsent);
        
        // Calculate days in month
        let daysInMonth = 30;
        try {
            const date = new Date(year, month - 1, 1);
            daysInMonth = new Date(year, month, 0).getDate();
        } catch (e) {
            daysInMonth = 30;
        }
        
        const dailyRate = daysInMonth > 0 ? (basic / daysInMonth) : 0;
        
        // Late deduction
        const lateDeduction = lateFeesPerLate * late;
        
        // Absent deduction
        let absentDeduction = 0;
        if (absentFeesPerAbsent !== null && absentFeesPerAbsent !== 'null') {
            absentDeduction = parseFloat(absentFeesPerAbsent) * deductibleAbsents;
        } else {
            absentDeduction = dailyRate * deductibleAbsents;
        }
        
        calculatedSalary = Math.max(0, basic - absentDeduction - lateDeduction);
    }
    
    // Update the generated salary field
    document.getElementById('edit_salary_generated').value = calculatedSalary.toFixed(2);
}

// Print Payment Receipt
function printPaymentReceipt(salaryId) {
    // Open print receipt in new window
    window.open(`{{ url('/salary-loan/manage-salaries') }}/${salaryId}/print-receipt`, '_blank');
}

// Auto-open thermal receipt print window if payment was made and status became Paid
@if(session('print_receipt_id'))
    window.onload = function() {
        setTimeout(function() {
            const receiptId = {{ session('print_receipt_id') }};
            const printUrl = '{{ url("/salary-loan/manage-salaries") }}/' + receiptId + '/print-receipt-thermal';
            window.open(printUrl, '_blank');
        }, 500);
    };
@endif
</script>
@endsection
