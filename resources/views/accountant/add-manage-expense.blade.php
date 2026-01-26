@extends('layouts.accountant')

@php
    use Illuminate\Support\Facades\Storage;
@endphp

@section('title', 'Add / Manage Expense - Accountant')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Add Management Expense</h4>
                <button type="button" class="btn btn-sm py-2 px-3 d-inline-flex align-items-center gap-1 rounded-8 expense-add-btn" data-bs-toggle="modal" data-bs-target="#expenseModal" onclick="resetForm()">
                    <span class="material-symbols-outlined" style="font-size: 16px;">add</span>
                    <span>Add New Expense</span>
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
                        <a href="{{ route('accountant.add-manage-expense.export', ['format' => 'excel']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn excel-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                            <span>Excel</span>
                        </a>
                        <a href="{{ route('accountant.add-manage-expense.export', ['format' => 'pdf']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn pdf-btn" target="_blank">
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
                            <input type="text" id="searchInput" class="form-control border-start-0 border-end-0" placeholder="Search expenses..." value="{{ request('search') }}" onkeypress="handleSearchKeyPress(event)" oninput="handleSearchInput(event)" style="padding: 4px 8px; font-size: 13px;">
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
                    <span>Management Expenses List</span>
                </h5>
            </div>
             
            <!-- Search Results Info -->
            @if(request('search'))
                <div class="search-results-info">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; color: #003471;">search</span>
                    <strong>Search Results:</strong> Showing results for "<strong>{{ request('search') }}</strong>"
                    @if(isset($expenses))
                        ({{ $expenses->total() }} {{ Str::plural('result', $expenses->total()) }} found)
                    @endif
                    <a href="{{ route('accountant.add-manage-expense') }}" class="text-decoration-none ms-2" style="color: #003471;">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">close</span>
                        Clear
                    </a>
                </div>
            @endif

            <div class="default-table-area" style="margin-top: 0;">
                <div class="table-responsive expense-table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Campus</th>
                                <th>Category</th>
                                <th>Title</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Invoice/Receipt</th>
                                <th>Date</th>
                                <th>Notify Admin</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($expenses) && $expenses->count() > 0)
                                @forelse($expenses as $expense)
                                    <tr>
                                        <td>{{ $loop->iteration + (($expenses->currentPage() - 1) * $expenses->perPage()) }}</td>
                                        <td>
                                            <span class="badge bg-info text-white">{{ $expense->campus }}</span>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary text-white">{{ $expense->category }}</span>
                                        </td>
                                        <td>
                                            <strong class="text-primary">{{ $expense->title }}</strong>
                                        </td>
                                        <td>
                                            <span class="text-muted" style="font-size: 11px;">{{ Str::limit($expense->description ?? 'N/A', 30) }}</span>
                                        </td>
                                        <td>
                                            <strong class="text-success">₹{{ number_format($expense->amount, 2) }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-primary text-white">{{ $expense->method }}</span>
                                        </td>
                                        <td>
                                            @if($expense->invoice_receipt)
                                                <span class="badge bg-success text-white">Yes</span>
                                            @else
                                                <span class="badge bg-warning text-dark">No</span>
                                            @endif
                                        </td>
                                        <td>
                                            <span class="text-muted">{{ $expense->date->format('d M Y') }}</span>
                                        </td>
                                        <td>
                                            @if($expense->notify_admin)
                                                <span class="badge bg-danger text-white">Yes</span>
                                            @else
                                                <span class="badge bg-light text-dark">No</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <a href="{{ route('accountant.add-manage-expense.show', $expense->id) }}" class="btn btn-sm btn-info px-2 py-1" title="View">
                                                    <span class="material-symbols-outlined" style="font-size: 14px; color: white;">visibility</span>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-primary px-2 py-1" title="Edit" onclick="editExpense({{ $expense->id }}, '{{ addslashes($expense->campus) }}', '{{ addslashes($expense->category) }}', '{{ addslashes($expense->title) }}', '{{ addslashes($expense->description ?? '') }}', {{ $expense->amount }}, '{{ addslashes($expense->method) }}', '{{ $expense->invoice_receipt ? Storage::url($expense->invoice_receipt) : '' }}', '{{ $expense->date->format('Y-m-d') }}', {{ $expense->notify_admin ? 'true' : 'false' }})">
                                                    <span class="material-symbols-outlined" style="font-size: 14px; color: white;">edit</span>
                                                </button>
                                                <a href="{{ route('accountant.add-manage-expense.print', $expense->id) }}" target="_blank" class="btn btn-sm btn-dark px-2 py-1" title="Print">
                                                    <span class="material-symbols-outlined" style="font-size: 14px; color: white;">print</span>
                                                </a>
                                                <button type="button" class="btn btn-sm btn-danger px-2 py-1" title="Delete" onclick="if(confirm('Are you sure you want to delete this expense?')) { document.getElementById('delete-form-{{ $expense->id }}').submit(); }">
                                                    <span class="material-symbols-outlined" style="font-size: 14px; color: white;">delete</span>
                                                </button>
                                                <form id="delete-form-{{ $expense->id }}" action="{{ route('accountant.add-manage-expense.destroy', $expense->id) }}" method="POST" class="d-none">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="11" class="text-center text-muted py-5">
                                            <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                            <p class="mt-2 mb-0">No expenses found.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            @else
                                <tr>
                                    <td colspan="11" class="text-center text-muted py-5">
                                        <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                        <p class="mt-2 mb-0">No expenses found.</p>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            @if(isset($expenses) && $expenses->hasPages())
                <div class="mt-3">
                    {{ $expenses->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Expense Modal -->
<div class="modal fade" id="expenseModal" tabindex="-1" aria-labelledby="expenseModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white p-3" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                <h5 class="modal-title fs-15 fw-semibold mb-0 d-flex align-items-center gap-2" id="expenseModalLabel" style="color: white !important;">
                    <span class="material-symbols-outlined" style="font-size: 20px; color: white !important;">receipt</span>
                    <span style="color: white !important;">Add New Expense</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 0.8;"></button>
            </div>
            <form id="expenseForm" method="POST" action="{{ route('accountant.add-manage-expense.store') }}" enctype="multipart/form-data">
                @csrf
                <div id="methodField"></div>
                <div class="modal-body p-3">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm expense-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">location_on</span>
                                </span>
                                <select class="form-control expense-input" name="campus" id="campus" required style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;">
                                    <option value="">Select Campus</option>
                                    @if(isset($campuses) && $campuses->count() > 0)
                                        @foreach($campuses as $campus)
                                            <option value="{{ $campus->campus_name ?? $campus }}" {{ ($defaultCampus ?? '') === ($campus->campus_name ?? $campus) ? 'selected' : '' }}>
                                                {{ $campus->campus_name ?? $campus }}
                                            </option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Category <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm expense-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">category</span>
                                </span>
                                <select class="form-control expense-input" name="category" id="category" required style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;">
                                    <option value="">Select Category</option>
                                    @if(isset($categories) && $categories->count() > 0)
                                        @foreach($categories as $cat)
                                            <option value="{{ $cat->category_name }}" data-campus="{{ $cat->campus }}">{{ $cat->category_name }} ({{ $cat->campus }})</option>
                                        @endforeach
                                    @endif
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Title <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm expense-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">title</span>
                                </span>
                                <input type="text" class="form-control expense-input" name="title" id="title" placeholder="Enter title" required>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Description</label>
                            <div class="input-group input-group-sm expense-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">description</span>
                                </span>
                                <textarea class="form-control expense-input" name="description" id="description" placeholder="Enter description" rows="3" style="resize: none;"></textarea>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Amount <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm expense-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">currency_rupee</span>
                                </span>
                                <input type="number" class="form-control expense-input" name="amount" id="amount" placeholder="Enter amount" step="0.01" min="0" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Method <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm expense-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">payment</span>
                                </span>
                                <select class="form-control expense-input" name="method" id="method" required style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;">
                                    <option value="">Select Payment Method</option>
                                    <option value="Bank">Bank</option>
                                    <option value="Wallet">Wallet</option>
                                    <option value="Transfer">Transfer</option>
                                    <option value="Card">Card</option>
                                    <option value="Check">Check</option>
                                    <option value="Deposit">Deposit</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Invoice/Receipt Image</label>
                            <div class="input-group input-group-sm expense-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">image</span>
                                </span>
                                <input type="file" class="form-control expense-input" name="invoice_receipt" id="invoice_receipt" accept="image/*" onchange="previewImage(this)">
                            </div>
                            <div id="imagePreview" class="mt-2" style="display: none;">
                                <img id="previewImg" src="" alt="Preview" style="max-width: 200px; max-height: 150px; border-radius: 8px; border: 1px solid #dee2e6;">
                                <button type="button" class="btn btn-sm btn-danger ms-2" onclick="removeImage()" style="margin-top: 5px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">delete</span>
                                    Remove
                                </button>
                            </div>
                            <div id="existingImage" class="mt-2" style="display: none;">
                                <img id="existingImg" src="" alt="Current Image" style="max-width: 200px; max-height: 150px; border-radius: 8px; border: 1px solid #dee2e6;">
                                <p class="text-muted small mt-1 mb-0">Current image (upload new to replace)</p>
                            </div>
                            <small class="text-muted">Max size: 5MB | Formats: JPG, PNG, GIF, WEBP</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Date <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm expense-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">calendar_today</span>
                                </span>
                                <input type="date" class="form-control expense-input" name="date" id="date" required>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="notify_admin" id="notify_admin" value="1">
                                <label class="form-check-label fs-12 fw-semibold" for="notify_admin" style="color: #003471;">
                                    Notify Admin
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-3" style="background-color: #f8f9fa; border-top: 1px solid #e9ecef;">
                    <button type="button" class="btn btn-sm py-2 px-4 rounded-8" data-bs-dismiss="modal" style="background-color: #6c757d; color: white; border: none; transition: all 0.3s ease;">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">close</span>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-sm py-2 px-4 rounded-8 expense-submit-btn">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
                        Save Expense
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Expense Form Styling */
    #expenseModal .expense-input-group {
        height: 36px;
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s ease;
        border: 1px solid #dee2e6;
    }
    
    #expenseModal .expense-input-group:focus-within {
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
        border-color: #003471;
    }
    
    #expenseModal .expense-input {
        height: 36px;
        font-size: 13px;
        padding: 0.5rem 0.75rem;
        border: none;
        border-left: 1px solid #e0e7ff;
        border-radius: 0 8px 8px 0;
        transition: all 0.3s ease;
    }
    
    #expenseModal .expense-input:focus {
        border-left-color: #003471;
        box-shadow: none;
        outline: none;
    }
    
    #expenseModal select.expense-input {
        appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23003471' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.75rem center;
        padding-right: 2.5rem;
    }
    
    #expenseModal textarea.expense-input {
        height: auto;
        min-height: 36px;
        padding-top: 0.5rem;
        padding-bottom: 0.5rem;
    }
    
    #expenseModal .input-group-text {
        height: 36px;
        padding: 0 0.75rem;
        display: flex;
        align-items: center;
        border: none;
        border-right: 1px solid #e0e7ff;
        border-radius: 8px 0 0 8px;
        transition: all 0.3s ease;
    }
    
    #expenseModal .expense-input-group:focus-within .input-group-text {
        background-color: #003471 !important;
        color: white !important;
        border-right-color: #003471;
    }
    
    #expenseModal .form-label {
        margin-bottom: 0.4rem;
        line-height: 1.3;
    }
    
    #expenseModal .expense-submit-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 52, 113, 0.25);
    }
    
    #expenseModal .expense-submit-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 52, 113, 0.35);
    }
    
    #expenseModal .expense-submit-btn:active {
        transform: translateY(0);
    }
    
    /* Add New Button Styling */
    .expense-add-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 6px rgba(0, 52, 113, 0.2);
    }
    
    .expense-add-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 52, 113, 0.3);
        color: white;
    }

    .expense-table-responsive {
        overflow-x: visible;
        overflow-y: auto;
        max-height: 520px;
    }
    
    .expense-add-btn:active {
        transform: translateY(0);
    }
    
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
        border-radius: 8px;
        overflow: hidden;
    }
    
    .default-table-area table thead {
        border-bottom: 1px solid #dee2e6;
        background-color: #f8f9fa;
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
        padding: 3px 6px;
        min-height: auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
    }
    
    .default-table-area .btn-sm .material-symbols-outlined {
        font-size: 14px !important;
        vertical-align: middle;
        color: white !important;
        line-height: 1;
        display: inline-block;
    }
    
    .default-table-area .btn-primary .material-symbols-outlined,
    .default-table-area .btn-danger .material-symbols-outlined {
        color: white !important;
    }
    
    .default-table-area table tbody td strong {
        font-size: 13px;
        font-weight: 600;
    }
