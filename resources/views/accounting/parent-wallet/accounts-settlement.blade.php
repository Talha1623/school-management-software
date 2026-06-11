@extends('layouts.app')

@section('title', 'Accounts Settlement')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-3 p-3 rounded-8" style="background-color: #f8f9fa; border: 1px solid #e9ecef;">
                <div class="d-flex align-items-center gap-2">
                    <span class="material-symbols-outlined" style="font-size: 18px; color: #003471;">receipt_long</span>
                    <div>
                        <h5 class="mb-0 fs-15 fw-semibold" style="color: #003471;">Accounts Settlement</h5>
                        <small class="text-muted">Settlement list with export actions</small>
                    </div>
                </div>
                <div class="d-flex gap-2 flex-wrap export-buttons">
                    <a href="{{ route('accounting.parent-wallet.accounts-settlement.export', array_merge(request()->query(), ['format' => 'excel'])) }}"
                       class="btn btn-sm px-2 py-1 export-btn excel-btn">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                        <span>Excel</span>
                    </a>
                    <a href="{{ route('accounting.parent-wallet.accounts-settlement.export', array_merge(request()->query(), ['format' => 'csv'])) }}"
                       class="btn btn-sm px-2 py-1 export-btn csv-btn">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">file_present</span>
                        <span>CSV</span>
                    </a>
                    <a href="{{ route('accounting.parent-wallet.accounts-settlement.export', array_merge(request()->query(), ['format' => 'pdf'])) }}"
                       target="_blank"
                       class="btn btn-sm px-2 py-1 export-btn pdf-btn">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">picture_as_pdf</span>
                        <span>PDF</span>
                    </a>
                    <a href="{{ route('accounting.parent-wallet.accounts-settlement.print', array_merge(request()->query(), ['auto_print' => 1])) }}"
                       target="_blank"
                       class="btn btn-sm px-2 py-1 export-btn print-btn">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">print</span>
                        <span>Print</span>
                    </a>
                </div>
            </div>

            @if(session('success'))
                <div class="alert alert-success py-2 px-3 fs-12 mb-2">{{ session('success') }}</div>
            @endif
            @if(session('error'))
                <div class="alert alert-danger py-2 px-3 fs-12 mb-2">{{ session('error') }}</div>
            @endif

            <form method="GET" action="{{ route('accounting.parent-wallet.accounts-settlement') }}" class="row g-2 mb-3">
                <div class="col-md-4">
                    <label class="form-label mb-1 fs-12 fw-semibold">Date</label>
                    <input type="date" class="form-control form-control-sm" name="date" value="{{ request('date') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label mb-1 fs-12 fw-semibold">Method</label>
                    <select class="form-select form-select-sm" name="method">
                        <option value="">All Methods</option>
                        <option value="cash_by_hand" {{ request('method') === 'cash_by_hand' ? 'selected' : '' }}>Cash By Hand</option>
                        <option value="online_transfer" {{ request('method') === 'online_transfer' ? 'selected' : '' }}>Online Transfer</option>
                        <option value="banks_transfer" {{ request('method') === 'banks_transfer' ? 'selected' : '' }}>Banks Transfer</option>
                    </select>
                </div>
                <div class="col-md-4 d-flex align-items-end">
                    <button class="btn btn-sm btn-primary w-100" type="submit">Filter</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Date</th>
                            <th>Campus</th>
                            <th>User</th>
                            <th>Method</th>
                            <th>Transaction ID</th>
                            <th class="text-end">Total Payment</th>
                            <th>Remarks</th>
                            <th>Receipt</th>
                            <th>Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($settlements as $settlement)
                            <tr>
                                <td>{{ $settlement->id }}</td>
                                <td>{{ \Carbon\Carbon::parse($settlement->settlement_date)->format('d-m-Y') }}</td>
                                <td>{{ $settlement->campus === 'all' ? 'All' : ucfirst($settlement->campus) }}</td>
                                <td>
                                    {{ $settlement->created_by_name ?: ($settlement->user_name === 'all' ? 'All' : $settlement->user_name) }}
                                    @if($settlement->created_by_type)
                                        <small class="text-muted d-block">{{ $settlement->created_by_type }}</small>
                                    @endif
                                </td>
                                <td>{{ ucwords(str_replace('_', ' ', $settlement->method)) }}</td>
                                <td>{{ $settlement->transaction_id ?: '-' }}</td>
                                <td class="text-end">{{ number_format((float) $settlement->total_payment, 2) }}</td>
                                <td>{{ $settlement->remarks ?: '-' }}</td>
                                <td>
                                    @if($settlement->receipt_path)
                                        <a class="btn btn-sm btn-outline-info" target="_blank" href="{{ Storage::disk('public')->url($settlement->receipt_path) }}">View</a>
                                    @else
                                        <span class="text-muted">No Receipt</span>
                                    @endif
                                </td>
                                <td>
                                    <form action="{{ route('accounting.parent-wallet.accounts-settlement.delete', ['type' => 'balance-sheet', 'id' => $settlement->id]) }}" method="POST" onsubmit="return confirm('Delete this settlement?')">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-outline-danger">Delete</button>
                                    </form>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted py-3">No settlement records found.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>

            <div class="mt-2">
                {{ $settlements->links() }}
            </div>
        </div>
    </div>
</div>
@endsection

@push('styles')
<style>
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

    .csv-btn {
        background-color: #ff9800;
        color: white;
    }

    .csv-btn:hover {
        background-color: #f57c00;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(255, 152, 0, 0.3);
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

    .export-btn:active {
        transform: translateY(0);
    }
</style>
@endpush

