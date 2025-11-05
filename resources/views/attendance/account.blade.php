@extends('layouts.app')

@section('title', 'Attendance Account')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Attendance Account</h4>
                <button type="button" class="btn btn-sm py-2 px-3 d-inline-flex align-items-center gap-1 rounded-8 account-add-btn" data-bs-toggle="modal" data-bs-target="#accountModal" onclick="resetForm()">
                    <span class="material-symbols-outlined" style="font-size: 16px;">add</span>
                    <span>Add New Attendance</span>
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
                        <a href="{{ route('attendance.account.export', ['format' => 'excel']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn excel-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                            <span>Excel</span>
                        </a>
                        <a href="{{ route('attendance.account.export', ['format' => 'csv']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn csv-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">file_present</span>
                            <span>CSV</span>
                        </a>
                        <a href="{{ route('attendance.account.export', ['format' => 'pdf']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn pdf-btn" target="_blank">
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
                            <input type="text" id="searchInput" class="form-control border-start-0 border-end-0" placeholder="Search by user name, ID card, campus..." value="{{ request('search') }}" onkeypress="handleSearchKeyPress(event)" oninput="handleSearchInput(event)" style="padding: 4px 8px; font-size: 13px;">
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
                    <span>Attendance Accounts List</span>
                </h5>
            </div>

            <!-- Search Results Info -->
            @if(request('search'))
                <div class="search-results-info">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; color: #003471;">search</span>
                    <strong>Search Results:</strong> Showing results for "<strong>{{ request('search') }}</strong>"
                    @if(isset($accounts))
                        ({{ $accounts->total() }} {{ Str::plural('result', $accounts->total()) }} found)
                    @endif
                    <a href="{{ route('attendance.account') }}" class="text-decoration-none ms-2" style="color: #003471;">
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
                                <th>User Name</th>
                                <th>User ID Card</th>
                                <th>Password</th>
                                <th>Campus</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($accounts) && $accounts->count() > 0)
                                @forelse($accounts as $account)
                                    <tr>
                                        <td>{{ $loop->iteration + (($accounts->currentPage() - 1) * $accounts->perPage()) }}</td>
                                        <td>
                                            <strong class="text-primary">{{ $account->user_name }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-info text-white">{{ $account->user_id_card }}</span>
                                        </td>
                                        <td>
                                            <code style="background-color: #f8f9fa; padding: 2px 6px; border-radius: 4px; font-size: 12px;">{{ $account->password }}</code>
                                        </td>
                                        <td>
                                            <span class="badge bg-secondary text-white">{{ $account->campus }}</span>
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <button type="button" class="btn btn-sm btn-primary px-2 py-0" title="Edit" onclick="editAccount({{ $account->id }}, '{{ addslashes($account->user_name) }}', '{{ addslashes($account->user_id_card) }}', '{{ addslashes($account->password) }}', '{{ addslashes($account->campus) }}')">
                                                    <span class="material-symbols-outlined" style="font-size: 14px; color: white;">edit</span>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger px-2 py-0" title="Delete" onclick="if(confirm('Are you sure you want to delete this attendance account?')) { document.getElementById('delete-form-{{ $account->id }}').submit(); }">
                                                    <span class="material-symbols-outlined" style="font-size: 14px; color: white;">delete</span>
                                                </button>
                                                <form id="delete-form-{{ $account->id }}" action="{{ route('attendance.account.destroy', $account->id) }}" method="POST" class="d-none">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-4">
                                            <div class="d-flex flex-column align-items-center gap-2">
                                                <span class="material-symbols-outlined" style="font-size: 48px; color: #dee2e6;">inbox</span>
                                                <p class="text-muted mb-0">No attendance accounts found.</p>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            @else
                                <tr>
                                    <td colspan="6" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center gap-2">
                                            <span class="material-symbols-outlined" style="font-size: 48px; color: #dee2e6;">inbox</span>
                                            <p class="text-muted mb-0">No attendance accounts found.</p>
                                        </div>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Pagination -->
            @if(isset($accounts) && $accounts->hasPages())
                <div class="d-flex justify-content-between align-items-center mt-3">
                    <div class="text-muted fs-13">
                        Showing {{ $accounts->firstItem() ?? 0 }} to {{ $accounts->lastItem() ?? 0 }} of {{ $accounts->total() }} entries
                    </div>
                    <div>
                        {{ $accounts->links() }}
                    </div>
                </div>
            @endif

            <!-- Important Note -->
            <div class="mt-4 p-3 rounded-8" style="background: linear-gradient(135deg, #fff3e0 0%, #ffe0b2 100%); border-left: 4px solid #ff9800;">
                <div class="d-flex align-items-start gap-2">
                    <span class="material-symbols-outlined" style="font-size: 20px; color: #e65100; flex-shrink: 0; margin-top: 2px;">info</span>
                    <div>
                        <h6 class="fw-semibold mb-2" style="color: #e65100;">Important Note:</h6>
                        <p class="mb-0 fs-13" style="color: #bf360c; line-height: 1.6;">
                            You can add multiple attendance accounts for a single campus. Each account will have access to log in to the mobile app and efficiently manage digital attendance through student ID card scanning. Usually, these accounts are provided to school gatekeepers to handle incoming student attendance smoothly.
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Account Modal -->
<div class="modal fade" id="accountModal" tabindex="-1" aria-labelledby="accountModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white p-3" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                <h5 class="modal-title fs-15 fw-semibold mb-0 d-flex align-items-center gap-2" id="accountModalLabel">
                    <span class="material-symbols-outlined" style="font-size: 20px;">person_add</span>
                    <span>Add New Attendance</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 0.8;"></button>
            </div>
            <form id="accountForm" method="POST" action="{{ route('attendance.account.store') }}">
                @csrf
                <div id="methodField"></div>
                <div class="modal-body p-3">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">User Name <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm account-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">person</span>
                                </span>
                                <input type="text" class="form-control account-input" name="user_name" id="user_name" placeholder="Enter user name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">User ID Card <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm account-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">badge</span>
                                </span>
                                <input type="text" class="form-control account-input" name="user_id_card" id="user_id_card" placeholder="Enter user ID card" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Password <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm account-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">lock</span>
                                </span>
                                <input type="password" class="form-control account-input" name="password" id="password" placeholder="Enter password (min 6 characters)" required minlength="6">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">For Campus <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm account-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">location_on</span>
                                </span>
                                <select class="form-select account-input" name="campus" id="campus" required>
                                    <option value="">Select Campus</option>
                                    @if(isset($campuses) && count($campuses) > 0)
                                        @foreach($campuses as $campus)
                                            <option value="{{ $campus }}">{{ $campus }}</option>
                                        @endforeach
                                    @else
                                        <option value="Main Campus">Main Campus</option>
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-3" style="background-color: #f8f9fa; border-top: 1px solid #e9ecef;">
                    <button type="button" class="btn btn-sm py-2 px-4 rounded-8" data-bs-dismiss="modal" style="background-color: #6c757d; color: white; border: none; transition: all 0.3s ease;">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">close</span>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-sm py-2 px-4 rounded-8 account-submit-btn">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
                        Add Account
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Account Form Styling */
    #accountModal .account-input-group {
        height: 36px;
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s ease;
        border: 1px solid #dee2e6;
    }
    
    #accountModal .account-input-group:focus-within {
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
        border-color: #003471;
    }
    
    #accountModal .account-input {
        height: 36px;
        font-size: 13px;
        padding: 0.5rem 0.75rem;
        border: none;
        border-left: 1px solid #e0e7ff;
        border-radius: 0 8px 8px 0;
        transition: all 0.3s ease;
    }
    
    #accountModal .account-input:focus {
        border-left-color: #003471;
        box-shadow: none;
        outline: none;
    }
    
    #accountModal .input-group-text {
        height: 36px;
        padding: 0 0.75rem;
        display: flex;
        align-items: center;
        border: none;
        border-right: 1px solid #e0e7ff;
        border-radius: 8px 0 0 8px;
        transition: all 0.3s ease;
    }
    
    #accountModal select.account-input {
        border-left: 1px solid #e0e7ff;
    }
    
    .account-add-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        transition: all 0.3s ease;
    }
    
    .account-add-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #0056c3 100%);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 52, 113, 0.3);
    }
    
    .account-submit-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        transition: all 0.3s ease;
    }
    
    .account-submit-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #0056c3 100%);
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 52, 113, 0.3);
    }

    .search-results-info {
        background-color: #e3f2fd;
        padding: 10px 15px;
        border-radius: 8px;
        margin-bottom: 15px;
        font-size: 13px;
        color: #1565c0;
    }

    .export-btn {
        border-radius: 6px;
        font-size: 12px;
        transition: all 0.3s ease;
        border: 1px solid transparent;
    }

    .excel-btn {
        background-color: #28a745;
        color: white;
    }

    .excel-btn:hover {
        background-color: #218838;
        color: white;
    }

    .csv-btn {
        background-color: #17a2b8;
        color: white;
    }

    .csv-btn:hover {
        background-color: #138496;
        color: white;
    }

    .pdf-btn {
        background-color: #dc3545;
        color: white;
    }

    .pdf-btn:hover {
        background-color: #c82333;
        color: white;
    }

    .print-btn {
        background-color: #6c757d;
        color: white;
    }

    .print-btn:hover {
        background-color: #5a6268;
        color: white;
    }

    .search-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
    }

    .search-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #0056c3 100%);
        color: white;
    }

    .search-input-group:focus-within {
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
    }