</style>

<script>
// Reset form when opening modal for new expense
function resetForm() {
    document.getElementById('expenseForm').reset();
    document.getElementById('expenseForm').action = "{{ route('accountant.add-manage-expense.store') }}";
    document.getElementById('methodField').innerHTML = '';
    document.getElementById('expenseModalLabel').innerHTML = `
        <span class="material-symbols-outlined" style="font-size: 20px; color: white !important;">receipt</span>
        <span style="color: white !important;">Add New Expense</span>
    `;
    document.querySelector('.expense-submit-btn').innerHTML = `
        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
        Save Expense
    `;
    document.getElementById('notify_admin').checked = false;
    // Set today's date as default
    const today = new Date().toISOString().split('T')[0];
    document.getElementById('date').value = today;
    // Reset image previews
    document.getElementById('imagePreview').style.display = 'none';
    document.getElementById('existingImage').style.display = 'none';
    document.getElementById('invoice_receipt').value = '';
}

// Preview image function
function previewImage(input) {
    const preview = document.getElementById('imagePreview');
    const previewImg = document.getElementById('previewImg');
    const existingImage = document.getElementById('existingImage');
    
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            previewImg.src = e.target.result;
            preview.style.display = 'block';
            if (existingImage) {
                existingImage.style.display = 'none';
            }
        };
        reader.readAsDataURL(input.files[0]);
    } else {
        preview.style.display = 'none';
    }
}

