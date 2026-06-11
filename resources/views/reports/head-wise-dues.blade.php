@extends('layouts.app')

@section('title', 'Head Wise Dues Summary')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center gap-3 mb-3">
                <div>
                    <h4 class="mb-0 fs-16 fw-semibold" style="color: #003471;">Head Wise Dues Summary</h4>
                    <small class="text-muted">Columns only from Fee Type / Fee Head for each campus; paid from all fee titles, due from outstanding only (same ledger as Fee Payment)</small>
                </div>
                @if($hasFilters)
                <div class="d-flex gap-2 flex-wrap export-buttons">
                    <a href="{{ route('reports.head-wise-dues.export', array_merge(request()->query(), ['format' => 'excel'])) }}"
                       class="btn btn-sm px-2 py-1 export-btn excel-btn">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">description</span>
                        <span>Excel</span>
                    </a>
                    <a href="{{ route('reports.head-wise-dues.export', array_merge(request()->query(), ['format' => 'csv'])) }}"
                       class="btn btn-sm px-2 py-1 export-btn csv-btn">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">file_present</span>
                        <span>CSV</span>
                    </a>
                    <a href="{{ route('reports.head-wise-dues.export', array_merge(request()->query(), ['format' => 'pdf'])) }}"
                       class="btn btn-sm px-2 py-1 export-btn pdf-btn">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">picture_as_pdf</span>
                        <span>PDF</span>
                    </a>
                    <a href="{{ route('reports.head-wise-dues.print', array_merge(request()->query(), ['auto_print' => 1])) }}"
                       target="_blank"
                       class="btn btn-sm px-2 py-1 export-btn print-btn">
                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">print</span>
                        <span>Print</span>
                    </a>
                </div>
                @endif
            </div>

            @if(session('error'))
                <div class="alert alert-danger py-2 px-3 fs-12 mb-2">{{ session('error') }}</div>
            @endif

            <!-- Filters -->
            <form action="{{ route('reports.head-wise-dues') }}" method="GET" id="filterForm" class="mb-3">
                <div class="row g-2 align-items-end">
                    <div class="col-xl-3 col-lg-3 col-md-4 col-sm-6">
                        <label for="filter_campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                        <select class="form-select form-select-sm" id="filter_campus" name="filter_campus" style="height: 32px;">
                            <option value="">All Campuses</option>
                            @foreach($campuses as $campus)
                                @php
                                    $campusName = is_object($campus) ? ($campus->campus_name ?? $campus->name ?? '') : $campus;
                                @endphp
                                <option value="{{ $campusName }}" {{ ($filterCampus ?? '') == $campusName ? 'selected' : '' }}>{{ $campusName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-3 col-lg-3 col-md-4 col-sm-6">
                        <label for="filter_class" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class</label>
                        <select class="form-select form-select-sm" id="filter_class" name="filter_class" data-selected-class="{{ $filterClass ?? '' }}" style="height: 32px;">
                            <option value="">All Classes</option>
                            @foreach($classOptions as $className)
                                <option value="{{ $className }}" {{ ($filterClass ?? '') == $className ? 'selected' : '' }}>{{ $className }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-3 col-lg-3 col-md-4 col-sm-6">
                        <label for="filter_section" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Section</label>
                        <select class="form-select form-select-sm" id="filter_section" name="filter_section" data-selected-section="{{ $filterSection ?? '' }}" style="height: 32px;">
                            <option value="">All Sections</option>
                            @foreach($sectionOptions as $sectionName)
                                <option value="{{ $sectionName }}" {{ ($filterSection ?? '') == $sectionName ? 'selected' : '' }}>{{ $sectionName }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-xl-3 col-lg-3 col-md-4 col-sm-6">
                        <button type="submit" class="btn btn-sm w-100 filter-btn" style="height: 32px;">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">filter_alt</span>
                            <span style="font-size: 12px;">Filter</span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Report Display -->
            @if(!$hasFilters)
            <div class="text-center py-5 mt-3">
                <span class="material-symbols-outlined" style="font-size: 64px; color: #dee2e6; opacity: 0.5;">filter_list</span>
                <h5 class="mt-3 text-muted">Apply Filters to View Report</h5>
                <p class="text-muted mb-0">Please select campus or class, then click Filter.</p>
            </div>
            @elseif($feeHeads->isEmpty())
            <div class="text-center py-5 mt-3">
                <span class="material-symbols-outlined" style="font-size: 64px; color: #dee2e6; opacity: 0.5;">category</span>
                <h5 class="mt-3 text-muted">No Fee Heads</h5>
                <p class="text-muted mb-0">Add fee heads in <strong>Fee Type / Fee Head</strong> for this campus, then filter again.</p>
            </div>
            @elseif($allCampusData->count() > 0)
            <div id="reportContent">
                @foreach($allCampusData as $campusData)
                @php
                    $campusFeeHeads = $campusData['fee_heads'] ?? $feeHeads;
                @endphp
                <div class="mb-3">
                    <!-- Report Header -->
                    <div class="mb-3">
                        <h2 class="mb-1 fw-bold" style="color: #003471; font-size: 20px;">{{ $campusData['campus'] }}</h2>
                        <div style="font-size: 11px; color: #6c757d;">
                            <strong>Report Date:</strong> {{ date('d-m-Y H:i') }}
                        </div>
                    </div>

                    <!-- Report Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered" style="font-size: 12px;">
                            <thead style="background-color: #D3D3D3;">
                                <tr>
                                    <th style="padding: 6px 8px; text-align: left; border: 1px solid #dee2e6; font-weight: 600;">Class</th>
                                    @foreach($campusFeeHeads as $head)
                                        <th style="padding: 6px 8px; text-align: right; border: 1px solid #dee2e6; font-weight: 600;">{{ $head }}</th>
                                    @endforeach
                                    <th style="padding: 6px 8px; text-align: right; border: 1px solid #dee2e6; font-weight: 600;">Total Paid</th>
                                    <th style="padding: 6px 8px; text-align: right; border: 1px solid #dee2e6; font-weight: 600;">Total Due</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($campusData['rows'] as $data)
                                    <tr>
                                        <td style="padding: 6px 8px; border: 1px solid #dee2e6; background-color: #D3D3D3;">{{ $data['class'] }}</td>
                                        @foreach($campusFeeHeads as $head)
                                            @php
                                                $headData = $data['heads'][$head] ?? ['paid' => 0, 'due' => 0];
                                            @endphp
                                            <td style="padding: 6px 8px; text-align: right; border: 1px solid #dee2e6; background-color: #D3D3D3; line-height: 1.35;">
                                                <span class="text-success">{{ number_format($headData['paid'] ?? 0, 2) }}</span>
                                                <br><span class="text-danger">{{ number_format($headData['due'] ?? 0, 2) }}</span>
                                                <br><small class="text-muted">Paid / Due</small>
                                            </td>
                                        @endforeach
                                        <td style="padding: 6px 8px; text-align: right; border: 1px solid #dee2e6; font-weight: 600; background-color: #D3D3D3; color: #198754;">{{ number_format($data['total_paid'] ?? 0, 2) }}</td>
                                        <td style="padding: 6px 8px; text-align: right; border: 1px solid #dee2e6; font-weight: 600; background-color: #D3D3D3; color: #dc3545;">{{ number_format($data['total'], 2) }}</td>
                                    </tr>
                                @endforeach
                                <tr style="background-color: #e8f5e9;">
                                    <td style="padding: 6px 8px; border: 1px solid #dee2e6; font-weight: 600;">Total Paid</td>
                                    @foreach($campusFeeHeads as $head)
                                        <td style="padding: 6px 8px; text-align: right; border: 1px solid #dee2e6; font-weight: 600; color: #198754;">{{ number_format($campusData['head_paid_totals'][$head] ?? 0, 2) }}</td>
                                    @endforeach
                                    <td style="padding: 6px 8px; text-align: right; border: 1px solid #dee2e6; font-weight: 600; color: #198754;">{{ number_format($campusData['total_paid'] ?? 0, 2) }}</td>
                                    <td style="padding: 6px 8px; text-align: right; border: 1px solid #dee2e6;"></td>
                                </tr>
                                <tr style="background-color: #ffebee;">
                                    <td style="padding: 6px 8px; border: 1px solid #dee2e6; font-weight: 600;">Total Due</td>
                                    @foreach($campusFeeHeads as $head)
                                        <td style="padding: 6px 8px; text-align: right; border: 1px solid #dee2e6; font-weight: 600; color: #dc3545;">{{ number_format($campusData['head_totals'][$head] ?? 0, 2) }}</td>
                                    @endforeach
                                    <td style="padding: 6px 8px; text-align: right; border: 1px solid #dee2e6;"></td>
                                    <td style="padding: 6px 8px; text-align: right; border: 1px solid #dee2e6; font-weight: 600; color: #dc3545;">{{ number_format($campusData['total'], 2) }}</td>
                                </tr>
                            </tbody>
                        </table>
                    </div>
                </div>
                @endforeach
            </div>
            @else
            <div class="text-center py-5 mt-3">
                <span class="material-symbols-outlined" style="font-size: 64px; color: #dee2e6; opacity: 0.5;">inbox</span>
                <h5 class="mt-3 text-muted">No Data Found</h5>
                <p class="text-muted mb-0">No outstanding fees for selected filters (same as Fee Payment with no Search Results).</p>
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
    box-shadow: 0 2px 6px rgba(0, 52, 113, 0.2);
}

.filter-btn:hover {
    background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 10px rgba(0, 52, 113, 0.3);
    color: white;
}

.filter-btn:active {
    transform: translateY(0);
}

.filter-btn .material-symbols-outlined {
    color: white !important;
}

.form-select-sm {
    font-size: 13px;
    padding: 6px 12px;
    border-radius: 6px;
    border: 1px solid #dee2e6;
    transition: all 0.3s ease;
}

.form-label {
    font-size: 12px;
}

.btn-sm.filter-btn {
    height: 32px;
}

.form-select-sm:focus {
    border-color: #003471;
    box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.15);
    outline: none;
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

.excel-btn { background-color: #28a745; color: white; }
.excel-btn:hover { background-color: #218838; color: white; transform: translateY(-1px); }

.csv-btn { background-color: #ff9800; color: white; }
.csv-btn:hover { background-color: #f57c00; color: white; transform: translateY(-1px); }

.pdf-btn { background-color: #dc3545; color: white; }
.pdf-btn:hover { background-color: #c82333; color: white; transform: translateY(-1px); }

.print-btn { background-color: #2196f3; color: white; }
.print-btn:hover { background-color: #0b7dda; color: white; transform: translateY(-1px); }

/* Print Styles */
@media print {
    /* Hide everything by default */
    body * {
        visibility: hidden;
    }
    
    /* Hide sidebar, header, and other layout elements */
    .sidebar-area,
    .header-area,
    #header-area,
    .container-fluid,
    .row,
    .card,
    .btn,
    nav,
    header,
    aside,
    .d-flex.justify-content-between,
    h2,
    h4,
    .text-end {
        display: none !important;
        visibility: hidden !important;
    }
    
    /* Only show the table and its container */
    .table-responsive,
    .table,
    .table thead,
    .table tbody,
    .table tr,
    .table th,
    .table td {
        visibility: visible !important;
        display: block;
    }
    
    .table {
        display: table !important;
    }
    
    .table thead {
        display: table-header-group !important;
    }
    
    .table tbody {
        display: table-row-group !important;
    }
    
    .table tr {
        display: table-row !important;
    }
    
    .table th,
    .table td {
        display: table-cell !important;
    }
    
    /* Show only table container */
    .table-responsive {
        position: absolute;
        left: 0;
        top: 0;
        width: 100%;
        margin: 0;
        padding: 0;
    }
    
    /* Table styles for print */
    .table {
        font-size: 12px !important;
        border-collapse: collapse !important;
        width: 100% !important;
        margin: 0 !important;
    }
    
    .table th, 
    .table td {
        padding: 8px !important;
        border: 1px solid #000000 !important;
        color: #000000 !important;
    }
    
    .table thead th {
        background-color: #f0f0f0 !important;
        color: #000000 !important;
        font-weight: 600 !important;
    }
    
    .table tbody tr:nth-child(even) {
        background-color: #ffffff !important;
    }
    
    .table tbody tr:last-child {
        background-color: #f0f0f0 !important;
        font-weight: 600 !important;
    }
    
    /* Remove background colors and gradients */
    body {
        background: #ffffff !important;
        margin: 0;
        padding: 20px;
    }
    
    .table thead th,
    .table tbody tr:last-child {
        background-color: #f0f0f0 !important;
    }
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const campusSelect = document.getElementById('filter_campus');
    const classSelect = document.getElementById('filter_class');
    const sectionSelect = document.getElementById('filter_section');

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
        }
    }

    function loadClassesByCampus(campus, selectedClass = '') {
        classSelect.innerHTML = '<option value="">Loading...</option>';
        sectionSelect.innerHTML = '<option value="">All Sections</option>';
        const params = new URLSearchParams();
        if (campus) {
            params.append('campus', campus);
        }
        fetch(`{{ route('reports.head-wise-dues.get-classes-by-campus') }}?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                populateClasses(data.classes || [], selectedClass);
            })
            .catch(error => {
                console.error('Error loading classes:', error);
                classSelect.innerHTML = '<option value="">All Classes</option>';
            });
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
    }

    function loadSectionsByClass(campus, className, selectedSection = '') {
        if (!className) {
            populateSections([], selectedSection);
            return;
        }
        sectionSelect.innerHTML = '<option value="">Loading...</option>';
        const params = new URLSearchParams();
        params.append('class', className);
        if (campus) {
            params.append('campus', campus);
        }
        fetch(`{{ route('reports.head-wise-dues.get-sections-by-class') }}?${params.toString()}`)
            .then(response => response.json())
            .then(data => {
                populateSections(data.sections || [], selectedSection);
            })
            .catch(error => {
                console.error('Error loading sections:', error);
                sectionSelect.innerHTML = '<option value="">All Sections</option>';
            });
    }

    campusSelect.addEventListener('change', function() {
        loadClassesByCampus(this.value);
        loadSectionsByClass(this.value, '');
    });

    classSelect.addEventListener('change', function() {
        loadSectionsByClass(campusSelect.value, this.value);
    });

    const selectedClass = classSelect.dataset.selectedClass || '';
    const selectedSection = sectionSelect.dataset.selectedSection || '';
    loadClassesByCampus(campusSelect.value, selectedClass);
    if (selectedClass) {
        loadSectionsByClass(campusSelect.value, selectedClass, selectedSection);
    } else {
        populateSections([], selectedSection);
    }
});
</script>
@endsection
