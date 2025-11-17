@extends('layouts.app')

@section('title', 'Test List')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Test List</h4>
                <button type="button" class="btn btn-sm py-2 px-3 d-inline-flex align-items-center gap-1 rounded-8 test-add-btn" data-bs-toggle="modal" data-bs-target="#testModal" onclick="resetForm()">
                    <span class="material-symbols-outlined" style="font-size: 16px;">add</span>
                    <span>Add New Test</span>
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
                        <a href="{{ route('test.list.export', ['format' => 'excel']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn excel-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                            <span>Excel</span>
                        </a>
                        <a href="{{ route('test.list.export', ['format' => 'pdf']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn pdf-btn" target="_blank">
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
                            <input type="text" id="searchInput" class="form-control border-start-0 border-end-0" placeholder="Search tests..." value="{{ request('search') }}" onkeypress="handleSearchKeyPress(event)" oninput="handleSearchInput(event)" style="padding: 4px 8px; font-size: 13px;">
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
                    <span>Tests List</span>
                </h5>
            </div>

            <!-- Search Results Info -->
            @if(request('search'))
                <div class="search-results-info">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; color: #003471;">search</span>
                    <strong>Search Results:</strong> Showing results for "<strong>{{ request('search') }}</strong>"
                    @if(isset($tests))
                        ({{ $tests->total() }} {{ $tests->total() == 1 ? 'result' : 'results' }} found)
                    @endif
                    <a href="{{ route('test.list') }}" class="text-decoration-none ms-2" style="color: #003471;">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">close</span>
                        Clear
                    </a>
                </div>
            @endif

            <div class="default-table-area" style="margin-top: 0;">
                <div class="table-responsive">
                    <table class="table table-sm table-hover">
                        <thead>
                            <tr>
                                <th>#</th>
                                <th>Test Name</th>
                                <th>Campus</th>
                                <th>Class</th>
                                <th>Section</th>
                                <th>Subject</th>
                                <th>Test Type</th>
                                <th>Date</th>
                                <th>Session</th>
                                <th>Result Status</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($tests) && $tests->count() > 0)
                                @forelse($tests as $test)
                                    <tr>
                                        <td>{{ $loop->iteration + (($tests->currentPage() - 1) * $tests->perPage()) }}</td>
                                        <td>
                                            <strong class="text-primary">{{ $test->test_name }}</strong>
                                        </td>
                                        <td>
                                            <span class="badge bg-info text-white">{{ $test->campus }}</span>
                                        </td>
                                        <td>{{ $test->for_class }}</td>
                                        <td>
                                            <span class="badge bg-secondary text-white">{{ $test->section }}</span>
                                        </td>
                                        <td>{{ $test->subject }}</td>
                                        <td>
                                            <span class="badge bg-warning text-dark">{{ $test->test_type }}</span>
                                        </td>
                                        <td>{{ date('d M Y', strtotime($test->date)) }}</td>
                                        <td>{{ $test->session }}</td>
                                        <td>
                                            @if($test->result_status ?? false)
                                                <button type="button" class="btn btn-sm btn-success px-3 py-1 result-status-btn" id="result-btn-{{ $test->id }}" data-test-id="{{ $test->id }}" onclick="toggleResultStatus({{ $test->id }})" style="font-size: 11px;">
                                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">check_circle</span>
                                                    <span style="color: white;">Result Declared</span>
                                                </button>
                                            @else
                                                <button type="button" class="btn btn-sm btn-warning px-3 py-1 result-status-btn" id="result-btn-{{ $test->id }}" data-test-id="{{ $test->id }}" onclick="toggleResultStatus({{ $test->id }})" style="font-size: 11px;">
                                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">pending</span>
                                                    <span style="color: white;">Declare Result</span>
                                                </button>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <button type="button" class="btn btn-sm btn-primary px-2 py-1" title="Edit" onclick="editTest({{ $test->id }}, '{{ addslashes($test->campus) }}', '{{ addslashes($test->test_name) }}', '{{ addslashes($test->for_class) }}', '{{ addslashes($test->section) }}', '{{ addslashes($test->subject) }}', '{{ addslashes($test->test_type) }}', '{{ addslashes($test->description ?? '') }}', '{{ $test->date->format('Y-m-d') }}', '{{ addslashes($test->session) }}')">
                                                    <span class="material-symbols-outlined" style="font-size: 14px; color: white;">edit</span>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger px-2 py-1" title="Delete" onclick="if(confirm('Are you sure you want to delete this test?')) { document.getElementById('delete-form-{{ $test->id }}').submit(); }">
                                                    <span class="material-symbols-outlined" style="font-size: 14px; color: white;">delete</span>
                                                </button>
                                                <form id="delete-form-{{ $test->id }}" action="{{ route('test.list.destroy', $test->id) }}" method="POST" class="d-none">
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
                                            <p class="mt-2 mb-0">No tests found.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            @else
                                <tr>
                                    <td colspan="11" class="text-center text-muted py-5">
                                        <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                        <p class="mt-2 mb-0">No tests found.</p>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            @if(isset($tests) && $tests->hasPages())
                <div class="mt-3">
                    {{ $tests->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Test Modal -->
<div class="modal fade" id="testModal" tabindex="-1" aria-labelledby="testModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header text-white p-3" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                <h5 class="modal-title fs-15 fw-semibold mb-0 d-flex align-items-center gap-2" id="testModalLabel">
                    <span class="material-symbols-outlined" style="font-size: 20px;">quiz</span>
                    <span>Add New Test</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 0.8;"></button>
            </div>
            <form id="testForm" method="POST" action="{{ route('test.list.store') }}">
                @csrf
                <div id="methodField"></div>
                <div class="modal-body p-3">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm test-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">location_on</span>
                                </span>
                                <select class="form-control test-input" name="campus" id="campus" required style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;">
                                    <option value="">Select Campus</option>
                                    @foreach($campuses as $campus)
                                        @php
                                            $campusName = is_object($campus) ? ($campus->campus_name ?? '') : $campus;
                                        @endphp
                                        <option value="{{ $campusName }}">{{ $campusName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Test Name <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm test-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">quiz</span>
                                </span>
                                <input type="text" class="form-control test-input" name="test_name" id="test_name" placeholder="Enter test name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">For Class <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm test-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">class</span>
                                </span>
                                <select class="form-control test-input" name="for_class" id="for_class" required style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;">
                                    <option value="">Select Class</option>
                                    @foreach($classes as $className)
                                        <option value="{{ $className }}">{{ $className }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Section <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm test-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">groups</span>
                                </span>
                                <select class="form-control test-input" name="section" id="section" required style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;">
                                    <option value="">Select Section</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Subject <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm test-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">menu_book</span>
                                </span>
                                <select class="form-control test-input" name="subject" id="subject" required style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;">
                                    <option value="">Select Subject</option>
                                    @foreach($subjects as $subjectName)
                                        <option value="{{ $subjectName }}">{{ $subjectName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Test Type <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm test-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">category</span>
                                </span>
                                <select class="form-control test-input" name="test_type" id="test_type" required style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;">
                                    <option value="">Select Test Type</option>
                                    @foreach($testTypes as $type)
                                        <option value="{{ $type }}">{{ $type }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Date <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm test-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">calendar_today</span>
                                </span>
                                <input type="date" class="form-control test-input" name="date" id="date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Session <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm test-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">event</span>
                                </span>
                                <select class="form-control test-input" name="session" id="session" required style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;">
                                    <option value="">Select Session</option>
                                    @foreach($sessions as $sessionName)
                                        <option value="{{ $sessionName }}">{{ $sessionName }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Description</label>
                            <div class="input-group input-group-sm test-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471; align-items: start; padding-top: 8px;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">description</span>
                                </span>
                                <textarea class="form-control test-input" name="description" id="description" rows="3" placeholder="Enter description (optional)" style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0; resize: vertical;"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-3" style="background-color: #f8f9fa; border-top: 1px solid #e9ecef;">
                    <button type="button" class="btn btn-sm py-2 px-4 rounded-8" data-bs-dismiss="modal" style="background-color: #6c757d; color: white; border: none; transition: all 0.3s ease;">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">close</span>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-sm py-2 px-4 rounded-8 test-submit-btn">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
                        Save Test
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Test Form Styling */
    #testModal .test-input-group {
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s ease;
        border: 1px solid #dee2e6;
    }
    
    #testModal .test-input-group:focus-within {
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
        border-color: #003471;
    }
    
    #testModal .test-input {
        font-size: 13px;
        padding: 0.5rem 0.75rem;
        border: none;
        border-left: 1px solid #e0e7ff;
        border-radius: 0 8px 8px 0;
        transition: all 0.3s ease;
    }
    
    #testModal .test-input:focus {
        border-left-color: #003471;
        box-shadow: none;
        outline: none;
    }
    
    #testModal .input-group-text {
        padding: 0 0.75rem;
        display: flex;
        align-items: center;
        border: none;
        border-right: 1px solid #e0e7ff;
        border-radius: 8px 0 0 8px;
        transition: all 0.3s ease;
    }
    
    #testModal .test-input-group:focus-within .input-group-text {
        background-color: #003471 !important;
        color: white !important;
        border-right-color: #003471;
    }
    
    #testModal .form-label {
        margin-bottom: 0.4rem;
        line-height: 1.3;
    }
    
    #testModal .test-submit-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 52, 113, 0.25);
    }
    
    #testModal .test-submit-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 52, 113, 0.35);
    }
    
    .test-add-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 6px rgba(0, 52, 113, 0.2);
    }
    
    .test-add-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0, 52, 113, 0.3);
        color: white;
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
        padding: 4px 8px;
        min-height: auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1;
        height: 28px;
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
    
    .result-status-btn {
        font-size: 11px;
        padding: 4px 12px;
        min-height: auto;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
        line-height: 1;
        height: 28px;
        white-space: nowrap;
    }
    
    .result-status-btn .material-symbols-outlined {
        font-size: 14px !important;
        vertical-align: middle;
        color: white !important;
        line-height: 1;
    }
    
    .result-status-btn:disabled {
        opacity: 0.6;
        cursor: not-allowed;
    }
