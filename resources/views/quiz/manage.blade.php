@extends('layouts.app')

@section('title', 'Manage Quizzes')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Manage Quizzes</h4>
                <button type="button" class="btn btn-sm py-2 px-3 d-inline-flex align-items-center gap-1 rounded-8 quiz-add-btn" data-bs-toggle="modal" data-bs-target="#quizModal" onclick="resetForm()">
                    <span class="material-symbols-outlined" style="font-size: 16px;">add</span>
                    <span>Add New Quiz</span>
                </button>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show success-toast" role="alert" id="successAlert">
                    <div class="d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 20px;">check_circle</span>
                        <span>{{ session('success') }}</span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show error-toast" role="alert" id="errorAlert">
                    <div class="d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 20px;">error</span>
                        <span>{{ session('error') }}</span>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
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
                        <a href="{{ route('quiz.manage.export', ['format' => 'excel']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn excel-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                            <span>Excel</span>
                        </a>
                        <a href="{{ route('quiz.manage.export', ['format' => 'pdf']) }}{{ request()->has('search') ? '?search=' . request('search') : '' }}" class="btn btn-sm px-2 py-1 export-btn pdf-btn" target="_blank">
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
                            <input type="text" id="searchInput" class="form-control border-start-0 border-end-0" placeholder="Search quizzes..." value="{{ request('search') }}" onkeypress="handleSearchKeyPress(event)" oninput="handleSearchInput(event)" style="padding: 4px 8px; font-size: 13px;">
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
                    <span>Quizzes List</span>
                </h5>
            </div>

            <!-- Search Results Info -->
            @if(request('search'))
                <div class="search-results-info">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; color: #003471;">search</span>
                    <strong>Search Results:</strong> Showing results for "<strong>{{ request('search') }}</strong>"
                    @if(isset($quizzes))
                        ({{ $quizzes->total() }} {{ $quizzes->total() == 1 ? 'result' : 'results' }} found)
                    @endif
                    <a href="{{ route('quiz.manage') }}" class="text-decoration-none ms-2" style="color: #003471;">
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
                                <th>Campus</th>
                                <th>Quiz Name</th>
                                <th>Description</th>
                                <th>For Class</th>
                                <th>Section</th>
                                <th>Total Questions</th>
                                <th>Start Date & Time</th>
                                <th>Update Questions</th>
                                <th class="text-end">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @if(isset($quizzes) && $quizzes->count() > 0)
                                @forelse($quizzes as $quiz)
                                    @php
                                        $startAtTz = $quiz->startAtLocal();
                                        $quizHasStarted = $quiz->hasStarted();
                                        $startAtTimestamp = $startAtTz ? $startAtTz->timestamp : 0;
                                    @endphp
                                    <tr>
                                        <td>{{ $loop->iteration + (($quizzes->currentPage() - 1) * $quizzes->perPage()) }}</td>
                                        <td>
                                            <span class="badge bg-info text-white">{{ $quiz->campus }}</span>
                                        </td>
                                        <td>
                                            <strong class="text-primary">{{ $quiz->quiz_name }}</strong>
                                        </td>
                                        <td>{{ $quiz->description ? (strlen($quiz->description) > 50 ? substr($quiz->description, 0, 50) . '...' : $quiz->description) : 'N/A' }}</td>
                                        <td>{{ $quiz->for_class }}</td>
                                        <td>{{ $quiz->section }}</td>
                                        <td>{{ $quiz->total_questions }}</td>
                                        <td>{{ $startAtTz ? $startAtTz->format('d M Y h:i A') : '—' }}</td>
                                        <td>
                                            @if($quizHasStarted)
                                                <button type="button" class="btn btn-sm btn-success px-2 py-1 quiz-table-text-btn" title="Quiz has started — view questions and marks (no editing)" onclick="openCheckResultModal({{ $quiz->id }})">
                                                    <span class="material-symbols-outlined" style="font-size: 14px;">description</span>
                                                    Check the Result
                                                </button>
                                            @else
                                                <button type="button" class="btn btn-sm btn-warning px-2 py-1 quiz-table-text-btn" title="Update Questions" onclick="openUpdateQuestionsModal({{ $quiz->id }}, {{ $quiz->total_questions }}, {{ $startAtTimestamp }})">
                                                    <span class="material-symbols-outlined" style="font-size: 14px;">+</span>
                                                    Update Questions
                                                </button>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <div class="d-inline-flex gap-1">
                                                <button type="button" class="btn btn-sm btn-primary px-2 py-1 quiz-table-icon-btn" title="Edit" onclick="editQuiz({{ $quiz->id }}, '{{ addslashes($quiz->campus) }}', '{{ addslashes($quiz->quiz_name) }}', '{{ addslashes($quiz->description ?? '') }}', '{{ addslashes($quiz->for_class) }}', '{{ addslashes($quiz->section) }}', {{ $quiz->total_questions }}, '{{ $startAtTz ? $startAtTz->format('Y-m-d\TH:i') : '' }}', {{ $quiz->duration_minutes ?? 60 }})">
                                                    <span class="material-symbols-outlined">edit</span>
                                                </button>
                                                <button type="button" class="btn btn-sm btn-danger px-2 py-1 quiz-table-icon-btn" title="Delete" onclick="if(confirm('Are you sure you want to delete this quiz?')) { document.getElementById('delete-form-{{ $quiz->id }}').submit(); }">
                                                    <span class="material-symbols-outlined">delete</span>
                                                </button>
                                                <form id="delete-form-{{ $quiz->id }}" action="{{ route('quiz.manage.destroy', $quiz->id) }}" method="POST" class="d-none">
                                                    @csrf
                                                    @method('DELETE')
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="10" class="text-center text-muted py-5">
                                            <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                            <p class="mt-2 mb-0">No quizzes found.</p>
                                        </td>
                                    </tr>
                                @endforelse
                            @else
                                <tr>
                                    <td colspan="10" class="text-center text-muted py-5">
                                        <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                        <p class="mt-2 mb-0">No quizzes found.</p>
                                    </td>
                                </tr>
                            @endif
                        </tbody>
                    </table>
                </div>
            </div>

            @if(isset($quizzes) && $quizzes->hasPages())
                <div class="mt-3">
                    {{ $quizzes->links() }}
                </div>
            @endif
        </div>
    </div>
