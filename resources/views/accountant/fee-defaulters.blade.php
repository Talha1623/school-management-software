@extends('layouts.accountant')

@section('title', 'Fee Defaulters Reports')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Fee Default Reports</h4>
            </div>

            <!-- Filter Form -->
            <form action="{{ route('accountant.fee-defaulters') }}" method="GET" id="filterForm">
                <div class="row g-2 mb-3 align-items-end">
                    <!-- Campus -->
                    <div class="col-md-2">
                        <label for="filter_campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                        <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px;">
                            <option value="">All Campuses</option>
                            @foreach($campuses as $campus)
                                @php $campusName = $campus->campus_name ?? $campus; @endphp
                                <option value="{{ $campusName }}" data-campus-id="{{ $campus->id ?? '' }}" {{ ($filterCampus == $campusName || (isset($defaultCampus) && $defaultCampus === $campusName && !$filterCampus)) ? 'selected' : '' }}>{{ $campusName }}</option>
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
                        <select class="form-select form-select-sm" id="filter_type" name="filter_type" data-selected-type="{{ $filterType }}" style="height: 32px;">
                            <option value="">All Types</option>
                            @foreach($typeOptions as $key => $label)
                                <option value="{{ $key }}" {{ $filterType == $key ? 'selected' : '' }}>{{ $label }}</option>
                            @endforeach
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
                <div class="print-area">
                <div class="d-none d-print-block text-center mb-2 print-header">
                    <h3 class="mb-1">Fee Default Reports</h3>
                    <div class="small">Generated: {{ now()->format('d M Y, h:i A') }}</div>
                </div>

                <div class="mb-2 p-2 rounded-8 d-print-none" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
                    <h5 class="mb-0 text-white fs-15 fw-semibold d-flex align-items-center gap-2">
                        <span class="material-symbols-outlined" style="font-size: 18px;">list</span>
                        <span>Fee Default Reports</span>
                    </h5>
                </div>

                <div class="d-flex justify-content-end mb-2 d-print-none">
                    <div class="d-flex gap-2 flex-wrap">
                        <a href="{{ route('accountant.fee-defaulters.export', ['format' => 'excel']) }}?{{ http_build_query(request()->except(['page'])) }}" class="btn btn-sm px-2 py-1 export-btn excel-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                            <span>Excel</span>
                        </a>
                        <a href="{{ route('accountant.fee-defaulters.export', ['format' => 'csv']) }}?{{ http_build_query(request()->except(['page'])) }}" class="btn btn-sm px-2 py-1 export-btn csv-btn">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">table_view</span>
                            <span>CSV</span>
                        </a>
                        <a href="{{ route('accountant.fee-defaulters.export', ['format' => 'pdf']) }}?{{ http_build_query(request()->except(['page'])) }}" class="btn btn-sm px-2 py-1 export-btn pdf-btn" target="_blank">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">picture_as_pdf</span>
                            <span>PDF</span>
                        </a>
                        <a href="{{ route('accountant.fee-defaulters.print') }}?{{ http_build_query(array_merge(request()->except(['page']), ['auto_print' => 1])) }}" class="btn btn-sm px-2 py-1 export-btn print-btn" target="_blank">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">print</span>
                            <span>Print</span>
                        </a>
                    </div>
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
                                    <th>Total Amount</th>
                                    <th>Late Fee</th>
                                    <th>Total Dues</th>
                                    <th>Phone</th>
                                    <th>Whatsapp</th>
                                    <th class="no-print">Action</th>
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
                                            <td>{{ number_format($row['total_amount'] ?? 0, 2) }}</td>
                                            <td>{{ number_format($row['late'] ?? 0, 2) }}</td>
                                            <td>{{ number_format($row['total_dues'] ?? 0, 2) }}</td>
                                            <td>{{ $row['phone'] ?? 'N/A' }}</td>
                                            <td>{{ $row['whatsapp'] ?? 'N/A' }}</td>
                                            <td class="no-print">
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
                                            <td colspan="12" class="text-center text-muted py-5">
                                                <span class="material-symbols-outlined" style="font-size: 48px; opacity: 0.3;">inbox</span>
                                                <p class="mt-2 mb-0">No records found.</p>
                                            </td>
                                        </tr>
                                    @endforelse
                                @else
                                    <tr>
                                        <td colspan="12" class="text-center text-muted py-5">
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
    }

    .csv-btn {
        background-color: #17a2b8;
        color: white;
    }

    .csv-btn:hover {
        background-color: #138496;
        color: white;
    }

    .pdf-btn {
        background-color: #dc3545;
        color: white;
    }

    .pdf-btn:hover {
        background-color: #c82333;
        color: white;
    }

    .print-btn {
        background-color: #6c757d;
        color: white;
    }

    .print-btn:hover {
        background-color: #5a6268;
        color: white;
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

    @media print {
        .filter-btn,
        .export-btn,
        .btn,
        nav,
        header,
        footer {
            display: none !important;
        }

        body * {
            visibility: hidden;
        }

        .print-area, .print-area * {
            visibility: visible;
        }

        .print-area {
            position: absolute;
            left: 0;
            top: 0;
            width: 100%;
            padding: 0 10mm;
        }

        .default-table-area table {
            width: 100% !important;
            font-size: 12px;
        }

        .default-table-area thead th,
        .default-table-area tbody td {
            padding: 6px 8px !important;
            border-color: #000 !important;
        }

        .default-table-area th.no-print,
        .default-table-area td.no-print {
            display: none !important;
        }

        * {
            -webkit-print-color-adjust: exact;
            print-color-adjust: exact;
            color: #000 !important;
        }
    }
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const campusSelect = document.getElementById('filter_campus');
    const classSelect = document.getElementById('filter_class');
    const sectionSelect = document.getElementById('filter_section');
    const typeSelect = document.getElementById('filter_type');

    function populateFeeTypes(types, selectedType = '') {
        typeSelect.innerHTML = '<option value="">All Types</option>';
        if (types && types.length > 0) {
            types.forEach(item => {
                const option = document.createElement('option');
                option.value = item.value;
                option.textContent = item.label;
                if (selectedType && selectedType === item.value) {
                    option.selected = true;
                }
                typeSelect.appendChild(option);
            });
        }
    }

    function loadFeeTypes(campus, selectedType = '') {
        const params = new URLSearchParams();
        if (campus) {
            params.append('campus', campus);
        }

        fetch(`{{ route('accountant.fee-defaulters.get-fee-types-by-campus') }}?${params.toString()}`)
            .then(response => response.json())
            .then(data => populateFeeTypes(data.types || [], selectedType))
            .catch(error => console.error('Error loading fee types:', error));
    }

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

        fetch(`{{ route('accountant.fee-defaulters.get-classes-by-campus') }}?${params.toString()}`)
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

        fetch(`{{ route('accountant.fee-defaulters.get-sections-by-class') }}?${params.toString()}`)
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
        loadFeeTypes(this.value);
    });

    classSelect.addEventListener('change', function() {
        loadSections(this.value);
    });

    const selectedClass = classSelect.dataset.selectedClass || '';
    const selectedSection = sectionSelect.dataset.selectedSection || '';
    const selectedType = typeSelect.dataset.selectedType || '';
    loadClassesByCampus(campusSelect.value, selectedClass, selectedSection);
    loadFeeTypes(campusSelect.value, selectedType);
});
</script>
@endsection
