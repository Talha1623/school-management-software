@extends('layouts.app')

@section('title', 'Balance Sheet')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Balance Sheet</h4>
            </div>

            <!-- Filter Form -->
            @php
                $balanceSheetRoute = request()->route() && request()->route()->getName() === 'accounting.parent-wallet.print-balance-sheet'
                    ? 'accounting.parent-wallet.print-balance-sheet'
                    : 'reports.balance-sheet';
            @endphp
            <form action="{{ route($balanceSheetRoute) }}" method="GET" id="filterForm">
                <div class="row g-2 mb-3 align-items-end">
                    <!-- Campus -->
                    <div class="col-md-3">
                        <label for="filter_campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                        <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px;">
                            <option value="">All Campuses</option>
                            @foreach($campuses as $campus)
                                <option value="{{ $campus }}" {{ $filterCampus == $campus ? 'selected' : '' }}>{{ $campus }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- User Type -->
                    <div class="col-md-3">
                        <label for="filter_user_type" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">User Type</label>
                        <select class="form-select form-select-sm" id="filter_user_type" name="filter_user_type" style="height: 32px;">
                            <option value="">All Types</option>
                            @foreach($userTypeOptions as $userType)
                                <option value="{{ $userType }}" {{ $filterUserType == $userType ? 'selected' : '' }}>{{ $userType }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- User -->
                    <div class="col-md-3">
                        <label for="filter_user" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">User</label>
                        <select class="form-select form-select-sm" id="filter_user" name="filter_user" style="height: 32px;">
                            <option value="">All Users</option>
                            @foreach($users as $user)
                                <option value="{{ $user }}" {{ $filterUser == $user ? 'selected' : '' }}>{{ $user }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Button -->
                    <div class="col-md-3">
                        <button type="submit" class="btn btn-sm py-1 px-3 rounded-8 filter-btn w-100" style="height: 32px;">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">filter_alt</span>
                            <span style="font-size: 12px;">Filter</span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Balance Sheet Results -->
            @if(request()->hasAny(['filter_campus', 'filter_user_type', 'filter_user']))
            <div class="mt-3">
                <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 18px;">account_balance</span>
                        <span>Balance Sheet</span>
                    </h5>
                </div>

                <div class="row mt-3">
                    <!-- Income Section -->
                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-success text-white fw-semibold">
                                <h5 class="mb-0 d-flex align-items-center gap-2">
                                    <span class="material-symbols-outlined" style="font-size: 20px;">trending_up</span>
                                    Income (Credits)
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Source</th>
                                                <th class="text-end">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($balanceSheet['income_breakdown'] as $income)
                                            <tr>
                                                <td>{{ $income['source'] }}</td>
                                                <td class="text-end text-success fw-semibold">+{{ number_format($income['amount'], 2) }}</td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="2" class="text-center text-muted">No income records</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot>
                                            <tr class="fw-bold" style="background-color: #f8f9fa;">
                                                <td>Total Income</td>
                                                <td class="text-end text-success">+{{ number_format($balanceSheet['total_income'], 2) }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Expense Section -->
                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-danger text-white fw-semibold">
                                <h5 class="mb-0 d-flex align-items-center gap-2">
                                    <span class="material-symbols-outlined" style="font-size: 20px;">trending_down</span>
                                    Expenses (Debits)
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-sm">
                                        <thead>
                                            <tr>
                                                <th>Source</th>
                                                <th class="text-end">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($balanceSheet['expense_breakdown'] as $expense)
                                            <tr>
                                                <td>{{ $expense['source'] }}</td>
                                                <td class="text-end text-danger fw-semibold">-{{ number_format($expense['amount'], 2) }}</td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="2" class="text-center text-muted">No expense records</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot>
                                            <tr class="fw-bold" style="background-color: #f8f9fa;">
                                                <td>Total Expenses</td>
                                                <td class="text-end text-danger">-{{ number_format($balanceSheet['total_expense'], 2) }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Net Balance Summary -->
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm" style="background: linear-gradient(135deg, #e3f2fd 0%, #f5f5f5 100%);">
                            <div class="card-body">
                                <div class="row align-items-center">
                                    <div class="col-md-6">
                                        <h5 class="mb-0 fw-semibold">Net Balance</h5>
                                        <p class="text-muted mb-0 small">Total Income - Total Expenses</p>
                                    </div>
                                    <div class="col-md-6 text-end">
                                        @if($balanceSheet['net_balance'] >= 0)
                                            <h3 class="mb-0 text-success fw-bold">+{{ number_format($balanceSheet['net_balance'], 2) }}</h3>
                                            <span class="badge bg-success">Profit</span>
                                        @else
                                            <h3 class="mb-0 text-danger fw-bold">{{ number_format($balanceSheet['net_balance'], 2) }}</h3>
                                            <span class="badge bg-danger">Loss</span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @else
            <div class="text-center py-5">
                <span class="material-symbols-outlined text-muted" style="font-size: 64px;">account_balance</span>
                <p class="text-muted mt-3 mb-0">Please apply filters to view balance sheet</p>
            </div>
            @endif
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    const campusSelect = document.getElementById('filter_campus');
    const userTypeSelect = document.getElementById('filter_user_type');
    const userSelect = document.getElementById('filter_user');
    const filterForm = document.getElementById('filterForm');

    function resetUsers() {
        userSelect.innerHTML = '<option value="">All Users</option>';
    }

    function loadUsers() {
        resetUsers();
        const params = new URLSearchParams();
        if (campusSelect.value) {
            params.append('campus', campusSelect.value);
        }
        if (userTypeSelect.value) {
            params.append('user_type', userTypeSelect.value);
        }

        fetch(`{{ route('reports.balance-sheet.get-users-by-campus-and-type') }}?${params.toString()}`)
            .then(response => response.json())
            .then(users => {
                users.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user;
                    option.textContent = user;
                    userSelect.appendChild(option);
                });
            })
            .catch(() => {});
    }

    campusSelect.addEventListener('change', function () {
        loadUsers();
        if (filterForm) {
            filterForm.submit();
        }
    });

    userTypeSelect.addEventListener('change', function () {
        loadUsers();
    });
});
</script>

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

.card {
    border-radius: 8px;
}

.card-header {
    border-radius: 8px 8px 0 0 !important;
}

.badge {
    font-size: 11px;
    padding: 4px 8px;
}
</style>
@endsection