</div>

<!-- Quiz Modal -->
<div class="modal fade" id="quizModal" tabindex="-1" aria-labelledby="quizModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header p-3" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                <h5 class="modal-title fs-15 fw-semibold mb-0 d-flex align-items-center gap-2 text-white" id="quizModalLabel" style="color: white !important;">
                    <span class="material-symbols-outlined" style="font-size: 20px; color: white;">quiz</span>
                    <span style="color: white;">Add New Quiz</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 0.8;"></button>
            </div>
            <form id="quizForm" method="POST" action="{{ route('quiz.manage.store') }}">
                @csrf
                <div id="methodField"></div>
                <div class="modal-body p-3">
                    <div class="row g-2">
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm quiz-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">location_on</span>
                                </span>
                                <select class="form-control quiz-input" name="campus" id="campus" required style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;">
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
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Quiz Name <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm quiz-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">quiz</span>
                                </span>
                                <input type="text" class="form-control quiz-input" name="quiz_name" id="quiz_name" placeholder="Enter quiz name" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">For Class <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm quiz-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">class</span>
                                </span>
                                <select class="form-control quiz-input" name="for_class" id="for_class" required style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;" disabled>
                                    <option value="">Select Class</option>
                                    @foreach($classes as $className)
                                        <option value="{{ $className }}">{{ $className }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Section <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm quiz-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">group</span>
                                </span>
                                <select class="form-control quiz-input" name="section" id="section" required style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0;" disabled>
                                    <option value="">Select Section</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Total Questions <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm quiz-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">help_outline</span>
                                </span>
                                <input type="number" min="1" class="form-control quiz-input" name="total_questions" id="total_questions" placeholder="Enter total questions" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Start Date & Time <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm quiz-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">schedule</span>
                                </span>
                                <input type="datetime-local" class="form-control quiz-input" name="start_date_time" id="start_date_time" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Duration (Minutes) <span class="text-danger">*</span></label>
                            <div class="input-group input-group-sm quiz-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">timer</span>
                                </span>
                                <input type="number" min="1" class="form-control quiz-input" name="duration_minutes" id="duration_minutes" placeholder="Enter duration in minutes" value="60" required>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <label class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Description</label>
                            <div class="input-group input-group-sm quiz-input-group">
                                <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471; align-items: start; padding-top: 8px;">
                                    <span class="material-symbols-outlined" style="font-size: 15px;">description</span>
                                </span>
                                <textarea class="form-control quiz-input" name="description" id="description" rows="3" placeholder="Enter description (optional)" style="border: none; border-left: 1px solid #e0e7ff; border-radius: 0 8px 8px 0; resize: vertical;"></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer p-3" style="background-color: #f8f9fa; border-top: 1px solid #e9ecef;">
                    <button type="button" class="btn btn-sm py-2 px-4 rounded-8" data-bs-dismiss="modal" style="background-color: #6c757d; color: white; border: none; transition: all 0.3s ease;">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">close</span>
                        Cancel
                    </button>
                    <button type="submit" class="btn btn-sm py-2 px-4 rounded-8 quiz-submit-btn">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
                        Save Quiz
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    /* Quiz Form Styling */
    #quizModal .quiz-input-group {
        border-radius: 8px;
        overflow: hidden;
        transition: all 0.3s ease;
        border: 1px solid #dee2e6;
    }
    
    #quizModal .quiz-input-group:focus-within {
        box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
        border-color: #003471;
    }
    
    #quizModal .quiz-input {
        font-size: 13px;
        padding: 0.35rem 0.65rem;
        height: 32px;
        border: none;
        border-left: 1px solid #e0e7ff;
        border-radius: 0 8px 8px 0;
        transition: all 0.3s ease;
    }
    
    #quizModal .quiz-input[type="datetime-local"],
    #quizModal .quiz-input[type="number"],
    #quizModal .quiz-input select {
        height: 32px;
        padding: 0.35rem 0.65rem;
    }
    
    #quizModal textarea.quiz-input {
        min-height: 32px;
        padding: 0.35rem 0.65rem;
    }
    
    #quizModal select.quiz-input {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' width='12' height='12' viewBox='0 0 12 12'%3E%3Cpath fill='%23003471' d='M6 9L1 4h10z'/%3E%3C/svg%3E");
        background-repeat: no-repeat;
        background-position: right 0.65rem center;
        padding-right: 2rem;
    }
    
    #quizModal .quiz-input:focus {
        border-left-color: #003471;
        box-shadow: none;
        outline: none;
    }
    
    #quizModal .input-group-text {
        padding: 0 0.65rem;
        height: 32px;
        display: flex;
        align-items: center;
        border: none;
        border-right: 1px solid #e0e7ff;
        border-radius: 8px 0 0 8px;
        transition: all 0.3s ease;
    }
    
    #quizModal .quiz-input-group:focus-within .input-group-text {
        background-color: #003471 !important;
        color: white !important;
        border-right-color: #003471;
    }
    
    #quizModal .form-label {
        margin-bottom: 0.4rem;
        line-height: 1.3;
    }
    
    #quizModal .quiz-submit-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 52, 113, 0.25);
    }
    
    #quizModal .quiz-submit-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 52, 113, 0.35);
    }
    
    .quiz-add-btn {
        background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
        color: white;
        border: none;
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 6px rgba(0, 52, 113, 0.2);
    }
    
    .quiz-add-btn:hover {
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
    
    .default-table-area table {
        margin-bottom: 0;
        border-spacing: 0;
        border-collapse: collapse;
        border: 1px solid #dee2e6;
    }
    
    .default-table-area table thead th {
        padding: 5px 10px;
        font-size: 12px;
        font-weight: 600;
        vertical-align: middle;
        line-height: 1.3;
        height: 32px;
        white-space: nowrap;
        border: 1px solid #dee2e6;
        background-color: #f8f9fa;
    }
    
    .default-table-area table tbody td {
        padding: 5px 10px;
        font-size: 12px;
        vertical-align: middle;
        border: 1px solid #dee2e6;
        line-height: 1.3;
        height: 32px;
    }
    
    .default-table-area table tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    .default-table-area .btn-sm {
        font-size: 11px;
        padding: 4px 8px;
        min-height: 28px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        line-height: 1.2;
        height: auto;
        width: auto;
        border-radius: 6px;
        box-shadow: 0 1px 3px rgba(0, 0, 0, 0.1);
        transition: all 0.2s ease;
    }

    /* Icon-only row actions stay square; text buttons (Check the Result / Update Questions) use natural width */
    .default-table-area .btn-sm.quiz-table-icon-btn {
        width: 28px;
        min-width: 28px;
        padding-left: 0;
        padding-right: 0;
        height: 28px;
    }

    .default-table-area .btn-sm.quiz-table-text-btn {
        white-space: nowrap;
        gap: 4px;
        padding-left: 8px;
        padding-right: 10px;
        min-height: 28px;
    }

    .default-table-area .quiz-table-text-badge {
        font-size: 11px;
        font-weight: 600;
        padding: 6px 10px;
        white-space: nowrap;
        display: inline-flex;
        align-items: center;
        gap: 4px;
        border-radius: 6px;
    }
    
    .default-table-area .btn-sm:hover {
        transform: translateY(-1px);
        box-shadow: 0 2px 6px rgba(0, 0, 0, 0.15);
    }
    
    .default-table-area .btn-sm .material-symbols-outlined {
        font-size: 16px !important;
        vertical-align: middle;
        color: white !important;
        line-height: 1;
        display: inline-block;
    }
    
    .default-table-area .btn-primary .material-symbols-outlined,
    .default-table-area .btn-danger .material-symbols-outlined {
        color: white !important;
    }
    
    /* Toast Notification Styling */
    .success-toast,
    .error-toast {
        position: fixed;
        top: 20px;
        right: 20px;
        z-index: 9999;
        min-width: 300px;
        max-width: 400px;
        padding: 12px 16px;
        border-radius: 8px;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.15);
        animation: slideInDown 0.3s ease-out;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 12px;
    }
    
    .success-toast {
        background: linear-gradient(135deg, #28a745 0%, #20c997 100%);
        color: white;
        border: none;
    }
    
    .error-toast {
        background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);
        color: white;
        border: none;
    }
    
    .success-toast .btn-close,
    .error-toast .btn-close {
        filter: brightness(0) invert(1);
        opacity: 0.9;
        padding: 0;
        width: 20px;
        height: 20px;
        display: flex;
        align-items: center;
        justify-content: center;
        margin-left: auto;
    }
    
    .success-toast .btn-close:hover,
    .error-toast .btn-close:hover {
        opacity: 1;
    }
    
    .success-toast .material-symbols-outlined,
    .error-toast .material-symbols-outlined {
        font-size: 20px;
        flex-shrink: 0;
    }
    
    .success-toast > div,
    .error-toast > div {
        display: flex;
        align-items: center;
        gap: 8px;
        flex: 1;
    }
    
    @keyframes slideInDown {
        from {
            transform: translateY(-100%);
            opacity: 0;
        }
        to {
            transform: translateY(0);
            opacity: 1;
        }
    }
    
    @keyframes slideOutUp {
        from {
            transform: translateY(0);
            opacity: 1;
        }
        to {
            transform: translateY(-100%);
            opacity: 0;
        }
    }
    
    .success-toast.fade-out,
    .error-toast.fade-out {
        animation: slideOutUp 0.3s ease-out forwards;
    }
