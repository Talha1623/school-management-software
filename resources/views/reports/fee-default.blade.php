@extends('layouts.app')

@section('title', 'Fee Default Reports')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Fee Default Reports</h4>
            </div>

            <!-- Filter Form -->
            <form action="{{ route('reports.fee-default') }}" method="GET" id="filterForm">
                <div class="row g-2 mb-3 align-items-end">
                    <!-- Campus -->
                    <div class="col-md-2">
                        <label for="filter_campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                        <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px;">
                            <option value="">All Campuses</option>
                            @foreach($campuses as $campus)
                                @php
                                    $campusName = $campus->campus_name ?? $campus;
                                @endphp
                                <option value="{{ $campusName }}" {{ $filterCampus == $campusName ? 'selected' : '' }}>{{ $campusName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Class -->
                    <div class="col-md-2">
                        <label for="filter_class" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class</label>
                        <select class="form-select form-select-sm" id="filter_class" name="filter_class" data-selected-class="{{ $filterClass }}" style="height: 32px;">
                            <option value="">All Classes</option>
                            @foreach($classes as $className)
                                <option value="{{ $className }}" {{ $filterClass == $className ? 'selected' : '' }}>{{ $className }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Section -->
                    <div class="col-md-2">
                        <label for="filter_section" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Section</label>
                        <select class="form-select form-select-sm" id="filter_section" name="filter_section" data-selected-section="{{ $filterSection }}" style="height: 32px;">
                            <option value="">All Sections</option>
                            @foreach($sections as $sectionName)
                                <option value="{{ $sectionName }}" {{ $filterSection == $sectionName ? 'selected' : '' }}>{{ $sectionName }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Type -->
                    <div class="col-md-2">
                        <label for="filter_type" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Type</label>
                        <select class="form-select form-select-sm" id="filter_type" name="filter_type" style="height: 32px;">
                            <option value="">All Types</option>
                            @if(isset($typeOptions))
                                @foreach($typeOptions as $key => $label)
                                    <option value="{{ $key }}" {{ $filterType == $key ? 'selected' : '' }}>{{ $label }}</option>
                                @endforeach
                            @else
                                <option value="all_detailed" {{ $filterType == 'all_detailed' ? 'selected' : '' }}>All Detailed</option>
                                <option value="admission_fee_only" {{ $filterType == 'admission_fee_only' ? 'selected' : '' }}>Admission Fee Only</option>
                                <option value="transport_fee_only" {{ $filterType == 'transport_fee_only' ? 'selected' : '' }}>Transport Fee Only</option>
                                <option value="card_fee_only" {{ $filterType == 'card_fee_only' ? 'selected' : '' }}>Card Fee Only</option>
                            @endif
                        </select>
                    </div>

                    <!-- Status -->
                    <div class="col-md-2">
                        <label for="filter_status" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Status</label>
                        <select class="form-select form-select-sm" id="filter_status" name="filter_status" style="height: 32px;">
                            <option value="">All Status</option>
                            @foreach($statusOptions as $statusValue => $statusLabel)
                                <option value="{{ $statusValue }}" {{ $filterStatus == $statusValue ? 'selected' : '' }}>{{ $statusLabel }}</option>
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
            @if(request()->hasAny(['filter_campus', 'filter_class', 'filter_section', 'filter_type', 'filter_status']))
            <div class="mt-3">
                <div class="mb-2 p-2 rounded-8" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 18px;">list</span>
                        <span>Fee Default Reports</span>
                    </h5>
                </div>

                <div class="default-table-area" style="margin-top: 0;">
                    <div class="table-responsive">
                        <table class="table">
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Student Code</th>
                                    <th>Student</th>
                                    <th>Parent</th>
                                    <th>Class</th>
                                    <th>Last Payment</th>
                                    <th>Due Invoices</th>
                                    <th>Total</th>
                                    <th>Paid</th>
                                    <th>Late</th>
                                    <th>Remaining</th>
                                    <th>Phone</th>
                                    <th>Whatsapp</th>
                                    <th>Action</th>
                                </tr>
                            </thead>
                            <tbody>
                                @if(isset($reportRows) && $reportRows->count() > 0)
                                    @forelse($reportRows as $row)
                                        <tr>
                                            <td>{{ $loop->iteration }}</td>
                                            <td><strong class="text-primary">{{ $row['student_code'] ?? 'N/A' }}</strong></td>
                                            <td><strong>{{ $row['student_name'] ?? 'N/A' }}</strong></td>
                                            <td>{{ $row['parent_name'] ?? 'N/A' }}</td>
                                            <td>{{ $row['class'] ?? 'N/A' }}</td>
                                            <td>{{ $row['last_payment'] ? \Carbon\Carbon::parse($row['last_payment'])->format('d-m-Y') : 'N/A' }}</td>
                                            <td>{{ $row['due_invoices'] ?? 0 }}</td>
                                            <td>{{ number_format($row['total'] ?? 0, 2) }}</td>
                                            <td>{{ number_format($row['paid'] ?? 0, 2) }}</td>
                                            <td>{{ number_format($row['late'] ?? 0, 2) }}</td>
                                            <td>{{ number_format($row['remaining'] ?? 0, 2) }}</td>
                                            <td>{{ $row['phone'] ?? 'N/A' }}</td>
                                            <td>{{ $row['whatsapp'] ?? 'N/A' }}</td>
                                            <td>
                                                @if(!empty($row['student_code']))
                                                    <a class="btn btn-sm btn-primary" href="{{ route('accounting.particular-receipt') }}?student_code={{ urlencode($row['student_code']) }}" target="_blank">
                                                        View
                                                    </a>
                                                @else
                                                    <span class="text-muted">N/A</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="13" class="text-center text-muted py-5">
                                                <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                                <p class="mt-2 mb-0">No records found.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                @else
                                    <tr>
                                        <td colspan="13" class="text-center text-muted py-5">
                                            <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                            <p class="mt-2 mb-0">No records found. Please apply filters to see results.</p>
                                        </td>
                                    </tr>
                                @endif
                            </tbody>
                        </table>
                    </div>
                </div>
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
        font-weight: 500;
        transition: all 0.3s ease;
        box-shadow: 0 2px 8px rgba(0, 52, 113, 0.25);
        display: inline-flex;
        align-items: center;
        justify-content: center;
        gap: 4px;
    }
    
    .filter-btn:hover {
        background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 12px rgba(0, 52, 113, 0.35);
        color: white;
    }
    
    .filter-btn:active {
        transform: translateY(0);
    }
    
    .form-select-sm {
        font-size: 12px;
    }
    
    .form-label {
        font-size: 12px;
        margin-bottom: 4px;
    }
    
    .rounded-8 {
        border-radius: 8px;
    }
    
    .rounded-10 {
        border-radius: 10px;
    }
    
    /* Table Compact Styling */
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
    
    .default-table-area table thead th:first-child {
        border-left: 1px solid #dee2e6;
    }
    
    .default-table-area table thead th:last-child {
        border-right: 1px solid #dee2e6;
    }
    
    .default-table-area table tbody td {
        padding: 5px 10px;
        font-size: 12px;
        vertical-align: middle;
        line-height: 1.4;
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
        padding-left: 10px;
    }
    
    .default-table-area table thead th:last-child,
    .default-table-area table tbody td:last-child {
        padding-right: 10px;
    }
    
    .default-table-area table tbody tr {
        height: 36px;
    }
    
    .default-table-area table tbody tr:first-child td {
        border-top: none;
    }
    
    .default-table-area .table-responsive {
        padding: 0;
        margin-top: 0;
    }
    
    .default-table-area {
        margin-top: 0 !important;
    }
    
    .default-table-area table tbody tr:hover {
        background-color: #f8f9fa;
    }
    
    .default-table-area .badge {
        font-size: 11px;
        padding: 3px 6px;
        font-weight: 500;
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const campusSelect = document.getElementById('filter_campus');
    const classSelect = document.getElementById('filter_class');
    const sectionSelect = document.getElementById('filter_section');

    function resetSections() {
        sectionSelect.innerHTML = '<option value="">All Sections</option>';
        sectionSelect.disabled = true;
    }

    function populateClasses(classes, selectedClass = '') {
        classSelect.innerHTML = '<option value="">All Classes</option>';
        if (classes && classes.length > 0) {
            classes.forEach(className => {
                const option = document.createElement('option');
                option.value = className;
                option.textContent = className;
                if (selectedClass && selectedClass === className) {
                    option.selected = true;
                }
                classSelect.appendChild(option);
            });
            classSelect.disabled = false;
        } else {
            classSelect.innerHTML = '<option value="">No classes found</option>';
            classSelect.disabled = false;
        }
    }

    function populateSections(sections, selectedSection = '') {
        sectionSelect.innerHTML = '<option value="">All Sections</option>';
        if (sections && sections.length > 0) {
            sections.forEach(sectionName => {
                const option = document.createElement('option');
                option.value = sectionName;
                option.textContent = sectionName;
                if (selectedSection && selectedSection === sectionName) {
                    option.selected = true;
                }
                sectionSelect.appendChild(option);
            });
        }
        sectionSelect.disabled = false;
    }

    function loadClassesByCampus(campus, selectedClass = '', selectedSection = '') {
        classSelect.disabled = true;
        classSelect.innerHTML = '<option value="">Loading...</option>';
        resetSections();

        const params = new URLSearchParams();
        if (campus) {
            params.append('campus', campus);
        }

        fetch(`{{ route('reports.fee-default.get-classes-by-campus') }}?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                populateClasses(data.classes || [], selectedClass);
                if (selectedClass) {
                    loadSections(selectedClass, selectedSection);
                }
            })
            .catch(error => {
                console.error('Error loading classes:', error);
                classSelect.innerHTML = '<option value="">Error loading classes</option>';
                classSelect.disabled = false;
            });
    }

    function loadSections(selectedClass, selectedSection = '') {
        const campus = campusSelect.value;
        if (!selectedClass) {
            resetSections();
            return;
        }

        sectionSelect.innerHTML = '<option value="">Loading...</option>';
        sectionSelect.disabled = true;

        const params = new URLSearchParams();
        params.append('class', selectedClass);
        if (campus) {
            params.append('campus', campus);
        }

        fetch(`{{ route('reports.fee-default.get-sections-by-class') }}?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                populateSections(data.sections || [], selectedSection);
            })
            .catch(error => {
                console.error('Error loading sections:', error);
                sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
                sectionSelect.disabled = false;
            });
    }

    campusSelect.addEventListener('change', function() {
        loadClassesByCampus(this.value);
    });

    classSelect.addEventListener('change', function() {
        loadSections(this.value);
    });

    const selectedClass = classSelect.dataset.selectedClass || '';
    const selectedSection = sectionSelect.dataset.selectedSection || '';
    loadClassesByCampus(campusSelect.value, selectedClass, selectedSection);
});
</script>
@endsection
