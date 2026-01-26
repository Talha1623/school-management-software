@extends('layouts.app')

@section('title', 'Head Wise Dues Summary')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
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
                        <button type="submit" class="btn btn-sm w-100 filter-btn" style="height: 32px;">
                            <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">filter_alt</span>
                            <span style="font-size: 12px;">Filter</span>
                        </button>
                    </div>
                </div>
            </form>

            <!-- Report Display -->
            @if($allCampusData->count() > 0)
            <div id="reportContent">
                @foreach($allCampusData as $campusData)
                <div class="mb-3">
                    <!-- Report Header -->
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div>
                            <h2 class="mb-1 fw-bold" style="color: #003471; font-size: 20px;">ICMS</h2>
                            <h4 class="mb-0 fw-semibold" style="color: #495057; font-size: 14px;">Head Wise Dues Summary</h4>
                        </div>
                        <div class="text-end">
                            <button type="button" class="btn btn-sm btn-primary" onclick="window.print()" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none; color: white;">
                                <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle; color: white;">print</span>
                                <span style="color: white; font-size: 12px;">Print</span>
                            </button>
                            <div class="mt-1" style="font-size: 11px; color: #6c757d;">
                                <div><strong>Campus:</strong> {{ $campusData['campus'] }}</div>
                                <div><strong>Print Date:</strong> {{ date('d-m-Y H:i') }}</div>
                            </div>
                        </div>
                    </div>

                    <!-- Report Table -->
                    <div class="table-responsive">
                        <table class="table table-bordered" style="font-size: 12px;">
                            <thead style="background-color: #f8f9fa;">
                                <tr>
                                    <th style="padding: 6px 8px; text-align: left; border: 1px solid #dee2e6; font-weight: 600;">Class</th>
                                    <th style="padding: 6px 8px; text-align: right; border: 1px solid #dee2e6; font-weight: 600;">Monthly Fee</th>
                                    <th style="padding: 6px 8px; text-align: right; border: 1px solid #dee2e6; font-weight: 600;">Muhammad Talha</th>
                                    <th style="padding: 6px 8px; text-align: right; border: 1px solid #dee2e6; font-weight: 600;">Card Fees</th>
                                    <th style="padding: 6px 8px; text-align: right; border: 1px solid #dee2e6; font-weight: 600;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($campusData['data'] as $data)
                                    <tr>
                                        <td style="padding: 6px 8px; border: 1px solid #dee2e6;">{{ $data['class'] }}</td>
                                        <td style="padding: 6px 8px; text-align: right; border: 1px solid #dee2e6;">{{ number_format($data['monthly_fee'], 2) }}</td>
                                        <td style="padding: 6px 8px; text-align: right; border: 1px solid #dee2e6;">{{ number_format($data['muhammad_talha'], 2) }}</td>
                                        <td style="padding: 6px 8px; text-align: right; border: 1px solid #dee2e6;">{{ number_format($data['card_fees'], 2) }}</td>
                                        <td style="padding: 6px 8px; text-align: right; border: 1px solid #dee2e6; font-weight: 600;">{{ number_format($data['total'], 2) }}</td>
                                    </tr>
                                @endforeach
                                <!-- Summary Row -->
                                <tr style="background-color: #f8f9fa;">
                                    <td style="padding: 6px 8px; border: 1px solid #dee2e6; font-weight: 600;">Total Unpaid</td>
                                    <td style="padding: 6px 8px; text-align: right; border: 1px solid #dee2e6; font-weight: 600;">{{ number_format($campusData['total']['monthly_fee'], 2) }}</td>
                                    <td style="padding: 6px 8px; text-align: right; border: 1px solid #dee2e6; font-weight: 600;">{{ number_format($campusData['total']['muhammad_talha'], 2) }}</td>
                                    <td style="padding: 6px 8px; text-align: right; border: 1px solid #dee2e6; font-weight: 600;">{{ number_format($campusData['total']['card_fees'], 2) }}</td>
                                    <td style="padding: 6px 8px; text-align: right; border: 1px solid #dee2e6; font-weight: 600;">{{ number_format($campusData['total']['total'], 2) }}</td>
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
                <p class="text-muted mb-0">No dues data available.</p>
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

    campusSelect.addEventListener('change', function() {
        loadClassesByCampus(this.value);
    });

    const selectedClass = classSelect.dataset.selectedClass || '';
    loadClassesByCampus(campusSelect.value, selectedClass);
});
</script>
@endsection
