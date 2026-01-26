@extends('layouts.app')

@section('title', 'Discount Student')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-2">
                <h4 class="mb-0 fs-16 fw-semibold">Discount Student</h4>
                <button type="button" class="btn btn-sm px-3 py-2" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); color: white;" data-bs-toggle="modal" data-bs-target="#discountModal" onclick="resetDiscountForm()">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">add</span>
                    Add Discount
                </button>
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
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-2 p-2 rounded-8" style="background-color: #f8f9fa; border: 1px solid #e9ecef;">
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

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <div class="d-flex align-items-center gap-2">
                        <label for="searchInput" class="mb-0 fs-13 fw-medium text-dark">Search:</label>
                        <div class="input-group input-group-sm search-input-group" style="width: 280px;">
                            <span class="input-group-text bg-light border-end-0" style="background-color: #f0f4ff !important; border-color: #e0e7ff; padding: 4px 8px;">
                                <span class="material-symbols-outlined" style="font-size: 14px; color: #003471;">search</span>
                            </span>
                            <input type="text" id="searchInput" class="form-control border-start-0 border-end-0" placeholder="Search discounts..." value="{{ request('search') }}" onkeypress="handleSearchKeyPress(event)" oninput="handleSearchInput(event)" style="padding: 4px 8px; font-size: 13px;">
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

            <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                    <span class="material-symbols-outlined" style="font-size: 18px;">percent</span>
                    <span>Discount Students List</span>
                </h5>
            </div>

            <div class="default-table-area" style="margin-top: 0;">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>Student Code</th>
                                <th>Name</th>
                                <th>Parent</th>
                                <th>Class</th>
                                <th>Section</th>
                                <th>Discount Reason</th>
                                <th>Amount</th>
                                <th class="text-end">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($discounts as $discount)
                                <tr>
                                    <td>
                                        <span class="badge bg-info text-white">{{ $discount->student_code }}</span>
                                    </td>
                                    <td>
                                        <strong class="text-primary">{{ $discount->student->student_name ?? 'N/A' }}</strong>
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ $discount->student->father_name ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ $discount->student->class ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ $discount->student->section ?? 'N/A' }}</span>
                                    </td>
                                    <td>
                                        <span class="text-muted">{{ $discount->discount_title }}</span>
                                    </td>
                                    <td>
                                        <strong class="text-success">₹{{ number_format($discount->discount_amount, 2) }}</strong>
                                    </td>
                                    <td class="text-end">
                                        <button type="button" class="btn btn-sm btn-warning px-2 py-1" data-bs-toggle="modal" data-bs-target="#discountModal" onclick="editDiscount(this)" data-id="{{ $discount->id }}" data-student-code="{{ $discount->student_code }}" data-title="{{ $discount->discount_title }}" data-amount="{{ $discount->discount_amount }}">
                                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">edit</span>
                                            Edit
                                        </button>
                                        <form action="{{ route('accounting.parent-wallet.discount-student.delete', $discount->id) }}" method="POST" style="display: inline-block;" onsubmit="return confirm('Are you sure you want to delete this discount?');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger px-2 py-1">
                                                <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">delete</span>
                                                Delete
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted">No discounts found.</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>

                @if($discounts->hasPages())
                    <div class="d-flex justify-content-between align-items-center mt-3 px-3">
                        <div class="text-muted" style="font-size: 13px;">
                            Showing {{ $discounts->firstItem() }} to {{ $discounts->lastItem() }} of {{ $discounts->total() }} discounts
                        </div>
                        <div>
                            {{ $discounts->links() }}
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<!-- Discount Modal -->
<div class="modal fade" id="discountModal" tabindex="-1" aria-labelledby="discountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form id="discountForm" method="POST" action="{{ route('accounting.parent-wallet.discount-student.store') }}">
                @csrf
                <input type="hidden" name="_method" id="discountFormMethod" value="POST">
                <div class="modal-header" style="background-color: #003471;">
                    <h5 class="modal-title text-white" id="discountModalLabel">Discount Student</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-2">
                        <label for="discount_student_code" class="form-label mb-1 fs-13 fw-medium">Student Code <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm compact-field" id="discount_student_code" name="student_code" required>
                    </div>
                    <div class="mb-2">
                        <label for="discount_title" class="form-label mb-1 fs-13 fw-medium">Discount Title <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm compact-field" id="discount_title" name="discount_title" required>
                    </div>
                    <div class="mb-2">
                        <label for="discount_amount" class="form-label mb-1 fs-13 fw-medium">Discount Amount <span class="text-danger">*</span></label>
                        <input type="number" step="0.01" min="0" class="form-control form-control-sm compact-field" id="discount_amount" name="discount_amount" required>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-sm btn-primary" id="discountSubmitBtn">Save</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .search-btn {
        background-color: #003471;
        border-color: #003471;
        color: white;
    }
    
    .search-btn:hover {
        background-color: #004a9f;
        border-color: #004a9f;
        color: white;
    }
    
    .search-input-group .form-control:focus {
        border-color: #003471;
        box-shadow: 0 0 0 0.2rem rgba(0, 52, 113, 0.25);
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
    
    .default-table-area table tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    .default-table-area .badge {
        font-size: 11px;
        padding: 3px 6px;
        font-weight: 500;
        border-radius: 4px;
    }
    
    .default-table-area .btn-sm {
        font-size: 11px;
        padding: 4px 8px;
        min-height: auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }

    .compact-field {
        height: 28px;
        font-size: 12px;
        padding: 2px 6px;
    }
</style>

<script>
function updateEntriesPerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

function handleSearchInput(event) {
    if (event.target.value === '') {
        const url = new URL(window.location.href);
        url.searchParams.delete('search');
        url.searchParams.delete('page');
        window.location.href = url.toString();
    }
}

function handleSearchKeyPress(event) {
    if (event.key === 'Enter') {
        performSearch();
    }
}

function performSearch() {
    const searchValue = document.getElementById('searchInput').value.trim();
    const url = new URL(window.location.href);
    if (searchValue) {
        url.searchParams.set('search', searchValue);
    } else {
        url.searchParams.delete('search');
    }
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

function clearSearch() {
    const url = new URL(window.location.href);
    url.searchParams.delete('search');
    url.searchParams.delete('page');
    window.location.href = url.toString();
}

function resetDiscountForm() {
    const form = document.getElementById('discountForm');
    form.reset();
    form.action = `{{ route('accounting.parent-wallet.discount-student.store') }}`;
    document.getElementById('discountFormMethod').value = 'POST';
    document.getElementById('discountSubmitBtn').textContent = 'Save';
}

function editDiscount(button) {
    const id = button.dataset.id;
    const studentCode = button.dataset.studentCode;
    const title = button.dataset.title;
    const amount = button.dataset.amount;

    const form = document.getElementById('discountForm');
    form.action = `{{ url('/accounting/parent-wallet/discount-student') }}/${id}`;
    document.getElementById('discountFormMethod').value = 'PUT';
    document.getElementById('discount_student_code').value = studentCode;
    document.getElementById('discount_title').value = title;
    document.getElementById('discount_amount').value = amount;
    document.getElementById('discountSubmitBtn').textContent = 'Update';
}
</script>
@endsection

