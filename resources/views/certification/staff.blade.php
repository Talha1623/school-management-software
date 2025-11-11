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
                                <option value="{{ $campus }}" {{ $filterCampus == $campus ? 'selected' : '' }}>{{ $campus }}</option>
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
</style>
@endsection
