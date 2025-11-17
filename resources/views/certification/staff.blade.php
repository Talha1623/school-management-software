@extends('layouts.app')

@section('title', 'Staff Certification')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Staff Certification</h4>
            </div>

            <!-- Filter Form -->
            <form action="{{ route('certification.staff') }}" method="GET" id="filterForm">
                <div class="row g-2 mb-3 align-items-end">
                    <!-- Campus -->
                    <div class="col-md-4">
                        <label for="filter_campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                        <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px;">
                            <option value="">All Campuses</option>
                            @foreach($campuses as $campus)
                                @php
                                    $campusName = is_object($campus) ? ($campus->campus_name ?? $campus->name ?? '') : $campus;
                                    $campusValue = $campusName;
                                @endphp
                                <option value="{{ $campusValue }}" {{ $filterCampus == $campusValue ? 'selected' : '' }}>{{ $campusName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Certificate Type -->
                    <div class="col-md-4">
                        <label for="filter_certificate_type" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Certificate Type</label>
                        <select class="form-select form-select-sm" id="filter_certificate_type" name="filter_certificate_type" style="height: 32px;">
                            <option value="">All Types</option>
                            @foreach($certificateTypes as $type)
                                <option value="{{ $type }}" {{ $filterCertificateType == $type ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Staff Type -->
                    <div class="col-md-4">
                        <label for="filter_staff_type" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Staff Type</label>
                        <select class="form-select form-select-sm" id="filter_staff_type" name="filter_staff_type" style="height: 32px;">
                            <option value="">All Types</option>
                            @foreach($staffTypes as $type)
                                <option value="{{ $type }}" {{ $filterStaffType == $type ? 'selected' : '' }}>{{ $type }}</option>
                            @endforeach
                        </select>
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
        </div>
    </div>
</div>

@if(isset($staff) && $filterCertificateType)
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
                <div class="d-flex align-items-center gap-3">
                    <h4 class="mb-0 fs-16 fw-semibold">Staff List - {{ $filterCertificateType }}</h4>
                    <span class="badge bg-info">{{ $staff->count() }} Staff Member(s)</span>
                </div>
                
                <!-- Search -->
                <div class="d-flex align-items-center gap-2">
                    <label for="searchInput" class="mb-0 fs-13 fw-medium text-dark">Search:</label>
                    <div class="input-group input-group-sm search-input-group" style="width: 280px;">
                        <span class="input-group-text bg-light border-end-0" style="background-color: #f0f4ff !important; border-color: #e0e7ff; padding: 4px 8px;">
                            <span class="material-symbols-outlined" style="font-size: 14px; color: #003471;">search</span>
                        </span>
                        <input type="text" 
                               id="searchInput" 
                               class="form-control border-start-0 border-end-0" 
                               placeholder="Search by name, emp id..." 
                               value="{{ $search ?? '' }}" 
                               onkeypress="handleSearchKeyPress(event)" 
                               oninput="handleSearchInput(event)" 
                               style="padding: 4px 8px; font-size: 13px;">
                        @if(isset($search) && $search)
                            <button class="btn btn-outline-secondary border-start-0 border-end-0" 
                                    type="button" 
                                    onclick="clearSearch()" 
                                    title="Clear search" 
                                    style="padding: 4px 8px;">
                                <span class="material-symbols-outlined" style="font-size: 14px;">close</span>
                            </button>
                        @endif
                        <button class="btn btn-sm search-btn" 
                                type="button" 
                                onclick="performSearch()" 
                                title="Search" 
                                style="padding: 4px 10px;">
                            <span class="material-symbols-outlined" style="font-size: 14px;">search</span>
                        </button>
                    </div>
                </div>
            </div>

            <div class="table-responsive">
                <table class="table table-sm table-hover">
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Photo</th>
                            <th>Name</th>
                            <th>Emp. ID</th>
                            <th>Designation</th>
                            <th>Campus</th>
                            <th class="text-end">Action</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($staff as $index => $member)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>
                                @if($member->photo)
                                    <img src="{{ asset('storage/' . $member->photo) }}" alt="Photo" style="width: 40px; height: 40px; border-radius: 50%; object-fit: cover;">
                                @else
                                    <div style="width: 40px; height: 40px; border-radius: 50%; background-color: #e0e7ff; display: flex; align-items: center; justify-content: center;">
                                        <span class="material-symbols-outlined" style="font-size: 20px; color: #003471;">person</span>
                                    </div>
                                @endif
                            </td>
                            <td><strong>{{ $member->name }}</strong></td>
                            <td>{{ $member->emp_id ?? 'N/A' }}</td>
                            <td>{{ $member->designation ?? 'N/A' }}</td>
                            <td>{{ $member->campus ?? 'N/A' }}</td>
                            <td class="text-end">
                                <a href="{{ route('certification.staff.generate', ['staff' => $member->id, 'type' => $filterCertificateType]) }}" 
                                   target="_blank"
                                   class="btn btn-sm btn-primary px-3 py-1" style="color: white !important;">
                                    <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle; color: white !important;">print</span>
                                    <span style="color: white !important;">Generate Certificate</span>
                                </a>
                            </td>
                        </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@elseif(isset($staff) && $staff->isEmpty() && $filterCertificateType)
<div class="row">
    <div class="col-12">
        <div class="alert alert-info">
            <span class="material-symbols-outlined" style="vertical-align: middle;">info</span>
            No staff members found for the selected filters.
        </div>
    </div>
</div>
@endif

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

.search-input-group .form-control:focus {
    border-color: #e0e7ff;
    box-shadow: none;
}

.search-btn {
    background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
    color: white;
    border: none;
    transition: all 0.3s ease;
}

.search-btn:hover {
    background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
    transform: translateY(-1px);
    box-shadow: 0 2px 4px rgba(0, 52, 113, 0.3);
}

.search-btn:disabled {
    opacity: 0.6;
    cursor: not-allowed;
    transform: none;
}
</style>

<script>
// Search functionality
function performSearch() {
    const searchInput = document.getElementById('searchInput');
    if (!searchInput) return;
    
    const searchValue = searchInput.value.trim();
    const url = new URL(window.location.href);
    
    if (searchValue) {
        url.searchParams.set('search', searchValue);
    } else {
        url.searchParams.delete('search');
    }
    
    // Preserve filter values
    const filterCampus = '{{ $filterCampus ?? '' }}';
    const filterCertificateType = '{{ $filterCertificateType ?? '' }}';
    const filterStaffType = '{{ $filterStaffType ?? '' }}';
    
    if (filterCampus) url.searchParams.set('filter_campus', filterCampus);
    if (filterCertificateType) url.searchParams.set('filter_certificate_type', filterCertificateType);
    if (filterStaffType) url.searchParams.set('filter_staff_type', filterStaffType);
    
    // Show loading state
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
    // Auto-clear if input is empty (optional)
}

function clearSearch() {
    const url = new URL(window.location.href);
    url.searchParams.delete('search');
    
    // Preserve filter values
    const filterCampus = '{{ $filterCampus ?? '' }}';
    const filterCertificateType = '{{ $filterCertificateType ?? '' }}';
    const filterStaffType = '{{ $filterStaffType ?? '' }}';
    
    if (filterCampus) url.searchParams.set('filter_campus', filterCampus);
    if (filterCertificateType) url.searchParams.set('filter_certificate_type', filterCertificateType);
    if (filterStaffType) url.searchParams.set('filter_staff_type', filterStaffType);
    
    window.location.href = url.toString();
}
</script>
@endsection