// Remove image function
function removeImage() {
    const input = document.getElementById('invoice_receipt');
    const preview = document.getElementById('imagePreview');
    const existingImage = document.getElementById('existingImage');
    
    input.value = '';
    preview.style.display = 'none';
    if (existingImage) {
        existingImage.style.display = 'block';
    }
}

function restoreExpensePageState() {
    const key = `accountant:add-manage-expense:${window.location.pathname}${window.location.search}`;
    const raw = sessionStorage.getItem(key);
    if (!raw) return;
    try {
        const state = JSON.parse(raw);
        if (typeof state.scrollTop === 'number') {
            window.scrollTo(0, state.scrollTop);
        }
        const table = document.querySelector('.expense-table-responsive');
        if (table && typeof state.tableScrollTop === 'number') {
            table.scrollTop = state.tableScrollTop;
        }
    } catch (_) {
        // ignore
    }
}

function saveExpensePageState() {
    const table = document.querySelector('.expense-table-responsive');
    const key = `accountant:add-manage-expense:${window.location.pathname}${window.location.search}`;
    const state = {
        scrollTop: window.scrollY || 0,
        tableScrollTop: table ? table.scrollTop : 0
    };
    sessionStorage.setItem(key, JSON.stringify(state));
}

window.addEventListener('beforeunload', saveExpensePageState);
window.addEventListener('pageshow', restoreExpensePageState);

