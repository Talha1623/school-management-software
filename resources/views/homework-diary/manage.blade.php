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

            <!-- Subjects Table - Only show when all filters are applied -->
            @if(request('filter_campus') && request('filter_class') && request('filter_section'))
            <div class="mt-3">
                <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 18px;">menu_book</span>
                        <span>Subjects List</span>
                        <span class="badge bg-light text-dark ms-2">
                            {{ $subjects->count() }} {{ $subjects->count() == 1 ? 'subject' : 'subjects' }} found
                        </span>
                    </h5>
                </div>

                <div class="default-table-area" style="margin-top: 0;">
                    <div class="table-responsive" style="max-height: none; overflow: visible; overflow-x: auto;">
                        <table class="table table-sm table-hover" style="margin-bottom: 0; white-space: nowrap;">
                            <thead>
                                <tr>
                                    <th style="padding: 8px 12px; font-size: 13px;">#</th>
                                    <th style="padding: 8px 12px; font-size: 13px;">Subject Name</th>
                                    <th style="padding: 8px 12px; font-size: 13px;">Teacher</th>
                                    <th style="padding: 8px 12px; font-size: 13px;">Session</th>
                                    <th style="padding: 8px 12px; font-size: 13px; text-align: center;">Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($subjects as $index => $subject)
                                    <tr>
                                        <td style="padding: 8px 12px; font-size: 13px;">{{ $index + 1 }}</td>
                                        <td style="padding: 8px 12px; font-size: 13px;">
                                            <strong class="text-primary">{{ $subject->subject_name }}</strong>
                                        </td>
                                        <td style="padding: 8px 12px; font-size: 13px;">
                                            <span class="badge bg-info text-white" style="font-size: 11px;">{{ $subject->teacher ?? 'N/A' }}</span>
                                        </td>
                                        <td style="padding: 8px 12px; font-size: 13px;">
                                            <span class="badge bg-secondary text-white" style="font-size: 11px;">{{ $subject->session ?? 'N/A' }}</span>
                                        </td>
                                        <td style="padding: 8px 12px; font-size: 13px; text-align: center;">
                                            <form action="{{ route('homework-diary.send') }}" method="POST" style="display: inline-block;">
                                                @csrf
                                                <input type="hidden" name="subject_id" value="{{ $subject->id }}">
                                                <input type="hidden" name="date" value="{{ $filterDate }}">
                                                <button type="submit" class="btn btn-sm btn-success px-3 py-1" title="Send Diary" onclick="return confirm('Are you sure you want to send diary for {{ $subject->subject_name }}?')">
                                                    <span class="material-symbols-outlined" style="font-size: 14px; color: white; vertical-align: middle;">send</span>
                                                    <span style="color: white; font-size: 12px;">Send Diary</span>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-5">
                                            <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">menu_book</span>
                                            <p class="mt-2 mb-0">No subjects found.</p>
                                            <p class="mt-1 mb-0" style="font-size: 13px;">Please add subjects for this Campus, Class, and Section combination.</p>
                                            <div class="mt-3">
                                                <a href="{{ route('manage-subjects') }}" class="btn btn-sm btn-primary px-4 py-2" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                                                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; color: white;">add</span>
                                                    <span style="color: white; font-size: 13px;">Add Subjects (Super Admin)</span>
                                                </a>
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
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
</script>
@endsection
