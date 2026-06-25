<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Salary Report (All Staff) - {{ $filterYear }}</title>
     <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: #f3f4f6;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .toolbar {
            max-width: 1400px;
            margin: 0 auto 20px;
            background: white;
            border-radius: 8px;
            padding: 16px 20px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        }

        .toolbar-title {
            color: #003471;
            font-size: 18px;
            font-weight: 700;
            margin-bottom: 12px;
        }

        .report-card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            margin: 0 auto 30px;
            max-width: 1400px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            page-break-after: always;
        }

        .report-header {
            border-bottom: 2px solid #003471;
            padding-bottom: 15px;
            margin-bottom: 20px;
        }

        .school-logo {
            width: 70px;
            height: 70px;
            object-fit: contain;
        }

        .employee-info {
            background-color: #f8f9fa;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 15px;
            margin-bottom: 20px;
        }

        .table {
            width: 100%;
            margin-bottom: 0;
            border-collapse: collapse;
        }

        .table th {
            background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
            color: white;
            padding: 10px 6px;
            text-align: center;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.3);
            font-size: 11px;
            white-space: nowrap;
        }

        .table td {
            border: 1px solid #dee2e6;
            padding: 8px 6px;
            vertical-align: middle;
            font-size: 11px;
        }

        .table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .table tfoot td {
            background: #e9ecef;
            font-weight: 700;
            color: #003471;
        }

        .report-footer {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }

        .print-btn {
            background: #003471;
            color: #fff;
            border: 1px solid #003471;
            border-radius: 6px;
            padding: 8px 16px;
            font-size: 13px;
            font-weight: 600;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .print-btn:hover {
            background: #0b4a89;
            color: #fff;
        }

        .empty-state {
            max-width: 1400px;
            margin: 0 auto;
            background: white;
            padding: 40px;
            border-radius: 8px;
            text-align: center;
            box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .toolbar,
            .no-print {
                display: none !important;
            }

            .report-card {
                page-break-after: always;
                page-break-inside: avoid;
                box-shadow: none;
                margin: 0;
                max-width: none;
                border-radius: 0;
            }

            .report-card:last-child {
                page-break-after: auto;
            }
        }
    </style>
</head>
<body @if(request()->boolean('auto_print')) onload="window.print()" @endif>
    <div class="toolbar no-print">
        <div class="toolbar-title">Staff Salary Report (All Staff)</div>
        <form method="GET" action="{{ route('reports.staff-salary-summarized') }}" class="row g-2 align-items-end">
            <div class="col-md-4">
                <label for="filter_campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                <select class="form-select form-select-sm" id="filter_campus" name="filter_campus">
                    <option value="">All Campuses</option>
                    @foreach($campuses as $campus)
                        <option value="{{ $campus }}" {{ $filterCampus == $campus ? 'selected' : '' }}>{{ $campus }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-3">
                <label for="filter_year" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Year</label>
                <select class="form-select form-select-sm" id="filter_year" name="filter_year">
                    @foreach($years as $year)
                        <option value="{{ $year }}" {{ (string) $filterYear === (string) $year ? 'selected' : '' }}>{{ $year }}</option>
                    @endforeach
                </select>
            </div>
            <div class="col-md-5 d-flex gap-2">
                <button type="submit" class="btn btn-sm btn-primary px-3">Apply Filters</button>
                <button type="button" class="print-btn" onclick="window.print()">
                    <span class="material-symbols-outlined" style="font-size: 18px;">print</span>
                    <span>Print</span>
                </button>
            </div>
        </form>
    </div>

    @if($staffReports->count() > 0)
        @foreach($staffReports as $report)
            @php
                $staff = $report['staff'];
                $monthlyData = $report['monthly_data'];
                $totals = $report['totals'];
            @endphp
            <div class="report-card">
                <div class="report-header">
                    <div class="d-flex justify-content-between align-items-start mb-3">
                        <div class="d-flex align-items-center gap-3">
                            @if(!empty($schoolLogoUrl))
                                <img src="{{ $schoolLogoUrl }}" alt="School Logo" class="school-logo">
                            @endif
                            <div>
                                <h3 class="mb-1" style="color: #003471; font-size: 22px; font-weight: bold;">{{ $schoolName }}</h3>
                                @if(!empty($schoolAddress))
                                    <p class="mb-0" style="color: #666; font-size: 13px;">{{ $schoolAddress }}</p>
                                @endif
                                @if(!empty($schoolPhone) || !empty($schoolEmail))
                                    <p class="mb-0" style="color: #666; font-size: 13px;">
                                        @if(!empty($schoolPhone)){{ $schoolPhone }}@endif
                                        @if(!empty($schoolPhone) && !empty($schoolEmail)) | @endif
                                        @if(!empty($schoolEmail)){{ $schoolEmail }}@endif
                                    </p>
                                @endif
                            </div>
                        </div>
                    </div>
                    <div class="text-center">
                        <h4 class="mb-0" style="color: #003471; font-size: 18px; font-weight: bold;">
                            Summarized Salary &amp; Attendance Report - {{ $filterYear }}
                        </h4>
                        @if($filterCampus)
                            <p class="mb-0 mt-1" style="color: #555; font-size: 13px;">Campus: {{ $filterCampus }}</p>
                        @endif
                    </div>
                </div>

                <div class="employee-info">
                    <div class="row g-2">
                        <div class="col-md-3">
                            <strong style="color: #003471;">Campus:</strong> <span>{{ $staff->campus ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-3">
                            <strong style="color: #003471;">Name:</strong> <span>{{ $staff->name }}</span>
                        </div>
                        <div class="col-md-3">
                            <strong style="color: #003471;">Father/Husband:</strong> <span>{{ $staff->father_husband_name ?? 'N/A' }}</span>
                        </div>
                        <div class="col-md-3">
                            <strong style="color: #003471;">EMP Code:</strong> <span>{{ $staff->emp_id ?? 'N/A' }}</span>
                        </div>
                    </div>
                </div>

                <div style="overflow-x: auto;">
                    <table class="table table-bordered">
                        <thead>
                            <tr>
                                <th>Month</th>
                                <th>Presents</th>
                                <th>Absentes</th>
                                <th>Late</th>
                                <th>Leaves</th>
                                <th>Holidays</th>
                                <th>Sundays</th>
                                <th>Basic Salary</th>
                                <th>Salary Generated</th>
                                <th>Amount Paid</th>
                                <th>Loan Repayment</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($monthlyData as $month)
                            <tr>
                                <td class="text-center fw-medium">{{ $month['month'] }}</td>
                                <td class="text-center">{{ $month['present'] }}</td>
                                <td class="text-center">{{ $month['absent'] }}</td>
                                <td class="text-center">{{ $month['late'] }}</td>
                                <td class="text-center">{{ $month['leaves'] }}</td>
                                <td class="text-center">{{ $month['holidays'] }}</td>
                                <td class="text-center">{{ $month['sundays'] }}</td>
                                <td class="text-end">{{ number_format($month['basic_salary'], 2) }}</td>
                                <td class="text-end">{{ number_format($month['salary_generated'], 2) }}</td>
                                <td class="text-end">{{ number_format($month['amount_paid'], 2) }}</td>
                                <td class="text-end">{{ number_format($month['loan_repayment'], 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                        <tfoot>
                            <tr>
                                <td class="text-center">Total</td>
                                <td class="text-center">{{ $totals['present'] }}</td>
                                <td class="text-center">{{ $totals['absent'] }}</td>
                                <td class="text-center">{{ $totals['late'] }}</td>
                                <td class="text-center">{{ $totals['leaves'] }}</td>
                                <td class="text-center">{{ $totals['holidays'] }}</td>
                                <td class="text-center">{{ $totals['sundays'] }}</td>
                                <td class="text-end">{{ number_format($totals['basic_salary'], 2) }}</td>
                                <td class="text-end">{{ number_format($totals['salary_generated'], 2) }}</td>
                                <td class="text-end">{{ number_format($totals['amount_paid'], 2) }}</td>
                                <td class="text-end">{{ number_format($totals['loan_repayment'], 2) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="report-footer">
                    <div style="width: 200px;">
                        <p style="margin: 0; font-weight: 500; color: #003471;">INCHARGE :- ________</p>
                    </div>
                    <div style="width: 200px; text-align: right;">
                        <p style="margin: 0; font-weight: 500; color: #003471;">PRINCIPAL:- ________</p>
                    </div>
                </div>
            </div>
        @endforeach
    @else
        <div class="empty-state">
            <span class="material-symbols-outlined" style="font-size: 64px; color: #999;">inbox</span>
            <p style="color: #666; margin-top: 15px; font-size: 16px;">No staff found matching the selected filters</p>
        </div>
    @endif
</body>
</html>
