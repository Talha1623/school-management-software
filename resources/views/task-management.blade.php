@extends('layouts.app')

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
                @if(!isset($isStaffTeacher) || !$isStaffTeacher)
                    <button type="button" class="btn btn-sm py-2 px-3 d-inline-flex align-items-center gap-1 rounded-8 task-add-btn" data-bs-toggle="modal" data-bs-target="#taskModal" onclick="resetForm()">
                        <span class="material-symbols-outlined" style="font-size: 16px;">add</span>
                        <span>Add New Task</span>
                    </button>
                @endif
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
                        <a href="{{ route('task-management.export', ['format' => 'excel']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn excel-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                            <span>Excel</span>
                        </a>
                        <a href="{{ route('task-management.export', ['format' => 'csv']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn csv-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">file_present</span>
                            <span>CSV</span>
                        </a>
                        <a href="{{ route('task-management.export', ['format' => 'pdf']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn pdf-btn" target="_blank">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">picture_as_pdf</span>
                            <span>PDF</span>
                        </a>
                        <button type="button" class="btn btn-sm px-2 py-1 export-btn print-btn" onclick="printTable()">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">print</span>
                            <span>Print</span>
                        </button>
                        @if(!isset($isStaffTeacher) || !$isStaffTeacher)
                            <form action="{{ route('task-management.delete-all') }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete ALL tasks? This action cannot be undone!');">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="btn btn-sm px-2 py-1 export-btn delete-all-btn">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">delete_sweep</span>
                                    <span>Delete All</span>
                                </button>
                            </form>
                        @endif
                    </div>
                    
                    <!-- Search -->
                    <div class="d-flex align-items-center gap-2">
                        <label for="searchInput" class="mb-0 fs-13 fw-medium text-dark">Search:</label>
                        <div class="input-group input-group-sm search-input-group" style="width: 280px;">
                            <span class="input-group-text bg-light border-end-0" style="background-color: #f0f4ff !important; border-color: #e0e7ff; padding: 4px 8px;">
                                <span class="material-symbols-outlined" style="font-size: 14px; color: #003471;">search</span>
                            </span>
                            <input type="text" id="searchInput" class="form-control border-start-0 border-end-0" placeholder="Search by title, description, type..." value="{{ request('search') }}" onkeypress="handleSearchKeyPress(event)" style="padding: 4px 8px; font-size: 12px;">
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
                    <a href="{{ route('task-management') }}" class="text-decoration-none ms-2" style="color: #003471;">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">close</span>
                        Clear
                    </a>
                </div>
            @endif

            <div class="default-table-area" style="margin-top: 0;">
                <div class="table-responsive" style="max-height: none; overflow: visible; overflow-x: auto;">
                    <table class="table table-sm table-hover" style="margin-bottom: 0; white-space: nowrap;">
                        <thead>
                            <tr>
                                <th style="padding: 12px 15px; font-size: 14px;">#</th>
                                <th style="padding: 12px 15px; font-size: 14px;">Task Title</th>
                                <th style="padding: 12px 15px; font-size: 14px;">Description</th>
                                <th style="padding: 12px 15px; font-size: 14px;">Type</th>
                                <th style="padding: 12px 15px; font-size: 14px;">Assign To</th>
                                <th style="padding: 12px 15px; font-size: 14px;">Status</th>
                                <th style="padding: 12px 15px; font-size: 14px;">Created At</th>
                                <th style="padding: 12px 15px; font-size: 14px; text-align: center;">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($tasks as $task)
                                <tr>
                                    <td style="padding: 12px 15px; font-size: 14px;">{{ $loop->iteration + (($tasks->currentPage() - 1) * $tasks->perPage()) }}</td>
                                    <td style="padding: 12px 15px; font-size: 14px;">
                                        <strong class="text-primary">{{ $task->task_title }}</strong>
                                    </td>
                                    <td style="padding: 12px 15px; font-size: 14px;">
                                        <span class="text-muted">{{ Str::limit($task->description ?? 'N/A', 50) }}</span>
                                    </td>
                                    <td style="padding: 12px 15px; font-size: 14px;">
                                        @if($task->type)
                                            @if(strtolower($task->type) == 'urgent')
                                                <span class="badge bg-danger text-white" style="font-size: 11px;">Urgent</span>
                                            @elseif(strtolower($task->type) == 'normal')
                                                <span class="badge bg-info text-white" style="font-size: 11px;">Normal</span>
                                            @else
                                                <span class="badge bg-secondary text-white" style="font-size: 11px;">{{ $task->type }}</span>
                                            @endif
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
                                    <td style="padding: 12px 15px; font-size: 14px;">
                                        <span class="text-muted">{{ $task->created_at ? $task->created_at->format('Y-m-d') : 'N/A' }}</span>
                                    </td>
                                    <td style="padding: 12px 15px; font-size: 14px; text-align: center;">
                                        @if(!isset($isStaffTeacher) || !$isStaffTeacher)
                                            <div class="d-inline-flex gap-1">
                                                <button type="button" class="btn btn-sm btn-primary px-2 py-1" onclick="editTask({{ $task->id }})" title="Edit">
                                                    <span class="material-symbols-outlined" style="font-size: 14px; color: white;">edit</span>
                                                </button>
                                                <form action="{{ route('task-management.destroy', $task) }}" method="POST" class="d-inline" onsubmit="return confirm('Are you sure you want to delete this task?');">
                                                    @csrf
                                                    @method('DELETE')
                                                    <button type="submit" class="btn btn-sm btn-danger px-2 py-1" title="Delete">
                                                        <span class="material-symbols-outlined" style="font-size: 14px; color: white;">delete</span>
                                                    </button>
                                                </form>
                                            </div>
                                        @else
                                            <span class="text-muted" style="font-size: 12px;">View Only</span>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="8" class="text-center text-muted py-5">
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

