@extends('layouts.app')

@section('title', 'Add & Manage Diaries')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Add & Manage Diaries</h4>
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

            <!-- Filter Form -->
            <form action="{{ route('homework-diary.manage') }}" method="GET" id="filterForm">
                <div class="row g-2 mb-3 align-items-end">
                    <!-- Campus -->
                    <div class="col-md-3">
                        <label for="filter_campus" class="form-label mb-0 fs-13 fw-medium">Campus</label>
                        <div class="position-relative">
                            <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px; padding-right: {{ request('filter_campus') ? '30px' : '12px' }};">
                                <option value="">All Campuses</option>
                                @foreach($campuses as $campus)
                                    @php
                                        $campusName = is_object($campus) ? ($campus->campus_name ?? '') : $campus;
                                    @endphp
                                    <option value="{{ $campusName }}" {{ request('filter_campus') == $campusName ? 'selected' : '' }}>{{ $campusName }}</option>
                                @endforeach
                            </select>
                            @if(request('filter_campus'))
                                <button type="button" class="btn btn-sm position-absolute" onclick="clearFilter('filter_campus')" style="right: 25px; top: 50%; transform: translateY(-50%); padding: 0; width: 20px; height: 20px; background: transparent; border: none; color: #dc3545; z-index: 10;" title="Clear Campus">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">close</span>
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Class -->
                    <div class="col-md-3">
                        <label for="filter_class" class="form-label mb-0 fs-13 fw-medium">Class</label>
                        <div class="position-relative">
                            <select class="form-select form-select-sm" id="filter_class" name="filter_class" style="height: 32px; padding-right: {{ request('filter_class') ? '30px' : '12px' }};">
                                <option value="">All Classes</option>
                                @foreach($classes as $className)
                                    <option value="{{ $className }}" {{ request('filter_class') == $className ? 'selected' : '' }}>{{ $className }}</option>
                                @endforeach
                            </select>
                            @if(request('filter_class'))
                                <button type="button" class="btn btn-sm position-absolute" onclick="clearFilter('filter_class')" style="right: 25px; top: 50%; transform: translateY(-50%); padding: 0; width: 20px; height: 20px; background: transparent; border: none; color: #dc3545; z-index: 10;" title="Clear Class">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">close</span>
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Section -->
                    <div class="col-md-3">
                        <label for="filter_section" class="form-label mb-0 fs-13 fw-medium">Section</label>
                        <div class="position-relative">
                            <select class="form-select form-select-sm" id="filter_section" name="filter_section" style="height: 32px; padding-right: {{ request('filter_section') ? '30px' : '12px' }};">
                                <option value="">All Sections</option>
                                @foreach($sections as $sectionName)
                                    <option value="{{ $sectionName }}" {{ request('filter_section') == $sectionName ? 'selected' : '' }}>{{ $sectionName }}</option>
                                @endforeach
                            </select>
                            @if(request('filter_section'))
                                <button type="button" class="btn btn-sm position-absolute" onclick="clearFilter('filter_section')" style="right: 25px; top: 50%; transform: translateY(-50%); padding: 0; width: 20px; height: 20px; background: transparent; border: none; color: #dc3545; z-index: 10;" title="Clear Section">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">close</span>
                                </button>
                            @endif
                        </div>
                    </div>

                    <!-- Date -->
                    <div class="col-md-3">
                        <label for="filter_date" class="form-label mb-0 fs-13 fw-medium">Date</label>
                        <div class="position-relative">
                            <input type="date" class="form-control form-control-sm" id="filter_date" name="filter_date" value="{{ $filterDate }}" style="height: 32px; padding-right: {{ request('filter_date') ? '30px' : '12px' }};">
                            @if(request('filter_date'))
                                <button type="button" class="btn btn-sm position-absolute" onclick="clearFilter('filter_date')" style="right: 5px; top: 50%; transform: translateY(-50%); padding: 0; width: 20px; height: 20px; background: transparent; border: none; color: #dc3545; z-index: 10;" title="Clear Date">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">close</span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Filter Button -->
                <div class="row">
                    <div class="col-md-12 text-end">
                        <button type="submit" class="btn btn-sm py-1 px-3 rounded-8 filter-btn" style="height: 32px;">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">filter_alt</span>
                            <span style="font-size: 12px;">Filter</span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Homework Diary Form - Only show when all filters are applied -->
            @if(request('filter_campus') && request('filter_class') && request('filter_section'))
            <div class="mt-3">
                <!-- Context Card -->
                <div class="card mb-3" style="background-color: #f8f9fa; border: 1px solid #e9ecef;">
                    <div class="card-body p-3">
                        <div class="row align-items-center">
                            <div class="col-md-8">
                                <div class="d-flex flex-column gap-1">
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-semibold" style="color: #003471;">Section:</span>
                                        <span style="color: #495057;">{{ $filterSection }}</span>
                                    </div>
                                    <div class="d-flex align-items-center gap-2">
                                        <span class="fw-semibold" style="color: #003471;">Date:</span>
                                        <span style="color: #495057;">{{ \Carbon\Carbon::parse($filterDate)->format('d F - Y') }}</span>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 text-end">
                                <span class="material-symbols-outlined" style="font-size: 48px; color: #dee2e6; opacity: 0.5;">bar_chart</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- Navigation Guide Banner -->
                <div class="alert alert-warning mb-3 d-flex align-items-center gap-2" style="background-color: #fff3cd; border-color: #ffc107; color: #856404;">
                    <span class="material-symbols-outlined" style="font-size: 20px;">info</span>
                    <span style="font-size: 13px;"><strong>Navigation Guide:</strong> Use ← (Left Arrow), → (Right Arrow), ↑ (Up Arrow), and ↓ (Down Arrow) to navigate between input fields.</span>
                </div>
                
                @if($subjects->count() > 0)
                <form action="{{ route('homework-diary.store') }}" method="POST" id="diaryForm">
                    @csrf
                    <input type="hidden" name="campus" value="{{ $filterCampus }}">
                    <input type="hidden" name="class" value="{{ $filterClass }}">
                    <input type="hidden" name="section" value="{{ $filterSection }}">
                    <input type="hidden" name="date" value="{{ $filterDate }}">
                    
                    <div class="row g-3">
                        @foreach($subjects as $subject)
                            @php
                                $existingEntry = $diaryEntries->get($subject->id);
                            @endphp
                            <div class="col-12">
                                <label for="diary_{{ $subject->id }}" class="form-label mb-1 fs-14 fw-semibold" style="color: #003471;">
                                    {{ $subject->subject_name }}
                                </label>
                                <textarea 
                                    class="form-control diary-textarea" 
                                    id="diary_{{ $subject->id }}" 
                                    name="diaries[{{ $loop->index }}][homework_content]" 
                                    rows="4" 
                                    placeholder="Enter homework for {{ $subject->subject_name }}..."
                                    style="font-size: 14px; padding: 12px; border: 1px solid #dee2e6; border-radius: 8px; resize: vertical; min-height: 100px;"
                                >{{ $existingEntry ? $existingEntry->homework_content : '' }}</textarea>
                                <input type="hidden" name="diaries[{{ $loop->index }}][subject_id]" value="{{ $subject->id }}">
                            </div>
                        @endforeach
                    </div>
                    
                    <div class="text-center mt-4">
                        <button type="submit" class="btn btn-success px-5 py-2" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); border: none; font-weight: 500; font-size: 14px;">
                            <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle; color: white;">thumb_up</span>
                            <span style="color: white;">Save Changes</span>
                        </button>
                    </div>
                </form>
                @else
                <div class="text-center py-5">
                    <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">menu_book</span>
                    <p class="mt-2 mb-0 text-muted">No subjects found.</p>
                    <p class="mt-1 mb-0 text-muted" style="font-size: 13px;">Please add subjects for this Campus, Class, and Section combination.</p>
                    <div class="mt-3">
                        <a href="{{ route('manage-subjects') }}" class="btn btn-sm btn-primary px-4 py-2" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                            <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; color: white;">add</span>
                            <span style="color: white; font-size: 13px;">Add Subjects (Super Admin)</span>
                        </a>
                    </div>
                </div>
                @endif
            </div>
            @else
            <!-- Message when filters are not fully applied -->
            <div class="text-center py-5 mt-3">
                <span class="material-symbols-outlined" style="font-size: 64px; color: #dee2e6; opacity: 0.5;">filter_list</span>
                <h5 class="mt-3 text-muted">Apply Filters to View Subjects</h5>
                <p class="text-muted mb-0">Please select Campus, Class, Section, and Date, then click Filter to view subjects list.</p>
            </div>
            @endif
        </div>
    </div>
