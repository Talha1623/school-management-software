@if($isPrint)
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Admission Forms Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            @page {
                margin: 1cm;
            }
            * {
                margin: 0;
                padding: 0;
            }
            html, body {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            body {
                padding: 20px !important;
                margin: 0 !important;
                background: white !important;
            }
            /* Hide sidebar and all navigation elements - comprehensive list */
            .sidebar-area,
            #sidebar-area,
            .sidebar,
            aside,
            #layout-menu,
            .layout-menu,
            .menu-vertical,
            .menu,
            .navbar,
            .navbar-area,
            .header-area,
            #header-area,
            header,
            .main-header,
            .header-navbar,
            .left-header-content,
            .right-header-content,
            .theme-settings-area,
            .theme-settings,
            .settings-btn,
            .preloader,
            .footer-area,
            footer,
            nav,
            .nav,
            .breadcrumb,
            .page-header,
            .card-header,
            .btn,
            .btn-sm,
            .btn-primary,
            .d-flex.justify-content-between,
            h4.mb-0,
            .src-form,
            .header-burger-menu,
            .sidebar-burger-menu,
            .sidebar-burger-menu-close,
            .container-fluid > .row:first-child,
            .summary-cards,
            .row.mb-3:first-child {
                display: none !important;
                visibility: hidden !important;
                width: 0 !important;
                height: 0 !important;
                overflow: hidden !important;
                position: absolute !important;
                left: -9999px !important;
                opacity: 0 !important;
            }
        }
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h3 {
            margin: 0;
            color: #003471;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th,
        table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h3>Admission Forms Report</h3>
        <p class="mb-0">Date: {{ now()->format('d-m-Y') }}</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">Sr.</th>
                <th style="width: 15%;">Student Code</th>
                <th style="width: 20%;">Student Name</th>
                <th style="width: 15%;">Parent Name</th>
                <th style="width: 10%;">Class</th>
                <th style="width: 10%;">Section</th>
                <th style="width: 15%;">Campus</th>
                <th style="width: 10%;">Admission Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $index => $student)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $student->student_code ?? 'N/A' }}</td>
                <td>{{ $student->student_name }}</td>
                <td>{{ $student->father_name ?? 'N/A' }}</td>
                <td>{{ $student->class ?? 'N/A' }}</td>
                <td>{{ $student->section ?? 'N/A' }}</td>
                <td>{{ $student->campus ?? 'N/A' }}</td>
                <td>{{ $student->admission_date ? \Carbon\Carbon::parse($student->admission_date)->format('d-m-Y') : 'N/A' }}</td>
            </tr>
            @empty
            <tr>
                <td colspan="8" class="text-center">No admission forms found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>

    <div style="margin-top: 20px;">
        <p><strong>Total Admission Forms:</strong> {{ $students->count() }}</p>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
@else
@extends('layouts.app')

@section('title', 'Admission Forms Report')

@section('content')
<!-- Print Header (only visible when printing) -->
<div class="print-header d-none">
    <h3 style="margin: 0; color: #003471;">Admission Forms Report</h3>
    <p style="margin: 5px 0 0 0;">Date: {{ now()->format('d-m-Y') }}</p>
</div>