</style>

<script>
// Reset form when opening modal for new test
function resetForm() {
    document.getElementById('testForm').reset();
    document.getElementById('testForm').action = "{{ route('test.list.store') }}";
    document.getElementById('methodField').innerHTML = '';
    document.getElementById('testModalLabel').innerHTML = `
        <span class="material-symbols-outlined" style="font-size: 20px;">quiz</span>
        <span>Add New Test</span>
    `;
    document.querySelector('.test-submit-btn').innerHTML = `
        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
        Save Test
    `;
    // Clear section dropdown
    document.getElementById('section').innerHTML = '<option value="">Select Section</option>';
}

// Edit test function
function editTest(id, campus, testName, forClass, section, subject, testType, description, date, session) {
    document.getElementById('campus').value = campus;
    document.getElementById('test_name').value = testName;
    document.getElementById('for_class').value = forClass;
    document.getElementById('subject').value = subject;
    document.getElementById('test_type').value = testType;
    document.getElementById('description').value = description;
    document.getElementById('date').value = date;
    document.getElementById('session').value = session;
    document.getElementById('testForm').action = "{{ url('test/list') }}/" + id;
    document.getElementById('methodField').innerHTML = '@method("PUT")';
    document.getElementById('testModalLabel').innerHTML = `
        <span class="material-symbols-outlined" style="font-size: 20px;">edit</span>
        <span>Edit Test</span>
    `;
    document.querySelector('.test-submit-btn').innerHTML = `
        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
        Update Test
    `;
    
    // Load sections for the selected class
    const sectionSelect = document.getElementById('section');
    if (forClass) {
        sectionSelect.innerHTML = '<option value="">Loading...</option>';
        fetch(`{{ route('test.list.get-sections') }}?class=${encodeURIComponent(forClass)}`)
            .then(response => response.json())
            .then(data => {
                sectionSelect.innerHTML = '<option value="">Select Section</option>';
                data.sections.forEach(sec => {
                    const option = document.createElement('option');
                    option.value = sec;
                    option.textContent = sec;
                    if (sec === section) {
                        option.selected = true;
                    }
                    sectionSelect.appendChild(option);
                });
            })
            .catch(error => {
                console.error('Error loading sections:', error);
                sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
            });
    } else {
        sectionSelect.innerHTML = '<option value="">Select Section</option>';
    }
    
    const modal = new bootstrap.Modal(document.getElementById('testModal'));
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
            <h3 style="text-align: center; margin-bottom: 20px; color: #003471;">Test List</h3>
            ${printContents}
        </div>
    `;
    
    window.print();
    document.body.innerHTML = originalContents;
    window.location.reload();
}

// Load sections when class changes
document.addEventListener('DOMContentLoaded', function() {
    const classSelect = document.getElementById('for_class');
    const sectionSelect = document.getElementById('section');

    if (classSelect && sectionSelect) {
        function loadSections(selectedClass) {
            if (selectedClass) {
                sectionSelect.innerHTML = '<option value="">Loading...</option>';
                
                fetch(`{{ route('test.list.get-sections') }}?class=${encodeURIComponent(selectedClass)}`)
                    .then(response => response.json())
                    .then(data => {
                        sectionSelect.innerHTML = '<option value="">Select Section</option>';
                        data.sections.forEach(section => {
                            const option = document.createElement('option');
                            option.value = section;
                            option.textContent = section;
                            sectionSelect.appendChild(option);
                        });
                    })
                    .catch(error => {
                        console.error('Error loading sections:', error);
                        sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
                    });
            } else {
                sectionSelect.innerHTML = '<option value="">Select Section</option>';
            }
        }

        classSelect.addEventListener('change', function() {
            loadSections(this.value);
        });
    }
});

// Toggle result status
function toggleResultStatus(testId) {
    const button = document.getElementById('result-btn-' + testId);
    const originalHTML = button.innerHTML;
    
    // Disable button and show loading
    button.disabled = true;
    button.innerHTML = '<span class="spinner-border spinner-border-sm" role="status"></span> <span>Processing...</span>';
    
    fetch(`{{ url('test/list') }}/${testId}/toggle-result-status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': '{{ csrf_token() }}',
            'Accept': 'application/json'
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            // Update button based on new status
            if (data.result_status) {
                button.className = 'btn btn-sm btn-success px-3 py-1 result-status-btn';
                button.innerHTML = `
                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">check_circle</span>
                    <span style="color: white;">Result Declared</span>
                `;
            } else {
                button.className = 'btn btn-sm btn-warning px-3 py-1 result-status-btn';
                button.innerHTML = `
                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white;">pending</span>
                    <span style="color: white;">Declare Result</span>
                `;
            }
            
            // Show success message
            if (typeof showToast !== 'undefined') {
                showToast(data.message, 'success');
            } else {
                alert(data.message);
            }
        } else {
            button.innerHTML = originalHTML;
            alert('Failed to update result status. Please try again.');
        }
    })
    .catch(error => {
        console.error('Error:', error);
        button.innerHTML = originalHTML;
        alert('An error occurred. Please try again.');
    })
    .finally(() => {
        button.disabled = false;
    });
}
</script>
@endsection