</div>

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

/* Table Styling */
.default-table-area .table {
    width: 100%;
    border-collapse: collapse;
    margin-bottom: 0;
}

.default-table-area .table th,
.default-table-area .table td {
    padding: 8px 12px;
    vertical-align: middle;
    border-top: 1px solid #e9ecef;
    font-size: 13px;
}

.default-table-area .table thead th {
    border-bottom: 2px solid #e9ecef;
    font-weight: 600;
    color: #495057;
    background-color: #f8f9fa;
}

.default-table-area .table tbody tr:nth-of-type(odd) {
    background-color: #ffffff;
}

.default-table-area .table tbody tr:nth-of-type(even) {
    background-color: #fdfdfd;
}

.table-hover tbody tr:hover {
    background-color: #f8f9fa;
}

.diary-textarea {
    transition: all 0.3s ease;
}

.diary-textarea:focus {
    border-color: #003471 !important;
    box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15) !important;
    outline: none;
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
function clearFilter(filterName) {
    const url = new URL(window.location.href);
    url.searchParams.delete(filterName);
    url.searchParams.delete('page');
    
    // If clearing class, also clear section
    if (filterName === 'filter_class') {
        url.searchParams.delete('filter_section');
        document.getElementById('filter_section').innerHTML = '<option value="">All Sections</option>';
    }
    
    // If clearing date, set it to today
    if (filterName === 'filter_date') {
        const today = new Date().toISOString().split('T')[0];
        url.searchParams.set('filter_date', today);
    }
    
    window.location.href = url.toString();
}