</style>

<script>
function resetForm() {
    document.getElementById('quizForm').reset();
    document.getElementById('methodField').innerHTML = '';
    document.getElementById('quizModalLabel').innerHTML = '<span class="material-symbols-outlined" style="font-size: 20px; color: white;">quiz</span><span style="color: white;">Add New Quiz</span>';
    const classSelect = document.getElementById('for_class');
    if (classSelect) {
        classSelect.innerHTML = '<option value="">Select Class</option>';
        classSelect.disabled = true;
    }
    // Reset section dropdown
    const sectionSelect = document.getElementById('section');
    if (sectionSelect) {
        sectionSelect.innerHTML = '<option value="">Select Section</option>';
        sectionSelect.disabled = true;
    }
}

function editQuiz(id, campus, quizName, description, forClass, section, totalQuestions, startDateTime, durationMinutes) {
    resetForm();
    document.getElementById('methodField').innerHTML = '<input type="hidden" name="_method" value="PUT">';
    document.getElementById('quizForm').action = '{{ route("quiz.manage.update", ":id") }}'.replace(':id', id);
    document.getElementById('campus').value = campus;
    document.getElementById('quiz_name').value = quizName;
    document.getElementById('description').value = description;
    document.getElementById('total_questions').value = totalQuestions;
    document.getElementById('start_date_time').value = startDateTime;
    document.getElementById('duration_minutes').value = durationMinutes || 60;
    document.getElementById('quizModalLabel').innerHTML = '<span class="material-symbols-outlined" style="font-size: 20px; color: white;">edit</span><span style="color: white;">Edit Quiz</span>';
    loadClassesForCampus(campus, forClass, section);
    
    new bootstrap.Modal(document.getElementById('quizModal')).show();
}

