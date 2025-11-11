@extends('layouts.app')

@section('title', 'Admission Data Reports')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Admission Data Reports</h4>
            </div>

            <!-- Filter Form -->
            <form action="{{ route('reports.admission-data') }}" method="GET" id="filterForm">
                <div class="row g-2 mb-3 align-items-end">
                    <!-- Campus -->
                    <div class="col-md-2">
                        <label for="filter_campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                        <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px;">
                            <option value="">All Campuses</option>
                            @foreach($campuses as $campus)
                                <option value="{{ $campus }}" {{ $filterCampus == $campus ? 'selected' : '' }}>{{ $campus }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Class -->
                    <div class="col-md-2">
                        <label for="filter_class" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class</label>
                        <select class="form-select form-select-sm" id="filter_class" name="filter_class" style="height: 32px;">
                            <option value="">All Classes</option>
                            @foreach($classes as $className)
                                <option value="{{ $className }}" {{ $filterClass == $className ? 'selected' : '' }}>{{ $className }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Section -->
                    <div class="col-md-2">
                        <label for="filter_section" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Section</label>
                        <select class="form-select form-select-sm" id="filter_section" name="filter_section" style="height: 32px;">
                            <option value="">All Sections</option>
                            @foreach($sections as $sectionName)
                                <option value="{{ $sectionName }}" {{ $filterSection == $sectionName ? 'selected' : '' }}>{{ $sectionName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Status -->
                    <div class="col-md-2">
                        <label for="filter_status" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Status</label>
                        <select class="form-select form-select-sm" id="filter_status" name="filter_status" style="height: 32px;">
                            <option value="">All Status</option>
                            @foreach($statusOptions as $status)
                                <option value="{{ $status }}" {{ $filterStatus == $status ? 'selected' : '' }}>{{ $status }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Filter Button -->
                    <div class="col-md-2">
                        <button type="submit" class="btn btn-sm py-1 px-3 rounded-8 filter-btn w-100" style="height: 32px;">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">filter_alt</span>
                            <span style="font-size: 12px;">Filter</span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Results Table -->
            @if(request()->hasAny(['filter_campus', 'filter_class', 'filter_section', 'filter_status']))
            <div class="mt-3">
                <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 18px;">list</span>
                        <span>Admission Data Reports</span>
                    </h5>
                </div>

                <div class="default-table-area" style="margin-top: 0;">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Photo</th>
                                    <th>Student Code</th>
                                    <th>Student Name</th>
                                    <th>GR Number</th>
                                    <th>Campus</th>
                                    <th>Class</th>
                                    <th>Section</th>
                                    <th>Gender</th>
                                    <th>Date of Birth</th>
                                    <th>Admission Date</th>
                                    <th>Father Name</th>
                                    <th>Father Phone</th>
                                    <th>WhatsApp</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse($admissionRecords as $index => $record)
                                <tr>
                                    <td>{{ $index + 1 }}</td>
                                    <td>
                                        @if($record['photo'])
                                            <img src="{{ asset('storage/' . $record['photo']) }}" alt="{{ $record['student_name'] }}" class="rounded-circle" style="width: 40px; height: 40px; object-fit: cover;">
                                        @else
                                            <div class="rounded-circle bg-secondary d-flex align-items-center justify-content-center" style="width: 40px; height: 40px;">
                                                <span class="text-white fw-bold">{{ substr($record['student_name'], 0, 1) }}</span>
                                            </div>
                                        @endif
                                    </td>
                                    <td>{{ $record['student_code'] ?? 'N/A' }}</td>
                                    <td>{{ $record['student_name'] }} {{ $record['surname_caste'] ?? '' }}</td>
                                    <td>{{ $record['gr_number'] ?? 'N/A' }}</td>
                                    <td>{{ $record['campus'] ?? 'N/A' }}</td>
                                    <td>{{ $record['class'] }}</td>
                                    <td>{{ $record['section'] ?? 'N/A' }}</td>
                                    <td>{{ ucfirst($record['gender'] ?? 'N/A') }}</td>
                                    <td>{{ $record['date_of_birth'] ? date('d M Y', strtotime($record['date_of_birth'])) : 'N/A' }}</td>
                                    <td>{{ $record['admission_date'] ? date('d M Y', strtotime($record['admission_date'])) : 'N/A' }}</td>
                                    <td>{{ $record['father_name'] ?? 'N/A' }}</td>
                                    <td>{{ $record['father_phone'] ?? 'N/A' }}</td>
                                    <td>{{ $record['whatsapp_number'] ?? 'N/A' }}</td>
                                    <td>
                                        @if($record['status'] == 'Active')
                                            <span class="badge bg-success">Active</span>
                                        @elseif($record['status'] == 'Inactive')
                                            <span class="badge bg-danger">Inactive</span>
                                        @elseif($record['status'] == 'Pending')
                                            <span class="badge bg-warning text-dark">Pending</span>
                                        @elseif($record['status'] == 'Graduated')
                                            <span class="badge bg-info">Graduated</span>
                                        @else
                                            <span class="badge bg-secondary">Transferred</span>
                                        @endif
                                    </td>
                                </tr>
                                @empty
                                <tr>
                                    <td colspan="15" class="text-center py-4">
                                        <div class="d-flex flex-column align-items-center">
                                            <span class="material-symbols-outlined text-muted" style="font-size: 48px;">inbox</span>
                                            <p class="text-muted mt-2 mb-0">No admission records found</p>
                                        </div>
                                    </td>
                                </tr>
                                @endforelse
                            </tbody>
                            @if($admissionRecords->count() > 0)
                            <tfoot>
                                <tr class="fw-bold" style="background-color: #f8f9fa;">
                                    <td colspan="14" class="text-end">Total Students:</td>
                                    <td>{{ $admissionRecords->count() }}</td>
                                </tr>
                            </tfoot>
                            @endif
                        </table>
                    </div>
                </div>
            </div>
            @else
            <div class="text-center py-5">
                <span class="material-symbols-outlined text-muted" style="font-size: 64px;">filter_alt</span>
                <p class="text-muted mt-3 mb-0">Please apply filters to view admission data reports</p>
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

.default-table-area {
    background: #fff;
    border-radius: 8px;
    overflow: hidden;
}

.default-table-area table {
    margin-bottom: 0;
}

.default-table-area thead {
    background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
}

.default-table-area thead th {
    font-weight: 600;
    font-size: 13px;
    color: #003471;
    border-bottom: 2px solid #dee2e6;
    padding: 12px;
}

.default-table-area tbody td {
    font-size: 13px;
    padding: 12px;
    vertical-align: middle;
}

.default-table-area tbody tr:hover {
    background-color: #f8f9fa;
}

.badge {
    font-size: 11px;
    padding: 4px 8px;
}
</style>
@endsection
