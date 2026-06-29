@extends('layouts.app')

@section('title', 'Detailed Expense Reports')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Detailed Expense Reports</h4>
            </div>

            <!-- Filter Form -->
            <form action="{{ route('reports.detailed-expense') }}" method="GET" id="filterForm">
                <div class="row g-2 mb-3 align-items-end">
                    <!-- Campus -->
                    <div class="col-md-2">
                        <label for="filter_campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                        <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px;">
                            <option value="">All Campuses</option>
                            @foreach($campuses as $campus)
                                <option value="{{ $campus }}" {{ $filterCampus == $campus ? 'selected' : '' }}>{{ $campus }}</option>
                            @endforeach
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

                    <!-- Date -->
                    <div class="col-md-2">
                        <label for="filter_date" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Date</label>
                        <input type="date" class="form-control form-control-sm" id="filter_date" name="filter_date" value="{{ $filterDate }}" style="height: 32px;">
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

                    <!-- Method -->
                    <div class="col-md-2">
                        <label for="filter_method" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Method</label>
                        <select class="form-select form-select-sm" id="filter_method" name="filter_method" data-selected-method="{{ $filterMethod ?? '' }}" style="height: 32px;">
                            <option value="">All Methods</option>
                            @foreach($methods as $method)
                                <option value="{{ $method }}" {{ $filterMethod == $method ? 'selected' : '' }}>{{ $method }}</option>
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
            @if(request()->hasAny(['filter_campus', 'filter_month', 'filter_date', 'filter_year', 'filter_method']))
            <div class="mt-3">
                <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 18px;">list</span>
                        <span>Detailed Expense Reports</span>
                    </h5>
                </div>

                <div class="d-flex justify-content-end mb-2">
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('reports.detailed-expense.export', ['format' => 'excel']) }}?{{ http_build_query(request()->except(['page'])) }}" class="btn btn-sm px-2 py-1 export-btn excel-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                            <span>Excel</span>
                        </a>
                        <a href="{{ route('reports.detailed-expense.export', ['format' => 'csv']) }}?{{ http_build_query(request()->except(['page'])) }}" class="btn btn-sm px-2 py-1 export-btn csv-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">table_view</span>
                            <span>CSV</span>
                        </a>
                        <a href="{{ route('reports.detailed-expense.export', ['format' => 'pdf']) }}?{{ http_build_query(request()->except(['page'])) }}" class="btn btn-sm px-2 py-1 export-btn pdf-btn" target="_blank">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">picture_as_pdf</span>
                            <span>PDF</span>
                        </a>
                        <a href="{{ route('reports.detailed-expense.print') }}?{{ http_build_query(array_merge(request()->except(['page']), ['auto_print' => 1])) }}" class="btn btn-sm px-2 py-1 export-btn print-btn" target="_blank">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">print</span>
                            <span>Print</span>
                        </a>
                    </div>
                </div>

                <div class="default-table-area" style="margin-top: 0;">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Title</th>
                                    <th>Categories</th>
                                    <th>Accountant</th>
                                    <th>Amount</th>
                                    <th>Date & Time</th>
                                    <th>Description</th>
                                    <th>Method</th>
                                    <th class="no-print">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($expenseRecords as $index => $record)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>{{ $record['title'] }}</td>
                                    <td>
                                        <span class="badge bg-warning text-dark">{{ $record['category'] }}</span>
                                    </td>
                                    <td>{{ $record['accountant'] ?? 'N/A' }}</td>
                                    <td class="fw-semibold text-danger">{{ number_format($record['amount'], 2) }}</td>
                                    <td>{{ $record['date'] ? \Carbon\Carbon::parse($record['date'])->format('d M Y h:i A') : 'N/A' }}</td>
                                    <td>{{ $record['description'] ? (strlen($record['description']) > 50 ? substr($record['description'], 0, 50) . '...' : $record['description']) : 'N/A' }}</td>
                                    <td>{{ $record['method'] }}</td>
                                    <td class="no-print">
                                        <div class="d-inline-flex align-items-center gap-1 flex-wrap">
                                            @if(($record['record_type'] ?? '') === 'expense' && !empty($record['expense_id']))
                                                <a class="btn btn-sm btn-primary px-2 py-1 d-inline-flex align-items-center gap-1"
                                                   href="{{ route('expense-management.add.print', $record['expense_id']) }}"
                                                   target="_blank"
                                                   title="Print expense slip">
                                                    <span class="material-symbols-outlined" style="font-size: 14px;">print</span>
                                                    Slip
                                                </a>
                                                <form action="{{ route('expense-management.add.destroy', $record['expense_id']) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this expense?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger px-2 py-1" title="Delete">
                                                        <span class="material-symbols-outlined" style="font-size: 16px;">delete</span>
                                                    </button>
                                                </form>
                                            @elseif(($record['record_type'] ?? '') === 'salary' && !empty($record['salary_id']))
                                                <a class="btn btn-sm btn-primary px-2 py-1 d-inline-flex align-items-center gap-1"
                                                   href="{{ route('salary-loan.manage-salaries.print-receipt-thermal', $record['salary_id']) }}"
                                                   target="_blank"
                                                   title="Print salary slip">
                                                    <span class="material-symbols-outlined" style="font-size: 14px;">print</span>
                                                    Slip
                                                </a>
                                            @else
                                                <span class="text-muted">N/A</span>
                                            @endif
                                        </div>
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="9" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <span class="material-symbols-outlined text-muted" style="font-size: 48px;">inbox</span>
                                            <p class="text-muted mt-2 mb-0">No expense records found</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if($expenseRecords->count() > 0)
                            <tfoot>
                                <tr class="fw-bold" style="background-color: #f8f9fa;">
                                    <td colspan="3" class="text-end">Total Expense:</td>
                                    <td class="text-danger">{{ number_format($expenseRecords->sum('amount'), 2) }}</td>
                                    <td colspan="5"></td>
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
                <p class="text-muted mt-3 mb-0">Please apply filters to view detailed expense reports</p>
            </div>
            @endif
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