<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Admission Forms Report</h4>
                <a href="{{ route('admission.report.forms', array_merge(request()->all(), ['print' => 1])) }}" target="_blank" class="btn btn-sm btn-primary">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                    Print
                </a>
            </div>

            <!-- Filters -->
            <form method="GET" action="{{ route('admission.report.forms') }}" class="mb-3">
                <div class="row g-3">
                    <div class="col-md-3">
                        <label class="form-label">Campus</label>
                        <select name="filter_campus" class="form-select">
                            <option value="">All Campuses</option>
                            @foreach($campuses as $campus)
                                <option value="{{ $campus }}" {{ $filterCampus == $campus ? 'selected' : '' }}>{{ $campus }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Class</label>
                        <select name="filter_class" class="form-select">
                            <option value="">All Classes</option>
                            @foreach($classes as $class)
                                <option value="{{ $class }}" {{ $filterClass == $class ? 'selected' : '' }}>{{ $class }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3">
                        <label class="form-label">Section</label>
                        <select name="filter_section" class="form-select">
                            <option value="">All Sections</option>
                            @foreach($sections as $section)
                                <option value="{{ $section }}" {{ $filterSection == $section ? 'selected' : '' }}>{{ $section }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-md-3 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">Filter</button>
                    </div>
                </div>
            </form>

            <div class="default-table-area">
                <div class="table-responsive">
                    <table class="table">
                        <thead>
                            <tr>
                                <th>Sr.</th>
                                <th>Student Code</th>
                                <th>Student Name</th>
                                <th>Parent Name</th>
                                <th>Class</th>
                                <th>Section</th>
                                <th>Campus</th>
                                <th>Admission Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @forelse($students as $index => $student)
                            <tr>
                                <td>{{ $index + 1 }}</td>
                                <td>{{ $student->student_code ?? 'N/A' }}</td>
                                <td><strong class="text-primary">{{ $student->student_name }}</strong></td>
                                <td>{{ $student->father_name ?? 'N/A' }}</td>
                                <td>{{ $student->class ?? 'N/A' }}</td>
                                <td>{{ $student->section ?? 'N/A' }}</td>
                                <td>{{ $student->campus ?? 'N/A' }}</td>
                                <td>{{ $student->admission_date ? \Carbon\Carbon::parse($student->admission_date)->format('d-m-Y') : 'N/A' }}</td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="d-flex flex-column align-items-center">
                                        <span class="material-symbols-outlined text-muted" style="font-size: 48px;">inbox</span>
                                        <p class="text-muted mt-2 mb-0">No admission forms found.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>

            <div class="mt-3">
                <p class="mb-0"><strong>Total Admission Forms:</strong> {{ $students->count() }}</p>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    /* Hide sidebar and all navigation elements - comprehensive list */
    .sidebar-area,
    #sidebar-area,
    .sidebar,
    aside,
    #layout-menu,
    .layout-menu,
    .menu-vertical,
    .menu,
    .navbar,
    .navbar-area,
    .header-area,
    #header-area,
    header,
    .main-header,
    .header-navbar,
    .left-header-content,
    .right-header-content,
    .theme-settings-area,
    .theme-settings,
    .settings-btn,
    .preloader,
    .footer-area,
    footer,
    .btn,
    .btn-sm,
    .btn-primary,
    .d-flex.justify-content-between,
    h4.mb-0,
    .src-form,
    .header-burger-menu,
    .sidebar-burger-menu,
    .sidebar-burger-menu-close,
    form,
    .form-select,
    .form-label,
    .row.g-3,
    .logo,
    .header-right-item,
    .admin-profile,
    .notifications,
    .dropdown,
    .dropdown-menu,
    .header-burger-menu,
    #header-burger-menu,
    .sidebar-burger-menu,
    #sidebar-burger-menu,
    .sidebar-burger-menu-close,
    #sidebar-burger-menu-close {
        display: none !important;
        visibility: hidden !important;
        width: 0 !important;
        height: 0 !important;
        overflow: hidden !important;
        position: absolute !important;
        left: -9999px !important;
        opacity: 0 !important;
    }
    
    /* Reset body and container */
    body {
        background: white !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    
    /* Hide container padding and margins */
    .container-fluid {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
        margin-left: 0 !important;
    }
    
    /* Force hide sidebar and header completely */
    body > .sidebar-area,
    body > #sidebar-area,
    body > aside,
    body > header,
    body > .header-area,
    body > #header-area {
        display: none !important;
        visibility: hidden !important;
        width: 0 !important;
        height: 0 !important;
        overflow: hidden !important;
        position: absolute !important;
        left: -9999px !important;
        opacity: 0 !important;
    }
    
    .main-content,
    .main-content-container {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        margin-left: 0 !important;
        max-width: 100% !important;
    }
    
    .row {
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .col-12 {
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }
    
    /* Card styles */
    .card {
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
        margin: 0 !important;
        background: white !important;
    }
    
    /* Hide card header with buttons */
    .card > .d-flex.justify-content-between {
        display: none !important;
    }
    
    /* Table styles */
    .default-table-area {
        border: none !important;
        margin: 0 !important;
        padding: 0 !important;
        background: white !important;
    }
    
    .table-responsive {
        overflow: visible !important;
    }
    
    .table {
        width: 100% !important;
        border-collapse: collapse !important;
        margin: 0 !important;
    }
    
    .table th,
    .table td {
        border: 1px solid #000 !important;
        padding: 8px !important;
    }
    
    /* Print header - show when printing */
    .print-header {
        display: block !important;
        text-align: center;
        margin-bottom: 20px;
        page-break-after: avoid;
    }
    
    .print-header.d-none {
        display: block !important;
    }
    
    /* Ensure full page width */
    @page {
        margin: 1cm;
    }
}
</style>
@endsection
@endif