</style>

<script>
    function resetForm() {
        document.getElementById('accountForm').reset();
        document.getElementById('methodField').innerHTML = '';
        document.getElementById('accountModalLabel').innerHTML = '<span class="material-symbols-outlined" style="font-size: 20px;">person_add</span><span>Add New Attendance</span>';
        document.getElementById('accountForm').action = '{{ route("attendance.account.store") }}';
    }

    function editAccount(id, user_name, user_id_card, password, campus) {
        resetForm();
        document.getElementById('user_name').value = user_name;
        document.getElementById('user_id_card').value = user_id_card;
        document.getElementById('password').value = password;
        document.getElementById('campus').value = campus;
        
        document.getElementById('accountModalLabel').innerHTML = '<span class="material-symbols-outlined" style="font-size: 20px;">edit</span><span>Edit Attendance Account</span>';
        document.getElementById('accountForm').action = '{{ route("attendance.account.update", ":id") }}'.replace(':id', id);
        document.getElementById('methodField').innerHTML = '@method("PUT")';
        
        new bootstrap.Modal(document.getElementById('accountModal')).show();
    }

    function updateEntriesPerPage(value) {
        const url = new URL(window.location.href);
        url.searchParams.set('per_page', value);
        window.location.href = url.toString();
    }

    function handleSearchKeyPress(event) {
        if (event.key === 'Enter') {
            event.preventDefault();
            performSearch();
        }
    }

    function handleSearchInput(event) {
        // Optional: Add real-time search functionality
    }

    function performSearch() {
        const searchValue = document.getElementById('searchInput').value.trim();
        const url = new URL(window.location.href);
        
        if (searchValue) {
            url.searchParams.set('search', searchValue);
        } else {
            url.searchParams.delete('search');
        }
        
        url.searchParams.delete('page'); // Reset to first page on new search
        window.location.href = url.toString();
    }

    function clearSearch() {
        const url = new URL(window.location.href);
        url.searchParams.delete('search');
        url.searchParams.delete('page');
        window.location.href = url.toString();
    }

    function printTable() {
        window.print();
    }
</script>
@endsection
