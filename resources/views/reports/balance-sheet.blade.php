@extends($layout ?? 'layouts.app')

@section('title', 'Balance Sheet')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3 no-print">
                <h4 class="mb-0 fs-16 fw-semibold">Balance Sheet</h4>
                <div class="d-flex align-items-center gap-2 no-print">
                    @if($showBalanceSheetResults || request()->hasAny(['filter_campus', 'filter_user_type', 'filter_user', 'filter_day']))
                    <button type="button" class="btn btn-sm py-1 px-3 rounded-8 balance-sheet-print-btn" onclick="printBalanceSheet()">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">print</span>
                        <span style="font-size: 12px;">Print</span>
                    </button>
                    @endif
                    @if(!empty($isFullySettled))
                    <span class="badge bg-success px-3 py-2" style="font-size: 12px;">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">check_circle</span>
                        Settled
                    </span>
                    @elseif($showBalanceSheetResults || request()->hasAny(['filter_campus', 'filter_user_type', 'filter_user', 'filter_day']))
                    <button type="button" class="btn btn-sm py-1 px-3 rounded-8 settlement-btn" data-bs-toggle="modal" data-bs-target="#makeSettlementModal">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">handshake</span>
                        <span style="font-size: 12px;">Make Settlement</span>
                    </button>
                    @endif
                </div>
            </div>

            <!-- Filter Form -->
            @php
                $balanceSheetRoute = $balanceSheetRoute ?? (
                    request()->route() && request()->route()->getName() === 'accounting.parent-wallet.print-balance-sheet'
                        ? 'accounting.parent-wallet.print-balance-sheet'
                        : 'reports.balance-sheet'
                );
                $balanceSheetUsersRoute = $balanceSheetUsersRoute ?? 'reports.balance-sheet.get-users-by-campus-and-type';
                $balanceSheetSettlementRoute = $balanceSheetSettlementRoute ?? 'reports.balance-sheet.settlement.store';
                $balanceSheetUsersUrl = \Illuminate\Support\Facades\Route::has($balanceSheetUsersRoute)
                    ? route($balanceSheetUsersRoute)
                    : url('/reports/balance-sheet/get-users-by-campus-and-type');
                $balanceSheetSettlementUrl = \Illuminate\Support\Facades\Route::has($balanceSheetSettlementRoute)
                    ? route($balanceSheetSettlementRoute)
                    : url('/reports/balance-sheet/settlement');
                $lockCampus = !empty($lockCampus);
                $lockAccountantUser = !empty($lockAccountantUser);
                $defaultAccountantName = $defaultAccountantName ?? ($filterUser ?? '');
                $showBalanceSheetResults = !empty($showBalanceSheetResults);
                $selectedDay = $filterDay ?? 'current_day';
                $dayFilters = [
                    'current_day' => 'CURRENT DAY',
                    'yesterday' => 'YESTERDAY',
                    'two_days_ago' => 'TWO DAYS AGO',
                    'three_days_ago' => 'THREE DAYS AGO',
                    'four_days_ago' => 'FOUR DAYS AGO',
                    'five_days_ago' => 'FIVE DAYS AGO',
                    'six_days_ago' => 'SIX DAYS AGO',
                ];
            @endphp
            <form action="{{ route($balanceSheetRoute) }}" method="GET" id="filterForm" class="no-print">
                <input type="hidden" name="filter_day" value="{{ $selectedDay }}">
                <div class="row g-2 mb-3 align-items-end">
                    <!-- Campus -->
                    <div class="col-md-3">
                        <label for="filter_campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                        @if($lockCampus && !empty($defaultCampus))
                            <input type="hidden" name="filter_campus" value="{{ $defaultCampus }}">
                            <select class="form-select form-select-sm" id="filter_campus" style="height: 32px;" disabled>
                                <option value="{{ $defaultCampus }}" selected>{{ $defaultCampus }}</option>
                            </select>
                        @else
                            <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px;">
                                <option value="">All Campuses</option>
                                @foreach($campuses as $campus)
                                    <option value="{{ $campus }}" {{ $filterCampus == $campus ? 'selected' : '' }}>{{ $campus }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    <!-- User Type -->
                    <div class="col-md-3">
                        <label for="filter_user_type" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">User Type</label>
                        @if($lockAccountantUser && $defaultAccountantName)
                            <input type="hidden" name="filter_user_type" value="accountant">
                            <select class="form-select form-select-sm" id="filter_user_type" style="height: 32px;" disabled>
                                <option value="accountant" selected>Accountant</option>
                            </select>
                        @else
                            <select class="form-select form-select-sm" id="filter_user_type" name="filter_user_type" style="height: 32px;">
                                <option value="">All Types</option>
                                @foreach($userTypeOptions as $userType)
                                    <option value="{{ $userType['value'] }}" {{ ($filterUserType ?? '') == $userType['value'] ? 'selected' : '' }}>{{ $userType['label'] }}</option>
                                @endforeach
                            </select>
                        @endif
                    </div>

                    <!-- User -->
                    <div class="col-md-3">
                        <label for="filter_user" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">User</label>
                        @if($lockAccountantUser && $defaultAccountantName)
                            <input type="hidden" name="filter_user" value="{{ $defaultAccountantName }}">
                            <select class="form-select form-select-sm" id="filter_user" data-selected-user="{{ $defaultAccountantName }}" style="height: 32px;" disabled>
                                <option value="{{ $defaultAccountantName }}" selected>{{ $defaultAccountantName }}</option>
                            </select>
                        @else
                            <select class="form-select form-select-sm" id="filter_user" name="filter_user" data-selected-user="{{ $filterUser ?? '' }}" style="height: 32px;">
                                <option value="">All Users</option>
                                @foreach($users as $user)
                                    <option value="{{ $user }}" {{ $filterUser == $user ? 'selected' : '' }}>{{ $user }}</option>
                                @endforeach
                            </select>
                        @endif
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

            <div class="day-tabs mb-3 no-print">
                @foreach($dayFilters as $dayKey => $dayLabel)
                    <a href="{{ route($balanceSheetRoute, array_merge(request()->query(), ['filter_day' => $dayKey])) }}"
                       class="day-tab {{ $selectedDay === $dayKey ? 'active' : '' }}">
                        {{ $dayLabel }}
                    </a>
                @endforeach
            </div>

            <!-- Balance Sheet Results -->
            @if($showBalanceSheetResults || request()->hasAny(['filter_campus', 'filter_user_type', 'filter_user', 'filter_day']))
            <div class="mt-3">
                @if(session('success'))
                    <div class="alert alert-success py-2 px-3 fs-12 mb-2 no-print">{{ session('success') }}</div>
                @endif
                @if(session('error'))
                    <div class="alert alert-danger py-2 px-3 fs-12 mb-2 no-print">{{ session('error') }}</div>
                @endif

                <div id="balanceSheetPrintArea" class="balance-sheet-print-area">
                <div class="balance-sheet-print-meta mb-3">
                    <h2 class="balance-sheet-print-title mb-1">Balance Sheet</h2>
                    <p class="balance-sheet-print-subtitle mb-0">
                        <strong>Day:</strong> {{ $dayFilters[$selectedDay] ?? strtoupper(str_replace('_', ' ', $selectedDay)) }}
                        | <strong>Campus:</strong> {{ $filterCampus ?: 'All Campuses' }}
                        | <strong>User Type:</strong> {{ $filterUserType ? ucwords(str_replace('_', ' ', $filterUserType)) : 'All Types' }}
                        | <strong>User:</strong> {{ $filterUser ?: 'All Users' }}
                    </p>
                </div>
                <div class="mb-2 p-2 rounded-8 balance-sheet-print-banner" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 18px;">account_balance</span>
                        <span>Balance Sheet</span>
                    </h5>
                </div>

                @php
                    $paymentsTableTotal = $paymentEntries->sum(fn ($entry) => (float) ($entry->payment_amount ?? 0));
                    $expensesTableTotal = collect($expenseEntries)->sum(fn ($expense) => (float) data_get($expense, 'amount', 0));
                @endphp

                <div class="row mt-3 balance-sheet-print-grid-row">
                    <div class="col-md-7 mb-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-success text-white fw-semibold">Payments</div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="balance-ledger-table balance-payments-table mb-0">
                                        <thead>
                                            <tr>
                                                <th>ST.Roll</th>
                                                <th>Name</th>
                                                <th>Class</th>
                                                <th>Payment Title</th>
                                                <th class="text-end">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($paymentEntries as $entry)
                                            <tr>
                                                <td>{{ $entry->student_code ?? 'N/A' }}</td>
                                                <td>{{ $entry->student_name ?? 'N/A' }}</td>
                                                <td>{{ $entry->class ?? 'N/A' }}</td>
                                                <td>{{ $entry->payment_title ?? 'N/A' }}</td>
                                                <td class="text-end fw-semibold balance-ledger-amount balance-ledger-amount-payment">{{ number_format((float) ($entry->payment_amount ?? 0), 2) }}</td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="5" class="text-center text-muted">No payment records</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot>
                                            <tr class="balance-sheet-total-row">
                                                <td colspan="4" class="fw-semibold text-end">Total</td>
                                                <td class="text-end fw-bold balance-ledger-amount balance-ledger-amount-payment">{{ number_format($paymentsTableTotal, 2) }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-5 mb-4">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-danger text-white fw-semibold">Expenses</div>
                            <div class="card-body p-0">
                                <div class="table-responsive">
                                    <table class="balance-ledger-table balance-expenses-table mb-0">
                                        <thead>
                                            <tr>
                                                <th>Reference</th>
                                                <th>Expense Title</th>
                                                <th class="text-end">Amount</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            @forelse($expenseEntries as $expense)
                                            <tr>
                                                <td>{{ data_get($expense, 'id') }}</td>
                                                <td>{{ data_get($expense, 'title', 'N/A') }}</td>
                                                <td class="text-end fw-semibold balance-ledger-amount balance-ledger-amount-expense">{{ number_format((float) data_get($expense, 'amount', 0), 2) }}</td>
                                            </tr>
                                            @empty
                                            <tr>
                                                <td colspan="3" class="text-center text-muted">No expense records (Teacher Salary / Management Expense)</td>
                                            </tr>
                                            @endforelse
                                        </tbody>
                                        <tfoot>
                                            <tr class="balance-sheet-total-row">
                                                <td colspan="2" class="fw-semibold text-end">Total</td>
                                                <td class="text-end fw-bold balance-ledger-amount balance-ledger-amount-expense">{{ number_format($expensesTableTotal, 2) }}</td>
                                            </tr>
                                        </tfoot>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                @if(empty($isFullySettled))
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-2 unsettled-note-box mb-3 no-print">
                    <div class="unsettled-note-text">
                        <span class="material-symbols-outlined align-middle" style="font-size: 16px;">error</span>
                        This balancesheet is still unsettled.
                    </div>
                    <button type="button" class="btn btn-sm py-1 px-3 rounded-8 settlement-btn" data-bs-toggle="modal" data-bs-target="#makeSettlementModal">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">handshake</span>
                        <span style="font-size: 12px;">Make Settlement</span>
                    </button>
                </div>
                @else
                <div class="alert alert-success py-2 px-3 fs-12 mb-3 no-print d-flex align-items-center gap-2">
                    <span class="material-symbols-outlined" style="font-size: 16px;">check_circle</span>
                    <span>This balance sheet scope is already settled for the selected day.</span>
                </div>
                @endif

                <!-- Balance Summary — stacked rows, no horizontal scroll -->
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm balance-summary-card">
                            <div class="card-body p-3">
                                <h5 class="mb-3 fw-semibold">Balance Summary</h5>
                                <table class="balance-summary-table mb-0">
                                    <colgroup>
                                        @if(empty($isFullySettled))
                                        <col class="balance-summary-col-watermark">
                                        @endif
                                        <col class="balance-summary-col-label">
                                        <col class="balance-summary-col-value">
                                    </colgroup>
                                    <tbody>
                                        <tr>
                                            @if(empty($isFullySettled))
                                            <td rowspan="4" class="balance-summary-watermark-col">
                                                <span class="balance-summary-watermark-cell">Unsettled</span>
                                            </td>
                                            @endif
                                            <td class="balance-summary-label text-end">Total Payment:</td>
                                            <td class="balance-summary-value balance-summary-payment text-end fw-bold" style="background-color: #97d897;">Rs. {{ number_format($balanceSheet['total_income'], 0) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="balance-summary-label text-end">Total Expenses:</td>
                                            <td class="balance-summary-value balance-summary-expense text-end fw-bold" style="background-color: #fdcbc9;">Rs. {{ number_format($balanceSheet['total_expense'], 0) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="balance-summary-label text-end">Previous Unsettled Amount:</td>
                                            <td class="balance-summary-value balance-summary-previous text-end fw-bold" style="background-color: #fdcbc9;">Rs. {{ number_format($balanceSheet['previous_unsettled'] ?? 0, 0) }}</td>
                                        </tr>
                                        <tr>
                                            <td class="balance-summary-label text-end">Cash in hand:</td>
                                            <td class="balance-summary-value balance-summary-cash text-end fw-bold" style="background-color: #e8e8d5;">Rs. {{ number_format($balanceSheet['cash_in_hand'] ?? $balanceSheet['net_balance'], 0) }}</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>

                @if(isset($settlementRecords) && $settlementRecords->isNotEmpty())
                <div class="row mt-3">
                    <div class="col-12">
                        <div class="card border-0 shadow-sm">
                            <div class="card-header bg-white fw-semibold py-2 px-3">Settlements</div>
                            <div class="card-body p-0">
                                <table class="balance-ledger-table balance-settlements-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>Settlement</th>
                                            <th>User</th>
                                            <th class="text-end">Amount</th>
                                            <th>Method</th>
                                            <th>Trx#</th>
                                            <th>Date</th>
                                            <th>Remarks</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($settlementRecords as $settlement)
                                        @php
                                            $settlementType = strtolower(trim((string) ($settlement->user_type ?? 'all')));
                                            $settlementUser = trim((string) ($settlement->user_name ?? 'all'));
                                            $typeLabel = $settlementType !== 'all'
                                                ? ucwords(str_replace('_', ' ', $settlementType))
                                                : 'All Types';

                                            if ($settlementType === 'all' && $settlementUser === 'all') {
                                                $settlementUserLabel = 'All Types / All Users';
                                            } elseif ($settlementUser !== 'all' && $settlementUser !== '') {
                                                $settlementUserLabel = $settlementType !== 'all'
                                                    ? $settlementUser . ' (' . $typeLabel . ')'
                                                    : $settlementUser;
                                            } else {
                                                $settlementUserLabel = $typeLabel . ' (All Users)';
                                            }
                                        @endphp
                                        <tr>
                                            <td>
                                                <span class="badge rounded-pill balance-settlement-status">Completed</span>
                                            </td>
                                            <td>{{ $settlementUserLabel }}</td>
                                            <td class="text-end fw-semibold">Rs. {{ number_format((float) ($settlement->total_payment ?? 0), 2) }}</td>
                                            <td>{{ ucwords(str_replace('_', ' ', $settlement->method)) }}</td>
                                            <td>{{ $settlement->transaction_id ?: '—' }}</td>
                                            <td>{{ optional($settlement->settlement_date)->format('d M Y') ?? $settlement->created_at->format('d M Y') }}</td>
                                            <td>{{ $settlement->remarks ?: '—' }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
                @endif
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

<!-- Make Settlement Modal -->
<div class="modal fade no-print" id="makeSettlementModal" tabindex="-1" aria-labelledby="makeSettlementModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fs-16 fw-semibold" id="makeSettlementModalLabel">Make Settlement</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <form id="makeSettlementForm" action="{{ $balanceSheetSettlementUrl }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    <input type="hidden" name="filter_campus" value="{{ $filterCampus }}">
                    <input type="hidden" name="filter_user_type" value="{{ $filterUserType }}">
                    <input type="hidden" name="filter_user" value="{{ $filterUser }}">
                    <input type="hidden" name="filter_day" value="{{ $selectedDay }}">
                    <div class="row g-2">
                    <div class="col-md-6">
                        <label for="settlement_total_payment" class="form-label mb-1 fs-12 fw-semibold">Total Payment</label>
                        <input
                            type="text"
                            name="total_payment"
                            id="settlement_total_payment"
                            class="form-control form-control-sm"
                            value="{{ number_format((float) ($balanceSheet['cash_in_hand'] ?? 0), 0, '.', '') }}"
                            readonly
                        >
                    </div>

                    <div class="col-md-6">
                        <label for="settlement_receipt" class="form-label mb-1 fs-12 fw-semibold">Photo / Receipt</label>
                        <input type="file" name="receipt" id="settlement_receipt" class="form-control form-control-sm" accept="image/*,.pdf">
                    </div>

                    <div class="col-md-6">
                        <label for="settlement_method" class="form-label mb-1 fs-12 fw-semibold">Method</label>
                        <select id="settlement_method" name="method" class="form-select form-select-sm" required>
                            <option value="">Select Method</option>
                            <option value="cash_by_hand">Cash By Hand</option>
                            <option value="online_transfer">Online Transfer</option>
                            <option value="banks_transfer">Banks Transfer</option>
                        </select>
                    </div>

                    <div class="col-md-6">
                        <label for="settlement_transaction_id" class="form-label mb-1 fs-12 fw-semibold">Transaction ID</label>
                        <input type="text" name="transaction_id" id="settlement_transaction_id" class="form-control form-control-sm" placeholder="Enter transaction id">
                    </div>

                    <div class="col-12">
                        <label for="settlement_remarks" class="form-label mb-1 fs-12 fw-semibold">Remarks</label>
                        <textarea id="settlement_remarks" name="remarks" class="form-control form-control-sm" rows="3" placeholder="Enter remarks"></textarea>
                    </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-light" data-bs-dismiss="modal">Close</button>
                <button type="submit" form="makeSettlementForm" class="btn btn-sm settlement-btn">Save Settlement</button>
            </div>
        </div>
    </div>
</div>

<script>
function printBalanceSheet() {
    const area = document.getElementById('balanceSheetPrintArea');
    if (!area) {
        alert('Please apply filters first to load balance sheet data.');
        return;
    }

    const printWindow = window.open('', '_blank');
    if (!printWindow) {
        alert('Please allow pop-ups to print the balance sheet.');
        return;
    }

    const pageStyles = document.getElementById('balanceSheetPrintStyles');
    const linkedStyles = Array.from(document.querySelectorAll('link[rel="stylesheet"]'))
        .map((link) => `<link rel="stylesheet" href="${link.href}">`)
        .join('\n');

    printWindow.document.open();
    printWindow.document.write(`
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Balance Sheet</title>
    ${linkedStyles}
    ${pageStyles ? pageStyles.outerHTML : ''}
    <style>
        html, body {
            margin: 0;
            padding: 16px;
            background: #fff;
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
        }
        .balance-sheet-print-meta { display: block !important; }
        .balance-sheet-print-banner { display: none !important; }
        .balance-sheet-print-banner .material-symbols-outlined { display: none !important; }
        .table-responsive { overflow: visible !important; }
        .balance-sheet-print-grid-row {
            display: flex !important;
            flex-wrap: nowrap;
            gap: 12px;
            align-items: flex-start;
        }
        .balance-sheet-print-grid-row > .col-md-7 {
            flex: 0 0 58%;
            max-width: 58%;
            width: 58%;
        }
        .balance-sheet-print-grid-row > .col-md-5 {
            flex: 0 0 40%;
            max-width: 40%;
            width: 40%;
        }
        .balance-sheet-print-area > .row.mt-3:not(.balance-sheet-print-grid-row) {
            width: 100%;
        }
        .card { box-shadow: none !important; break-inside: avoid; page-break-inside: avoid; }
        @page { margin: 12mm; size: A4; }
    </style>
</head>
<body>${area.innerHTML}</body>
</html>
    `);
    printWindow.document.close();

    printWindow.onload = function () {
        setTimeout(function () {
            printWindow.focus();
            printWindow.print();
            setTimeout(function () {
                printWindow.close();
            }, 800);
        }, 500);
    };
}

document.addEventListener('DOMContentLoaded', function () {
    const campusSelect = document.getElementById('filter_campus');
    const userTypeSelect = document.getElementById('filter_user_type');
    const userSelect = document.getElementById('filter_user');
    const filterForm = document.getElementById('filterForm');

    function resetUsers() {
        userSelect.innerHTML = '<option value="">All Users</option>';
    }

    function loadUsers(selectedUser = '') {
        resetUsers();
        const params = new URLSearchParams();
        if (campusSelect.value) {
            params.append('campus', campusSelect.value);
        }
        if (userTypeSelect.value) {
            params.append('user_type', userTypeSelect.value);
        }

        fetch(`{{ $balanceSheetUsersUrl }}?${params.toString()}`)
            .then(response => response.json())
            .then(users => {
                users.forEach(user => {
                    const option = document.createElement('option');
                    option.value = user;
                    option.textContent = user;
                    if (selectedUser && selectedUser === user) {
                        option.selected = true;
                    }
                    userSelect.appendChild(option);
                });
            })
            .catch(() => {});
    }

    if (campusSelect && !campusSelect.disabled) {
        campusSelect.addEventListener('change', function () {
            userSelect.dataset.selectedUser = '';
            loadUsers();
            if (filterForm) {
                filterForm.submit();
            }
        });
    }

    userTypeSelect.addEventListener('change', function () {
        userSelect.dataset.selectedUser = '';
        loadUsers();
    });

    loadUsers(userSelect.dataset.selectedUser || '');
});
</script>

<style id="balanceSheetPrintStyles">
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

.settlement-btn {
    background: linear-gradient(135deg, #12824c 0%, #0b6b3d 100%);
    color: #fff;
    border: none;
    transition: all 0.3s ease;
}

.settlement-btn:hover {
    background: linear-gradient(135deg, #0b6b3d 0%, #12824c 100%);
    color: #fff;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(11, 107, 61, 0.35);
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

.day-tabs {
    display: flex;
    flex-wrap: wrap;
    border-bottom: 1px solid #d8d8d8;
}

.day-tab {
    padding: 8px 14px;
    font-size: 11px;
    font-weight: 600;
    color: #6f6f6f;
    text-decoration: none;
    border: 1px solid #ececec;
    border-bottom: none;
    background: #f6f6f6;
    margin-right: 4px;
    text-transform: uppercase;
}

.day-tab:hover {
    color: #003471;
    background: #efefef;
}

.day-tab.active {
    background: #ffffff;
    color: #222222;
    border-color: #d8d8d8;
    position: relative;
}

.day-tab.active::after {
    content: '';
    position: absolute;
    left: 0;
    right: 0;
    bottom: -1px;
    height: 1px;
    background: #ffffff;
}

.unsettled-watermark-box {
    min-height: 120px;
    border: 1px solid #e5e5e5;
    background: #f7f7f7;
    display: flex;
    align-items: center;
    justify-content: center;
}

.unsettled-watermark-text {
    font-size: 42px;
    letter-spacing: 1px;
    color: rgba(90, 90, 90, 0.7);
    transform: rotate(-24deg);
    font-weight: 600;
}

.unsettled-note-box {
    border-top: 1px solid #ececec;
    padding-top: 8px;
}

.unsettled-note-text {
    color: #c63333;
    font-weight: 600;
    font-size: 15px;
}

.settled-note-box {
    border: 1px solid #d6eadc;
    background: #edf9f1;
    color: #12824c;
    font-weight: 600;
    padding: 8px 10px;
    border-radius: 6px;
}

.balance-ledger-table {
    width: 100%;
    border-collapse: collapse;
    font-size: 13px;
}

.balance-ledger-table th,
.balance-ledger-table td {
    padding: 8px 10px;
    border: 1px solid #dee2e6;
    vertical-align: middle;
}

.balance-ledger-table thead th {
    background: #f8f9fa;
    font-weight: 600;
}

.balance-sheet-total-row td {
    border-top: 2px solid rgba(0, 0, 0, 0.08);
    font-size: 13px;
}

.balance-ledger-amount-payment {
    background-color: #97d897 !important;
    color: #1a1a1a !important;
    box-shadow: none !important;
    font-weight: 700;
}

.balance-ledger-amount-expense {
    background-color: #fdcbc9 !important;
    color: #1a1a1a !important;
    box-shadow: none !important;
    font-weight: 700;
}

.balance-settlements-table thead th {
    background: #f8f9fa;
    font-weight: 600;
    font-size: 13px;
}

.balance-settlement-status {
    background-color: #198754 !important;
    color: #ffffff !important;
    font-size: 11px;
    font-weight: 600;
    padding: 6px 12px;
}


.balance-summary-card {
    background: #ffffff;
}

.balance-summary-table {
    width: 100%;
    table-layout: fixed;
    border-collapse: collapse;
    border: 1px solid #dee2e6;
}

.balance-summary-col-watermark {
    width: 160px;
}

.balance-summary-col-label {
    width: auto;
}

.balance-summary-col-value {
    width: 200px;
}

.balance-summary-table td {
    vertical-align: middle;
    font-size: 13px;
    padding: 10px 12px;
    border: 1px solid #dee2e6 !important;
    word-wrap: break-word;
}

.balance-summary-watermark-col {
    background: #ffffff;
    text-align: center;
    vertical-align: middle;
    padding: 0;
    position: relative;
    overflow: hidden;
}

.balance-summary-watermark-cell {
    position: absolute;
    left: 50%;
    top: 50%;
    transform: translate(-50%, -50%) rotate(-24deg);
    font-size: 34px;
    letter-spacing: 1px;
    color: rgba(90, 90, 90, 0.55);
    font-weight: 600;
    white-space: nowrap;
    line-height: 1.1;
    pointer-events: none;
    z-index: 1;
}

.balance-summary-label {
    font-weight: 600;
    background: #ffffff !important;
}

.balance-summary-value {
    font-weight: 700;
}

.balance-summary-table td.balance-summary-payment {
    background-color: #97d897 !important;
    box-shadow: none !important;
    color: #1a1a1a;
}

.balance-summary-table td.balance-summary-expense,
.balance-summary-table td.balance-summary-previous {
    background-color: #fdcbc9 !important;
    box-shadow: none !important;
    color: #1a1a1a;
}

.balance-summary-table td.balance-summary-cash {
    background-color: #e8e8d5 !important;
    box-shadow: none !important;
    color: #1a1a1a;
}


.balance-sheet-print-btn {
    background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
    color: #fff;
    border: none;
}

.balance-sheet-print-btn:hover {
    background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
    color: #fff;
}

.balance-sheet-print-meta {
    display: none;
}

@media print {
    body {
        background: #fff !important;
        -webkit-print-color-adjust: exact;
        print-color-adjust: exact;
    }

    .no-print,
    .sidebar-area,
    #sidebar-area,
    #header-area,
    .header-area,
    .footer-area,
    .theme-settings,
    #theme-settings,
    .preloader,
    .day-tabs,
    .unsettled-note-box {
        display: none !important;
    }

    .main-content-container,
    .main-content,
    .container-fluid,
    .row,
    .col-12,
    .mt-3 {
        overflow: visible !important;
        height: auto !important;
        max-height: none !important;
    }

    .card.bg-white {
        border: none !important;
        box-shadow: none !important;
        padding: 0 !important;
    }

    .balance-sheet-print-meta {
        display: block !important;
    }

    #balanceSheetPrintArea {
        position: static !important;
        display: block !important;
        width: 100% !important;
        overflow: visible !important;
    }

    .table-responsive {
        overflow: visible !important;
        display: block !important;
    }

    .balance-sheet-print-grid-row {
        display: flex !important;
        flex-wrap: nowrap !important;
        gap: 12px;
    }

    .balance-sheet-print-grid-row > .col-md-7 {
        flex: 0 0 58% !important;
        max-width: 58% !important;
        width: 58% !important;
    }

    .balance-sheet-print-grid-row > .col-md-5 {
        flex: 0 0 40% !important;
        max-width: 40% !important;
        width: 40% !important;
    }

    .balance-sheet-print-area .card {
        box-shadow: none !important;
        break-inside: avoid;
        page-break-inside: avoid;
        margin-bottom: 12px !important;
    }

    .balance-ledger-amount-payment,
    .balance-ledger-amount-expense,
    .balance-summary-payment,
    .balance-summary-expense,
    .balance-summary-previous,
    .balance-summary-cash,
    .card-header.bg-success,
    .card-header.bg-danger,
    .balance-settlement-status {
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
}

</style>
@endsection
