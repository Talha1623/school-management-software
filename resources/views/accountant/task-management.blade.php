@extends('layouts.accountant')

@section('title', 'Task Management')

@section('content')
<div class="row">
    <!-- Summary Cards -->
    <div class="row mb-3">
        <div class="col-md-3 mb-2">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 8px; overflow: hidden;">
                <div class="card-body p-3" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <div>
                        <h6 class="text-white-50 mb-1" style="font-size: 11px; font-weight: 500;">Total Tasks</h6>
                        <h3 class="text-white mb-0" style="font-size: 24px; font-weight: 700;">{{ $totalTasks }}</h3>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-2">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 8px; overflow: hidden;">
                <div class="card-body p-3" style="background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);">
                    <div>
                        <h6 class="text-white mb-1" style="font-size: 11px; font-weight: 500; opacity: 0.9;">Pending Tasks</h6>
                        <h3 class="text-white mb-0" style="font-size: 24px; font-weight: 700;">{{ $pendingTasks }}</h3>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-2">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 8px; overflow: hidden;">
                <div class="card-body p-3" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
                    <div>
                        <h6 class="text-white mb-1" style="font-size: 11px; font-weight: 500; opacity: 0.9;">Active Tasks</h6>
                        <h3 class="text-white mb-0" style="font-size: 24px; font-weight: 700;">{{ $activeTasks }}</h3>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-3 mb-2">
            <div class="card border-0 shadow-sm h-100" style="border-radius: 8px; overflow: hidden;">
                <div class="card-body p-3" style="background: linear-gradient(135deg, #17a2b8 0%, #0dcaf0 100%);">
                    <div>
                        <h6 class="text-white mb-1" style="font-size: 11px; font-weight: 500; opacity: 0.9;">Completed Tasks</h6>
                        <h3 class="text-white mb-0" style="font-size: 24px; font-weight: 700;">{{ $completedTasks }}</h3>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Task Management</h4>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(Auth::guard('accountant')->check())
                @php
                    $currentAccountant = Auth::guard('accountant')->user();
                @endphp
                <div class="alert alert-info alert-dismissible fade show" role="alert">
                    <strong>Debug Info:</strong> Logged in as: <strong>{{ $currentAccountant->name }}</strong> ({{ $currentAccountant->email }})
                    <br>Looking for tasks where assign_to contains: <strong>{{ $currentAccountant->name }}</strong>
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
                    <!-- Search -->
                    <div class="d-flex align-items-center gap-2">
                        <label for="searchInput" class="mb-0 fs-13 fw-medium text-dark">Search:</label>
                        <div class="input-group input-group-sm search-input-group" style="width: 280px;">
                            <span class="input-group-text bg-light border-end-0" style="background-color: #f0f4ff !important; border-color: #e0e7ff; padding: 4px 8px;">
                                <span class="material-symbols-outlined" style="font-size: 14px; color: #003471;">search</span>
                            </span>
                            <input type="text" id="searchInput" class="form-control border-start-0 border-end-0" placeholder="Search by title, type..." value="{{ request('search') }}" onkeypress="handleSearchKeyPress(event)" style="padding: 4px 8px; font-size: 12px;">
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
                    <span>Tasks List</span>
                </h5>
            </div>

            <!-- Search Results Info -->
            @if(request('search'))
                <div class="search-results-info">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; color: #003471;">search</span>
                    <strong>Search Results:</strong> Showing results for "<strong>{{ request('search') }}</strong>" 
                    ({{ $tasks->total() }} {{ Str::plural('result', $tasks->total()) }} found)
                    <a href="{{ route('accountant.task-management') }}" class="text-decoration-none ms-2" style="color: #003471;">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">close</span>
                        Clear
                    </a>
                </div>
            @endif

            <!-- Tasks Table -->
            <div class="default-table-area" style="margin-top: 0;">
                <div class="table-responsive" style="max-height: none; overflow: visible; overflow-x: auto;">
                    <table class="table table-sm table-hover" style="margin-bottom: 0; white-space: nowrap;">
                        <thead>
                            <tr>
                                <th style="padding: 12px 15px; font-size: 14px;">Task</th>
                                <th style="padding: 12px 15px; font-size: 14px;">Description</th>
                                <th style="padding: 12px 15px; font-size: 14px;">Type</th>
                                <th style="padding: 12px 15px; font-size: 14px;">Assigned To</th>
                                <th style="padding: 12px 15px; font-size: 14px;">Assign By</th>
                                <th style="padding: 12px 15px; font-size: 14px;">Creation Date</th>
                                <th style="padding: 12px 15px; font-size: 14px;">Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tasks as $task)
                                <tr>
                                    <td style="padding: 12px 15px; font-size: 14px;">
                                        <strong class="text-dark">{{ $task->task_title ?? 'N/A' }}</strong>
                                    </td>
                                    <td style="padding: 12px 15px; font-size: 14px;">
                                        <span class="text-muted">{{ Str::limit($task->description ?? 'N/A', 50) }}</span>
                                    </td>
                                    <td style="padding: 12px 15px; font-size: 14px;">
                                        @if($task->type)
                                            <span class="badge bg-info text-white" style="font-size: 11px;">{{ $task->type }}</span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td style="padding: 12px 15px; font-size: 14px;">
                                        @if($task->assign_to)
                                            <span class="badge bg-success text-white" style="font-size: 11px;">
                                                <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">person</span>
                                                {{ $task->assign_to }}
                                            </span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td style="padding: 12px 15px; font-size: 14px;">
                                        @if($task->assign_by ?? null)
                                            <span class="badge bg-primary text-white" style="font-size: 11px;">
                                                <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">person_add</span>
                                                {{ $task->assign_by }}
                                            </span>
                                        @else
                                            <span class="text-muted">N/A</span>
                                        @endif
                                    </td>
                                    <td style="padding: 12px 15px; font-size: 14px;">
                                        <span class="text-muted">{{ $task->created_at ? $task->created_at->format('Y-m-d') : 'N/A' }}</span>
                                    </td>
                                    <td style="padding: 12px 15px; font-size: 14px;">
                                        <div class="dropdown">
                                            @php
                                                $status = $task->status ?? 'Pending';
                                                $statusColors = [
                                                    'Pending' => 'status-pending',
                                                    'Accepted' => 'status-accepted',
                                                    'Returned' => 'status-returned',
                                                    'Completed' => 'status-completed'
                                                ];
                                                $statusClass = $statusColors[$status] ?? 'status-pending';
                                            @endphp
                                            <button class="status-btn {{ $statusClass }}" type="button" id="statusDropdown{{ $task->id }}" data-bs-toggle="dropdown" aria-expanded="false">
                                                <span class="status-text">{{ $status }}</span>
                                                <span class="material-symbols-outlined status-icon">arrow_drop_down</span>
                                            </button>
                                            <ul class="dropdown-menu status-dropdown-menu" aria-labelledby="statusDropdown{{ $task->id }}">
                                                <li><a class="dropdown-item status-option" href="#" onclick="updateTaskStatus({{ $task->id }}, 'Pending'); return false;">
                                                    <span class="status-indicator status-pending-indicator"></span>
                                                    <span>Pending</span>
                                                </a></li>
                                                <li><a class="dropdown-item status-option" href="#" onclick="updateTaskStatus({{ $task->id }}, 'Accepted'); return false;">
                                                    <span class="status-indicator status-accepted-indicator"></span>
                                                    <span>Accept Task</span>
                                                </a></li>
                                                <li><a class="dropdown-item status-option" href="#" onclick="updateTaskStatus({{ $task->id }}, 'Returned'); return false;">
                                                    <span class="status-indicator status-returned-indicator"></span>
                                                    <span>Return/Cancel</span>
                                                </a></li>
                                                <li><a class="dropdown-item status-option" href="#" onclick="updateTaskStatus({{ $task->id }}, 'Completed'); return false;">
                                                    <span class="status-indicator status-completed-indicator"></span>
                                                    <span>Mark as Complete</span>
                                                </a></li>
                                            </ul>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-5">
                                        <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                        <p class="mt-2 mb-0">No tasks found.</p>
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            @if($tasks->hasPages())
                <div class="mt-3">
                    {{ $tasks->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<style>
.default-table-area {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
}

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
    padding: 12px 15px;
    font-size: 14px;
    font-weight: 600;
    vertical-align: middle;
    line-height: 1.5;
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
    padding: 12px 15px;
    font-size: 14px;
    vertical-align: middle;
    line-height: 1.5;
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
    padding-left: 15px;
}

.default-table-area table thead th:last-child,
.default-table-area table tbody td:last-child {
    padding-right: 15px;
}

.default-table-area table tbody tr:first-child td {
    border-top: none;
}

.default-table-area tbody tr:hover {
    background-color: #f8f9fa;
}

.default-table-area .badge {
    font-size: 11px;
    padding: 4px 8px;
}

.status-btn {
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 12px;
    font-weight: 500;
    cursor: pointer;
    display: inline-flex;
    align-items: center;
    gap: 4px;
    transition: all 0.2s;
}

.status-pending {
    background-color: #ffc107;
    color: #000;
}

.status-accepted {
    background-color: #28a745;
    color: #fff;
}

.status-returned {
    background-color: #dc3545;
    color: #fff;
}

.status-completed {
    background-color: #17a2b8;
    color: #fff;
}

.status-btn:hover {
    opacity: 0.9;
    transform: translateY(-1px);
}

.status-icon {
    font-size: 16px !important;
}

.status-dropdown-menu {
    min-width: 180px;
    padding: 4px;
}

.status-option {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 8px 12px;
    font-size: 13px;
}

.status-indicator {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    display: inline-block;
}

.status-pending-indicator {
    background-color: #ffc107;
}

.status-accepted-indicator {
    background-color: #28a745;
}

.status-returned-indicator {
    background-color: #dc3545;
}

.status-completed-indicator {
    background-color: #17a2b8;
}

/* Search Input Group */
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
    font-size: 12px;
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

.search-results-info {
    padding: 8px 12px;
    background-color: #e7f3ff;
    border-left: 3px solid #003471;
    border-radius: 4px;
    margin-bottom: 15px;
    font-size: 13px;
}
</style>

<script>
function updateEntriesPerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

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
    window.location.href = url.toString();
}

function handleSearchKeyPress(event) {
    if (event.key === 'Enter') {
        event.preventDefault();
        performSearch();
    }
}

function clearSearch() {
    const url = new URL(window.location.href);
    url.searchParams.delete('search');
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

function updateTaskStatus(taskId, status) {
        fetch(`{{ url('/task-management') }}/${taskId}/status`, {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || document.querySelector('input[name="_token"]')?.value
        },
        body: JSON.stringify({ status: status })
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Reload page to show updated status
            window.location.reload();
        } else {
            alert('Error updating task status: ' + (data.message || 'Unknown error'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error updating task status. Please try again.');
    });
}
</script>
@endsection