document.addEventListener('DOMContentLoaded', function() {
    const classSelect = document.getElementById('filter_class');
    const sectionSelect = document.getElementById('filter_section');

    function loadSections(selectedClass) {
        if (selectedClass) {
            sectionSelect.innerHTML = '<option value="">Loading...</option>';
            
            fetch(`{{ route('homework-diary.get-sections') }}?class=${encodeURIComponent(selectedClass)}`)
                .then(response => response.json())
                .then(data => {
                    sectionSelect.innerHTML = '<option value="">All Sections</option>';
                    data.sections.forEach(section => {
                        const option = document.createElement('option');
                        option.value = section;
                        option.textContent = section;
                        sectionSelect.appendChild(option);
                    });
                    // Preserve selected section if it exists in new options
                    const currentSection = "{{ request('filter_section') }}";
                    if (currentSection && data.sections.includes(currentSection)) {
                        sectionSelect.value = currentSection;
                    }
                })
                .catch(error => {
                    console.error('Error loading sections:', error);
                    sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
                });
        } else {
            sectionSelect.innerHTML = '<option value="">All Sections</option>';
        }
    }

    classSelect.addEventListener('change', function() {
        loadSections(this.value);
    });

    // Initial load of sections if a class is already selected
    const initialClass = classSelect.value;
    if (initialClass) {
        loadSections(initialClass);
    }
});

// Keyboard navigation for textareas
document.addEventListener('DOMContentLoaded', function() {
    const textareas = document.querySelectorAll('.diary-textarea');
    
    textareas.forEach((textarea, index) => {
        textarea.addEventListener('keydown', function(e) {
            if (e.key === 'ArrowDown' && index < textareas.length - 1) {
                e.preventDefault();
                textareas[index + 1].focus();
            } else if (e.key === 'ArrowUp' && index > 0) {
                e.preventDefault();
                textareas[index - 1].focus();
            } else if (e.key === 'ArrowRight' && e.ctrlKey) {
                e.preventDefault();
                if (index < textareas.length - 1) {
                    textareas[index + 1].focus();
                }
            } else if (e.key === 'ArrowLeft' && e.ctrlKey) {
                e.preventDefault();
                if (index > 0) {
                    textareas[index - 1].focus();
                }
            }
        });
    });
});

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
</script>
@endsection