.export-btn {
    border: none;
    font-weight: 500;
    transition: all 0.3s ease;
    border-radius: 6px;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    height: 32px;
    font-size: 12px;
}
.excel-btn { background-color: #28a745; color: white; }
.excel-btn:hover { background-color: #218838; color: white; }
.csv-btn { background-color: #17a2b8; color: white; }
.csv-btn:hover { background-color: #138496; color: white; }
.pdf-btn { background-color: #dc3545; color: white; }
.pdf-btn:hover { background-color: #c82333; color: white; }
.print-btn { background-color: #6c757d; color: white; }
.print-btn:hover { background-color: #5a6268; color: white; }
</style>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const campusSelect = document.getElementById('filter_campus');
    const methodSelect = document.getElementById('filter_method');
    const methodsUrl = @json(route('reports.detailed-expense.get-methods-by-campus'));

    function resetMethodOptions(selectedMethod = '') {
        if (!methodSelect) {
            return;
        }

        methodSelect.innerHTML = '<option value="">All Methods</option>';
        if (selectedMethod) {
            const selectedOption = document.createElement('option');
            selectedOption.value = selectedMethod;
            selectedOption.textContent = selectedMethod;
            selectedOption.selected = true;
            methodSelect.appendChild(selectedOption);
        }
    }

    function loadMethods(selectedMethod = '') {
        if (!methodSelect) {
            return;
        }

        const params = new URLSearchParams();
        if (campusSelect && campusSelect.value) {
            params.append('filter_campus', campusSelect.value);
        }

        fetch(methodsUrl + '?' + params.toString())
            .then(response => response.json())
            .then(data => {
                const methods = Array.isArray(data.methods) ? data.methods : [];
                methodSelect.innerHTML = '<option value="">All Methods</option>';

                methods.forEach(method => {
                    const option = document.createElement('option');
                    option.value = method;
                    option.textContent = method;
                    if (selectedMethod && selectedMethod === method) {
                        option.selected = true;
                    }
                    methodSelect.appendChild(option);
                });
            })
            .catch(() => resetMethodOptions(selectedMethod));
    }

    if (campusSelect && !campusSelect.disabled) {
        campusSelect.addEventListener('change', function () {
            methodSelect.dataset.selectedMethod = '';
            loadMethods();
        });
    }

    loadMethods(methodSelect ? (methodSelect.dataset.selectedMethod || '') : '');
});
</script>

@endsection