<!-- Task Modal -->
<div class="modal fade" id="taskModal" tabindex="-1" aria-labelledby="taskModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered" style="max-width: 600px;">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white p-2" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                <h5 class="modal-title fw-semibold mb-0 d-flex align-items-center gap-2" id="taskModalLabel" style="font-size: 14px; color: white;">
                    <span class="material-symbols-outlined" style="font-size: 18px; color: white;">task_alt</span>
                    <span style="color: white;">Add New Task</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 0.8;"></button>
            </div>
            <form id="taskForm" method="POST" action="{{ route('task-management.store') }}">
                @csrf
                <div id="methodField"></div>
                <div class="modal-body p-3" style="max-height: 80vh; overflow-y: auto;">
                    <div class="row g-3">
                        <!-- Task Title -->
                        <div class="col-12">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Task Title <span class="text-danger">*</span></label>
                            <div class="input-group task-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">title</span>
                                </span>
                                <input type="text" class="form-control task-input" name="task_title" id="task_title" placeholder="Enter task title" required style="font-size: 12px;">
                            </div>
                        </div>

                        <!-- Description -->
                        <div class="col-12">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Description</label>
                            <div class="input-group task-input-group">
                                <span class="input-group-text align-items-start" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471; padding-top: 8px;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">description</span>
                                </span>
                                <textarea class="form-control task-input" name="description" id="description" rows="4" placeholder="Enter task description" style="font-size: 12px;"></textarea>
                            </div>
                        </div>

                        <!-- Type -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Type</label>
                            <div class="input-group task-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">category</span>
                                </span>
                                <select class="form-control task-input" name="type" id="type" style="font-size: 12px; border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;">
                                    <option value="">Select Type</option>
                                    <option value="normal">Normal</option>
                                    <option value="urgent">Urgent</option>
                                </select>
                            </div>
                        </div>

                        <!-- Assign To -->
                        <div class="col-md-6">
                            <label class="form-label mb-1 fw-semibold" style="color: #003471; font-size: 11px;">Assign To</label>
                            <div class="input-group task-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 14px;">person</span>
                                </span>
                                <select class="form-control task-input" name="assign_to" id="assign_to" style="font-size: 12px; border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;">
                                    <option value="">Select Person</option>
                                    @if($admins->count() > 0)
                                        <optgroup label="Admins">
                                            @foreach($admins as $admin)
                                                <option value="{{ $admin->name }}">{{ $admin->name }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                    @if($accountants->count() > 0)
                                        <optgroup label="Accountants">
                                            @foreach($accountants as $accountant)
                                                <option value="{{ $accountant->name }}">{{ $accountant->name }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                    @if($staff->count() > 0)
                                        <optgroup label="Staff">
                                            @foreach($staff as $staffMember)
                                                <option value="{{ $staffMember->name }}">{{ $staffMember->name }}</option>
                                            @endforeach
                                        </optgroup>
                                    @endif
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-2" style="background-color: #f8f9fa; border-top: 1px solid #e9ecef;">
                    <button type="button" class="btn btn-sm py-1 px-3 rounded-8" data-bs-dismiss="modal" style="background-color: #6c757d; color: white; border: none; transition: all 0.3s ease; font-size: 12px;">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">close</span>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-sm py-1 px-3 rounded-8 task-submit-btn" style="font-size: 12px;">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">add</span>
                        Add Task
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Task Form Styling */
    #taskModal .task-input-group {
        min-height: 32px;
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s ease;
        border: 1px solid #dee2e6;
    }
    
    #taskModal .task-input-group:focus-within {
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
        border-color: #003471;
    }
    
    #taskModal .task-input {
        min-height: 32px;
        font-size: 12px;
        padding: 0.4rem 0.65rem;
        border: none;
        border-left: 1px solid #e0e7ff;
        border-radius: 0 8px 8px 0;
        transition: all 0.3s ease;
        height: auto;
    }
    
    #taskModal .task-input:focus {
        border-left-color: #003471;
        box-shadow: none;
        outline: none;
    }
    
    #taskModal textarea.task-input {
        min-height: 80px;
        border-left: 1px solid #e0e7ff;
        padding-top: 0.4rem;
        font-size: 12px;
        height: auto;
        resize: vertical;
    }
    
    #taskModal .input-group-text {
        min-height: 32px;
        padding: 0 0.65rem;
        display: flex;
        align-items: center;
        border: none;
        border-right: 1px solid #e0e7ff;
        border-radius: 8px 0 0 8px;
        transition: all 0.3s ease;
    }
    
    #taskModal .input-group:has(textarea) .input-group-text {
        align-items: flex-start;
        padding-top: 0.4rem;
        min-height: 80px;
    }
    
    #taskModal .task-input-group:focus-within .input-group-text {
        background-color: #003471 !important;
        color: white !important;
        border-right-color: #003471;
    }
    
    #taskModal .task-submit-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 52, 113, 0.25);
    }
    
    #taskModal .task-submit-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 52, 113, 0.35);
        color: white;
    }
    
    .task-add-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 6px rgba(0, 52, 113, 0.2);
    }
    
    .task-add-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 52, 113, 0.3);
        color: white;
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
    
    .delete-all-btn {
        background-color: #dc3545;
        color: white;
    }
    
    .delete-all-btn:hover {
        background-color: #c82333;
        color: white;
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(220, 53, 69, 0.3);
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

    /* Table Styling */
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
    
    .default-table-area .badge {
        font-size: 11px;
        padding: 4px 8px;
    }
    
    .default-table-area .btn-sm .material-symbols-outlined {
        font-size: 14px !important;
        color: white !important;
    }
    
    #taskModal .form-control,
    #taskModal .form-select,
    #taskModal select {
        font-size: 12px !important;
    }

    /* Status Button Styling */
    .status-btn {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 4px 10px;
        border: none;
        border-radius: 6px;
        font-size: 10px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.2s ease;
        white-space: nowrap;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
    }

    .status-btn:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 5px rgba(0, 0, 0, 0.15);
    }

    .status-btn:focus {
        outline: none;
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.2);
    }

    .status-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
        transform: none !important;
    }

    .status-text {
        font-size: 10px;
        letter-spacing: 0.3px;
    }

    .status-icon {
        font-size: 14px !important;
        line-height: 1;
        margin-left: 2px;
    }

    /* Status Colors */
    .status-pending {
        background: linear-gradient(135deg, #ffc107 0%, #ff9800 100%);
        color: #000;
    }

    .status-accepted {
        background: linear-gradient(135deg, #0d6efd 0%, #0056b3 100%);
        color: #fff;
    }

    .status-returned {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: #fff;
    }

    .status-completed {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: #fff;
    }

    /* Dropdown Menu Styling */
    .status-dropdown-menu {
        min-width: 160px;
        padding: 4px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        border: 1px solid #e9ecef;
    }

    .status-option {
        display: flex;
        align-items: center;
        gap: 8px;
        padding: 8px 12px;
        font-size: 12px;
        border-radius: 6px;
        transition: all 0.2s ease;
        margin: 2px 0;
    }

    .status-option:hover {
        background-color: #f8f9fa;
        transform: translateX(2px);
    }

    .status-indicator {
        width: 8px;
        height: 8px;
        border-radius: 50%;
        display: inline-block;
        flex-shrink: 0;
    }

    .status-pending-indicator {
        background-color: #ffc107;
    }

    .status-accepted-indicator {
        background-color: #0d6efd;
    }

    .status-returned-indicator {
        background-color: #dc3545;
    }

    .status-completed-indicator {
        background-color: #28a745;
    }
</style>

<script>
function resetForm() {
    document.getElementById('taskForm').action = '{{ route('task-management.store') }}';
    document.getElementById('taskForm').reset();
    document.getElementById('methodField').innerHTML = '';
    const modalLabel = document.getElementById('taskModalLabel');
    modalLabel.innerHTML = '<span class="material-symbols-outlined" style="font-size: 18px; color: white;">task_alt</span><span style="color: white;">Add New Task</span>';
}

function editTask(id) {
    fetch(`{{ url('/task-management') }}/${id}`, {
        headers: {
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        }
    })
        .then(response => {
            if (!response.ok) {
                throw new Error('Network response was not ok');
            }
            return response.json();
        })
        .then(data => {
            document.getElementById('taskForm').action = '{{ route('task-management.update', ':id') }}'.replace(':id', id);
            document.getElementById('methodField').innerHTML = '@method('PUT')';
            const modalLabel = document.getElementById('taskModalLabel');
            modalLabel.innerHTML = '<span class="material-symbols-outlined" style="font-size: 18px; color: white;">task_alt</span><span style="color: white;">Edit Task</span>';
            
            document.getElementById('task_title').value = data.task_title || '';
            document.getElementById('description').value = data.description || '';
            document.getElementById('type').value = data.type || '';
            document.getElementById('assign_to').value = data.assign_to || '';
            
            new bootstrap.Modal(document.getElementById('taskModal')).show();
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error loading task data');
        });
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

function updateEntriesPerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    url.searchParams.set('page', '1');
    window.location.href = url.toString();
}

function printTable() {
    const printContents = document.querySelector('.default-table-area').innerHTML;
    const originalContents = document.body.innerHTML;
    
    document.body.innerHTML = `
        <div style="padding: 20px;">
            <h3 style="text-align: center; margin-bottom: 20px; color: #003471;">Tasks List</h3>
            ${printContents}
        </div>
    `;
    
    window.print();
    document.body.innerHTML = originalContents;
    window.location.reload();
}

function updateTaskStatus(taskId, status) {
    if (!confirm(`Are you sure you want to change status to "${status}"?`)) {
        return;
    }
    
    // Disable button during request
    const button = document.querySelector(`#statusDropdown${taskId}`);
    const originalContent = button.innerHTML;
    button.disabled = true;
    button.innerHTML = '<span class="status-text">Updating...</span>';
    
    fetch(`{{ route('task-management.update-status', ':id') }}`.replace(':id', taskId), {
        method: 'PATCH',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: JSON.stringify({ status: status })
    })
    .then(response => {
        if (!response.ok) {
            return response.json().then(err => {
                throw new Error(err.message || 'Failed to update status');
            });
        }
        return response.json();
    })
    .then(data => {
        if (data.success) {
            // Update the badge color and text
            const statusClasses = {
                'Pending': 'status-pending',
                'Accepted': 'status-accepted',
                'Returned': 'status-returned',
                'Completed': 'status-completed'
            };
            
            // Remove old status classes
            button.classList.remove('status-pending', 'status-accepted', 'status-returned', 'status-completed');
            // Add new status class
            button.classList.add(statusClasses[data.status]);
            // Update text
            button.innerHTML = `<span class="status-text">${data.status}</span><span class="material-symbols-outlined status-icon">arrow_drop_down</span>`;
            button.disabled = false;
            
            // Close dropdown
            const dropdown = bootstrap.Dropdown.getInstance(button);
            if (dropdown) {
                dropdown.hide();
            }
            
            // Show success message
            alert('Task status updated successfully!');
        } else {
            button.innerHTML = originalContent;
            button.disabled = false;
            alert('Error: ' + (data.message || 'Failed to update task status'));
        }
    })
    .catch(error => {
        console.error('Error:', error);
        button.innerHTML = originalContent;
        button.disabled = false;
        alert('Error: ' + (error.message || 'Failed to update task status. Please try again.'));
    });
}

// Ensure sidebar stays open on Task Management page
document.addEventListener('DOMContentLoaded', function() {
    // Force sidebar to show state
    document.body.setAttribute("sidebar-data-theme", "sidebar-show");
    
    // Ensure sidebar is visible
    const sidebarArea = document.getElementById('sidebar-area');
    if (sidebarArea) {
        sidebarArea.style.display = '';
        sidebarArea.classList.remove('sidebar-hide');
        sidebarArea.classList.add('sidebar-show');
    }
    
    // Prevent auto-close on window resize
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992) { // Only on desktop
            document.body.setAttribute("sidebar-data-theme", "sidebar-show");
        }
    });
});
</script>
@endsection