// Edit expense function
function editExpense(id, campus, category, title, description, amount, method, invoiceReceiptUrl, date, notifyAdmin) {
    document.getElementById('campus').value = campus;
    document.getElementById('category').value = category;
    document.getElementById('title').value = title;
    document.getElementById('description').value = description;
    document.getElementById('amount').value = amount;
    document.getElementById('method').value = method;
    document.getElementById('date').value = date;
    document.getElementById('notify_admin').checked = notifyAdmin;
    
    // Handle image display
    const existingImage = document.getElementById('existingImage');
    const existingImg = document.getElementById('existingImg');
    const imagePreview = document.getElementById('imagePreview');
    const invoiceInput = document.getElementById('invoice_receipt');
    
    invoiceInput.value = ''; // Clear file input
    
    if (invoiceReceiptUrl) {
        existingImg.src = invoiceReceiptUrl;
        existingImage.style.display = 'block';
        imagePreview.style.display = 'none';
    } else {
        existingImage.style.display = 'none';
        imagePreview.style.display = 'none';
    }
    document.getElementById('expenseForm').action = "{{ url('accountant/add-manage-expense') }}/" + id;
    document.getElementById('methodField').innerHTML = '@method("PUT")';
    document.getElementById('expenseModalLabel').innerHTML = `
        <span class="material-symbols-outlined" style="font-size: 20px; color: white !important;">edit</span>
        <span style="color: white !important;">Edit Expense</span>
    `;
    document.querySelector('.expense-submit-btn').innerHTML = `
        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
        Update Expense
    `;
    
    const modal = new bootstrap.Modal(document.getElementById('expenseModal'));
    modal.show();
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
            <h3 style="text-align: center; margin-bottom: 20px; color: #003471;">Management Expenses List</h3>
            ${printContents}
        </div>
    `;
    
    window.print();
    document.body.innerHTML = originalContents;
    window.location.reload();
}

// Set today's date as default when modal opens
document.addEventListener('DOMContentLoaded', function() {
    const dateInput = document.getElementById('date');
    if (dateInput && !dateInput.value) {
        const today = new Date().toISOString().split('T')[0];
        dateInput.value = today;
    }
    
    // Filter categories based on selected campus
    const campusSelect = document.getElementById('campus');
    const categorySelect = document.getElementById('category');
    
    if (campusSelect && categorySelect) {
        campusSelect.addEventListener('change', function() {
            const selectedCampus = this.value;
            const categoryOptions = categorySelect.querySelectorAll('option');
            
            // Show/hide categories based on campus
            categoryOptions.forEach(option => {
                if (option.value === '') {
                    option.style.display = 'block';
                } else {
                    const categoryCampus = option.getAttribute('data-campus');
                    if (selectedCampus && categoryCampus && categoryCampus !== selectedCampus) {
                        option.style.display = 'none';
                    } else {
                        option.style.display = 'block';
                    }
                }
            });
            
            // Reset category selection if current selection is hidden
            if (categorySelect.value && categorySelect.options[categorySelect.selectedIndex].style.display === 'none') {
                categorySelect.value = '';
            }
        });

        if (campusSelect.value) {
            campusSelect.dispatchEvent(new Event('change'));
        }
    }
});
</script>
@endsection