function updateEntriesPerPage(value) {
    const url = new URL(window.location.href);
    url.searchParams.set('per_page', value);
    window.location.href = url.toString();
}

function handleSearchKeyPress(event) {
    if (event.key === 'Enter') {
        performSearch();
    }
}

function handleSearchInput(event) {
    if (event.target.value === '') {
        clearSearch();
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

function printTable() {
    const searchValue = document.getElementById('searchInput')?.value?.trim() || '';
    let url = '{{ route("quiz.manage.print") }}?auto_print=1';
    if (searchValue) {
        url += '&search=' + encodeURIComponent(searchValue);
    }

    const printWindow = window.open(url, '_blank');
    if (!printWindow || printWindow.closed || typeof printWindow.closed === 'undefined') {
        window.location.href = url;
    }
}

// Dynamic section loading based on class
document.addEventListener('DOMContentLoaded', function() {
    const campusSelect = document.getElementById('campus');
    const classSelect = document.getElementById('for_class');
    const sectionSelect = document.getElementById('section');
    
    if (campusSelect && classSelect) {
        campusSelect.addEventListener('change', function() {
            const selectedCampus = this.value;
            loadClassesForCampus(selectedCampus);
        });
    }

    if (classSelect && sectionSelect) {
        classSelect.addEventListener('change', function() {
            const selectedClass = this.value;
            if (selectedClass) {
                loadSections(selectedClass, null, campusSelect ? campusSelect.value : '');
            } else {
                sectionSelect.innerHTML = '<option value="">Select Section</option>';
                sectionSelect.disabled = true;
            }
        });
    }
});

function loadClassesForCampus(selectedCampus, selectedClass = null, selectedSection = null) {
    const classSelect = document.getElementById('for_class');
    const sectionSelect = document.getElementById('section');
    if (!classSelect) return;

    if (!selectedCampus) {
        classSelect.innerHTML = '<option value="">Select Class</option>';
        classSelect.disabled = true;
        if (sectionSelect) {
            sectionSelect.innerHTML = '<option value="">Select Section</option>';
            sectionSelect.disabled = true;
        }
        return;
    }

    classSelect.innerHTML = '<option value="">Loading...</option>';
    classSelect.disabled = true;

    fetch(`{{ route('quiz.manage.get-classes-by-campus') }}?campus=${encodeURIComponent(selectedCampus)}`)
        .then(response => response.json())
        .then(data => {
            classSelect.innerHTML = '<option value="">Select Class</option>';
            if (data.classes && data.classes.length > 0) {
                data.classes.forEach(className => {
                    const option = document.createElement('option');
                    option.value = className;
                    option.textContent = className;
                    classSelect.appendChild(option);
                });
            }
            classSelect.disabled = false;

            if (selectedClass) {
                classSelect.value = selectedClass;
                loadSections(selectedClass, selectedSection, selectedCampus);
            } else if (sectionSelect) {
                sectionSelect.innerHTML = '<option value="">Select Section</option>';
                sectionSelect.disabled = true;
            }
        })
        .catch(error => {
            console.error('Error loading classes:', error);
            classSelect.innerHTML = '<option value="">Error loading classes</option>';
            classSelect.disabled = false;
        });
}

function loadSections(selectedClass, selectedSection = null, selectedCampus = null) {
    const sectionSelect = document.getElementById('section');
    if (!sectionSelect) return;
    
    sectionSelect.innerHTML = '<option value="">Loading...</option>';
    sectionSelect.disabled = true;
    
    const params = new URLSearchParams();
    params.append('class', selectedClass);
    if (selectedCampus) {
        params.append('campus', selectedCampus);
    }

    fetch(`{{ route('quiz.manage.get-sections-by-class') }}?${params.toString()}`)
        .then(response => response.json())
        .then(data => {
            sectionSelect.innerHTML = '<option value="">Select Section</option>';
            if (data.sections && data.sections.length > 0) {
                data.sections.forEach(section => {
                    const option = document.createElement('option');
                    option.value = section;
                    option.textContent = section;
                    if (selectedSection && section === selectedSection) {
                        option.selected = true;
                    }
                    sectionSelect.appendChild(option);
                });
            }
            sectionSelect.disabled = false;
        })
        .catch(error => {
            console.error('Error loading sections:', error);
            sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
            sectionSelect.disabled = false;
        });
}

// Auto-dismiss toast notifications
document.addEventListener('DOMContentLoaded', function() {
    const successAlert = document.getElementById('successAlert');
    const errorAlert = document.getElementById('errorAlert');
    
    function dismissToast(toast) {
        if (toast) {
            toast.classList.add('fade-out');
            setTimeout(() => {
                toast.remove();
            }, 300);
        }
    }
    
    if (successAlert) {
        setTimeout(() => {
            dismissToast(successAlert);
        }, 5000);
    }
    
    if (errorAlert) {
        setTimeout(() => {
            dismissToast(errorAlert);
        }, 5000);
    }
});

function openUpdateQuestionsModal(quizId, totalQuestions) {
    document.getElementById('updateQuestionsModalLabel').innerHTML = '<span class="material-symbols-outlined" style="font-size: 20px; color: white;">+</span><span style="color: white;">Update Quiz Questions</span>';
    document.getElementById('updateQuestionsForm').action = '{{ route("quiz.questions.update", ":id") }}'.replace(':id', quizId);
    document.getElementById('quizIdInput').value = quizId;

    applyQuestionsEditability(true);

    const questionsContainer = document.getElementById('questionsContainer');
    questionsContainer.innerHTML = '';

    for (let i = 1; i <= totalQuestions; i++) {
        addQuestionField(i);
    }

    loadQuizQuestions(quizId)
        .then((data) => {
            const canEdit = data?.can_edit !== false;
            applyQuestionsEditability(canEdit);
            const modalEl = document.getElementById('updateQuestionsModal');
            const modal = bootstrap.Modal.getOrCreateInstance(modalEl);
            modal.show();
        })
        .catch((error) => {
            console.error('Error loading questions:', error);
            applyQuestionsEditability(true);
            bootstrap.Modal.getOrCreateInstance(document.getElementById('updateQuestionsModal')).show();
        });
}

function applyQuestionsEditability(canEdit) {
    const saveBtn = document.getElementById('updateQuestionsSaveBtn');
    const notice = document.getElementById('updateQuestionsLockedNotice');
    const form = document.getElementById('updateQuestionsForm');
    document.querySelectorAll('#questionsContainer input').forEach((el) => {
        el.readOnly = !canEdit;
        el.disabled = !canEdit;
        el.classList.toggle('bg-light', !canEdit);
    });
    document.querySelectorAll('#questionsContainer button.btn-danger').forEach((btn) => {
        btn.disabled = !canEdit;
    });
    if (saveBtn) {
        saveBtn.disabled = !canEdit;
        saveBtn.classList.toggle('d-none', !canEdit);
    }
    if (notice) {
        notice.classList.toggle('d-none', canEdit);
    }
    if (form) {
        form.dataset.locked = canEdit ? '0' : '1';
    }
}

function addQuestionField(questionNumber) {
    const questionsContainer = document.getElementById('questionsContainer');
    const questionDiv = document.createElement('div');
    questionDiv.className = 'question-group mb-3 p-3 border rounded';
    questionDiv.id = `question-group-${questionNumber}`;
    
    questionDiv.innerHTML = `
        <div class="d-flex justify-content-between align-items-center mb-2">
            <h6 class="mb-0 fw-bold">Question ${questionNumber}</h6>
            <button type="button" class="btn btn-sm btn-danger" onclick="removeQuestion(${questionNumber})" style="display: none;">
                <span class="material-symbols-outlined" style="font-size: 14px;">delete</span>
            </button>
        </div>
        <div class="mb-2">
            <label class="form-label mb-1 fs-12 fw-semibold">Question Text</label>
            <input type="text" class="form-control form-control-sm" name="questions[${questionNumber}][question]" id="question_${questionNumber}" placeholder="Enter question text">
        </div>
        <div class="row g-2">
            <div class="col-md-8">
                <label class="form-label mb-1 fs-12 fw-semibold">Answer 1</label>
                <input type="text" class="form-control form-control-sm" name="questions[${questionNumber}][answer1]" id="answer1_${questionNumber}" placeholder="Enter answer 1">
            </div>
            <div class="col-md-4">
                <label class="form-label mb-1 fs-12 fw-semibold">Marks</label>
                <input type="number" class="form-control form-control-sm" name="questions[${questionNumber}][marks1]" id="marks1_${questionNumber}" placeholder="0" value="0" min="0">
            </div>
            <div class="col-md-8">
                <label class="form-label mb-1 fs-12 fw-semibold">Answer 2</label>
                <input type="text" class="form-control form-control-sm" name="questions[${questionNumber}][answer2]" id="answer2_${questionNumber}" placeholder="Enter answer 2">
            </div>
            <div class="col-md-4">
                <label class="form-label mb-1 fs-12 fw-semibold">Marks</label>
                <input type="number" class="form-control form-control-sm" name="questions[${questionNumber}][marks2]" id="marks2_${questionNumber}" placeholder="0" value="0" min="0">
            </div>
            <div class="col-md-8">
                <label class="form-label mb-1 fs-12 fw-semibold">Answer 3</label>
                <input type="text" class="form-control form-control-sm" name="questions[${questionNumber}][answer3]" id="answer3_${questionNumber}" placeholder="Enter answer 3">
            </div>
            <div class="col-md-4">
                <label class="form-label mb-1 fs-12 fw-semibold">Marks</label>
                <input type="number" class="form-control form-control-sm" name="questions[${questionNumber}][marks3]" id="marks3_${questionNumber}" placeholder="0" value="0" min="0">
            </div>
        </div>
        <hr class="my-2">
    `;
    
    questionsContainer.appendChild(questionDiv);
}

function removeQuestion(questionNumber) {
    const questionGroup = document.getElementById(`question-group-${questionNumber}`);
    if (questionGroup) {
        questionGroup.remove();
    }
}

document.getElementById('updateQuestionsForm')?.addEventListener('submit', function (e) {
    if (this.dataset.locked === '1') {
        e.preventDefault();
        alert('This quiz has already started. Questions can no longer be updated.');
    }
});

function loadQuizQuestions(quizId) {
    return fetch(`{{ route('quiz.questions.get', ':id') }}`.replace(':id', quizId))
        .then(response => response.json())
        .then(data => {
            if (data.questions && data.questions.length > 0) {
                data.questions.forEach((q, index) => {
                    const questionNum = index + 1;
                    const questionInput = document.getElementById(`question_${questionNum}`);
                    const answer1Input = document.getElementById(`answer1_${questionNum}`);
                    const answer2Input = document.getElementById(`answer2_${questionNum}`);
                    const answer3Input = document.getElementById(`answer3_${questionNum}`);
                    const marks1Input = document.getElementById(`marks1_${questionNum}`);
                    const marks2Input = document.getElementById(`marks2_${questionNum}`);
                    const marks3Input = document.getElementById(`marks3_${questionNum}`);

                    if (questionInput) questionInput.value = q.question || '';
                    if (answer1Input) answer1Input.value = q.answer1 || '';
                    if (answer2Input) answer2Input.value = q.answer2 || '';
                    if (answer3Input) answer3Input.value = q.answer3 || '';
                    if (marks1Input) marks1Input.value = q.marks1 || 0;
                    if (marks2Input) marks2Input.value = q.marks2 || 0;
                    if (marks3Input) marks3Input.value = q.marks3 || 0;
                });
            }
            return data;
        })
        .catch(error => {
            console.error('Error loading questions:', error);
            throw error;
        });
}

function openCheckResultModal(quizId) {
    fetch(`{{ route('quiz.result.get', ':id') }}`.replace(':id', quizId))
        .then(response => response.json())
        .then(data => {
            const modalBody = document.getElementById('checkResultModalBody');
            modalBody.innerHTML = '';

            const escapeHtml = (value) => {
                const div = document.createElement('div');
                div.textContent = value === null || value === undefined || value === '' ? 'N/A' : value;
                return div.innerHTML;
            };

            const formatMarks = (value) => {
                const number = Number(value || 0);
                return Number.isInteger(number) ? number : number.toFixed(2);
            };

            const quiz = data.quiz || {};
            const students = Array.isArray(data.students) ? data.students : [];
            const submittedCount = Number(quiz.submitted_count || students.filter(student => student.submitted).length);
            const totalStudents = Number(quiz.total_students || students.length);
            const pendingCount = Number(quiz.pending_count || Math.max(0, totalStudents - submittedCount));

            const summaryDiv = document.createElement('div');
            summaryDiv.className = 'result-summary mb-3 p-3 bg-light rounded';
            summaryDiv.innerHTML = `
                <h5 class="fw-bold mb-3">Quiz Result Summary</h5>
                <div class="row g-2">
                    <div class="col-md-3">
                        <div class="p-2 rounded bg-white border">
                            <div class="text-muted fs-12">Quiz</div>
                            <div class="fw-semibold">${escapeHtml(quiz.quiz_name || 'N/A')}</div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="p-2 rounded bg-white border">
                            <div class="text-muted fs-12">Class / Section</div>
                            <div class="fw-semibold">${escapeHtml(`${quiz.for_class || ''} ${quiz.section ? '- ' + quiz.section : ''}`.trim())}</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="p-2 rounded bg-white border">
                            <div class="text-muted fs-12">Total Students</div>
                            <div class="fw-semibold">${totalStudents}</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="p-2 rounded bg-white border">
                            <div class="text-muted fs-12">Submitted</div>
                            <div class="fw-semibold text-success">${submittedCount}</div>
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="p-2 rounded bg-white border">
                            <div class="text-muted fs-12">Pending</div>
                            <div class="fw-semibold text-danger">${pendingCount}</div>
                        </div>
                    </div>
                </div>
            `;
            modalBody.appendChild(summaryDiv);

            const studentsDiv = document.createElement('div');
            studentsDiv.className = 'mb-3';
            if (students.length > 0) {
                const rows = students.map((student, index) => {
                    const statusBadge = student.submitted
                        ? '<span class="badge bg-success">Submitted</span>'
                        : '<span class="badge bg-warning text-dark">Pending</span>';

                    return `
                        <tr>
                            <td>${index + 1}</td>
                            <td>${escapeHtml(student.student_code)}</td>
                            <td>${escapeHtml(student.student_name)}</td>
                            <td>${escapeHtml(student.father_name)}</td>
                            <td>${escapeHtml(`${student.class || ''} ${student.section ? '- ' + student.section : ''}`.trim())}</td>
                            <td class="text-center fw-semibold">${formatMarks(student.obtained_marks)} / ${formatMarks(student.total_marks)}</td>
                            <td class="text-center">${statusBadge}</td>
                            <td>${student.submitted_at ? escapeHtml(student.submitted_at) : 'N/A'}</td>
                        </tr>
                    `;
                }).join('');

                studentsDiv.innerHTML = `
                    <h6 class="fw-bold mb-2">Students Marks</h6>
                    <div class="table-responsive">
                        <table class="table table-bordered table-sm align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>#</th>
                                    <th>Student Code</th>
                                    <th>Student</th>
                                    <th>Parent</th>
                                    <th>Class/Sec</th>
                                    <th class="text-center">Marks</th>
                                    <th class="text-center">Status</th>
                                    <th>Submitted At</th>
                                </tr>
                            </thead>
                            <tbody>${rows}</tbody>
                        </table>
                    </div>
                `;
            } else {
                studentsDiv.innerHTML = '<p class="text-muted mb-3">No students found for this quiz class/section.</p>';
            }
            modalBody.appendChild(studentsDiv);
            
            if (data.questions && data.questions.length > 0) {
                let maxMarks = 0;
                
                data.questions.forEach((q, index) => {
                    const questionNum = index + 1;
                    const questionMarks = (q.marks1 || 0) + (q.marks2 || 0) + (q.marks3 || 0);
                    maxMarks += questionMarks;
                    
                    const questionDiv = document.createElement('div');
                    questionDiv.className = 'question-result mb-3 p-3 border rounded';
                    questionDiv.innerHTML = `
                        <h6 class="fw-bold mb-2">Question ${questionNum}</h6>
                        <p class="mb-2"><strong>Q:</strong> ${escapeHtml(q.question)}</p>
                        <div class="answers-section">
                            <div class="row g-2 mb-2">
                                <div class="col-md-8">
                                    <strong>Answer 1:</strong> ${escapeHtml(q.answer1)}
                                </div>
                                <div class="col-md-4">
                                    <strong>Marks:</strong> ${q.marks1 || 0}
                                </div>
                            </div>
                            <div class="row g-2 mb-2">
                                <div class="col-md-8">
                                    <strong>Answer 2:</strong> ${escapeHtml(q.answer2)}
                                </div>
                                <div class="col-md-4">
                                    <strong>Marks:</strong> ${q.marks2 || 0}
                                </div>
                            </div>
                            <div class="row g-2">
                                <div class="col-md-8">
                                    <strong>Answer 3:</strong> ${escapeHtml(q.answer3)}
                                </div>
                                <div class="col-md-4">
                                    <strong>Marks:</strong> ${q.marks3 || 0}
                                </div>
                            </div>
                            <div class="mt-2 pt-2 border-top">
                                <strong>Total Marks for Question ${questionNum}:</strong> ${questionMarks}
                            </div>
                        </div>
                    `;
                    modalBody.appendChild(questionDiv);
                });
                
                const questionsSummaryDiv = document.createElement('div');
                questionsSummaryDiv.className = 'result-summary mt-3 p-3 bg-light rounded';
                questionsSummaryDiv.innerHTML = `
                    <h5 class="fw-bold mb-2">Questions Summary</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <strong>Total Questions:</strong> ${data.questions.length}
                        </div>
                        <div class="col-md-6">
                            <strong>Maximum Marks:</strong> ${maxMarks}
                        </div>
                    </div>
                `;
                modalBody.appendChild(questionsSummaryDiv);
            } else {
                const noQuestionsDiv = document.createElement('p');
                noQuestionsDiv.className = 'text-muted';
                noQuestionsDiv.textContent = 'No questions found for this quiz.';
                modalBody.appendChild(noQuestionsDiv);
            }
            
            new bootstrap.Modal(document.getElementById('checkResultModal')).show();
        })
        .catch(error => {
            console.error('Error loading result:', error);
            alert('Error loading quiz result. Please try again.');
        });
}
</script>

<!-- Update Questions Modal -->
<div class="modal fade" id="updateQuestionsModal" tabindex="-1" aria-labelledby="updateQuestionsModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header p-3" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                <h5 class="modal-title fs-15 fw-semibold mb-0 d-flex align-items-center gap-2 text-white" id="updateQuestionsModalLabel" style="color: white !important;">
                    <span class="material-symbols-outlined" style="font-size: 20px; color: white;">+</span>
                    <span style="color: white;">Update Quiz Questions</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 0.8;"></button>
            </div>
            <form id="updateQuestionsForm" method="POST" action="">
                @csrf
                @method('PUT')
                <input type="hidden" id="quizIdInput" name="quiz_id" value="">
                <div class="modal-body p-3" style="max-height: 70vh; overflow-y: auto;">
                    <div id="updateQuestionsLockedNotice" class="alert alert-warning d-none mb-3" role="alert">
                        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">lock</span>
                        This quiz has reached its Start Date &amp; Time. Questions are locked and cannot be edited or saved.
                    </div>
                    <div id="questionsContainer">
                        <!-- Questions will be dynamically added here -->
                    </div>
                </div>
                <div class="modal-footer p-3" style="background-color: #f8f9fa; border-top: 1px solid #e9ecef;">
                    <button type="button" class="btn btn-sm py-2 px-4 rounded-8" data-bs-dismiss="modal" style="background-color: #6c757d; color: white; border: none; transition: all 0.3s ease;">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">close</span>
                        Close
                    </button>
                    <button type="submit" id="updateQuestionsSaveBtn" class="btn btn-sm py-2 px-4 rounded-8 quiz-submit-btn">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">save</span>
                        Save Questions
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
    .question-group {
        background-color: #f8f9fa;
        border-color: #dee2e6 !important;
    }
    
    .question-group:hover {
        background-color: #e9ecef;
    }
    
    #updateQuestionsModal .form-control-sm {
        font-size: 13px;
        padding: 0.35rem 0.65rem;
        height: 32px;
    }
    
    #updateQuestionsModal .form-label {
        font-size: 12px;
        color: #003471;
        font-weight: 600;
    }
</style>

<!-- Check Result Modal -->
<div class="modal fade" id="checkResultModal" tabindex="-1" aria-labelledby="checkResultModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-xl">
        <div class="modal-content border-0 shadow-lg" style="border-radius: 12px; overflow: hidden;">
            <div class="modal-header p-3" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border: none;">
                <h5 class="modal-title fs-15 fw-semibold mb-0 d-flex align-items-center gap-2 text-white" id="checkResultModalLabel" style="color: white !important;">
                    <span class="material-symbols-outlined" style="font-size: 20px; color: white;">description</span>
                    <span style="color: white;">Check the Result</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" style="opacity: 0.8;"></button>
            </div>
            <div class="modal-body p-3" style="max-height: 70vh; overflow-y: auto;" id="checkResultModalBody">
                <!-- Result content will be loaded here -->
            </div>
            <div class="modal-footer p-3" style="background-color: #f8f9fa; border-top: 1px solid #e9ecef;">
                <button type="button" class="btn btn-sm py-2 px-4 rounded-8" data-bs-dismiss="modal" style="background-color: #6c757d; color: white; border: none; transition: all 0.3s ease;">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">close</span>
                    Close
                </button>
            </div>
        </div>
    </div>
</div>
@endsection
