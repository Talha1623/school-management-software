<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Summarized Salary & Attendance Report</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@20..48,100..700,0..1,-50..200" rel="stylesheet">
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            background: white;
            padding: 20px;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
        }

        .report-card {
            background: white;
            padding: 30px;
            border-radius: 8px;
            margin-bottom: 30px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.1);
            page-break-after: always;
        }

        .report-header {
            border-bottom: 2px solid #003471;
            padding-bottom: 15px;
            margin-bottom: 20px;
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
            padding: 10px;
            text-align: center;
            font-weight: 600;
            border: 1px solid rgba(255, 255, 255, 0.3);
            font-size: 12px;
        }

        .table td {
            border: 1px solid #dee2e6;
            padding: 8px;
            vertical-align: middle;
            font-size: 12px;
        }

        .table tbody tr:nth-child(even) {
            background-color: #f8f9fa;
        }

        .table tbody tr:hover {
            background-color: #e9ecef;
        }

        .report-footer {
            margin-top: 30px;
            display: flex;
            justify-content: space-between;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }

            .report-card {
                page-break-after: always;
                page-break-inside: avoid;
                box-shadow: none;
            }

            .report-card:last-child {
                page-break-after: auto;
            }
        }
    </style>
</head>
<body>
    <!-- Reports Section -->
    @if($staffReports->count() > 0)
        @foreach($staffReports as $report)
            @php
                $staff = $report['staff'];
                $monthlyData = $report['monthly_data'];
            @endphp
            <div class="report-card">
                <!-- Header -->
                <div class="report-header">
                    <div style="display: flex; justify-content: space-between; align-items: start; margin-bottom: 15px;">
                        <div style="display: flex; align-items: center; gap: 15px;">
                            <div>
                                <img src="{{ asset('assets/images/logo-icon.png') }}" alt="Logo" style="width: 50px; height: 50px; object-fit: contain;" onerror="this.style.display='none'">
                            </div>
                            <div>
                                <h3 style="margin: 0; color: #003471; font-size: 24px; font-weight: bold;">{{ config('app.name', 'Royal Grammar School') }}</h3>
                                @if(config('app.contact'))
                                <p style="margin: 5px 0 0 0; color: #666; font-size: 14px;">{{ config('app.contact') }}</p>
                                @endif
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <img src="{{ asset('assets/images/logo-icon.png') }}" alt="Logo" style="width: 60px; height: 60px; object-fit: contain; opacity: 0.1;" onerror="this.style.display='none'">
                        </div>
                    </div>
                    <div style="text-align: center;">
                        <h4 style="margin: 0; color: #003471; font-size: 18px; font-weight: bold;">Summarized Salary & Attendance Report - {{ $filterYear }}</h4>
                    </div>
                </div>

                <!-- Employee Information -->
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

                <!-- Data Table -->
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
                                <td style="text-align: center; font-weight: 500;">{{ $month['month'] }}</td>
                                <td style="text-align: center;">{{ $month['present'] }}</td>
                                <td style="text-align: center;">{{ $month['absent'] }}</td>
                                <td style="text-align: center;">{{ $month['late'] }}</td>
                                <td style="text-align: center;">{{ $month['leaves'] }}</td>
                                <td style="text-align: center;">{{ $month['holidays'] }}</td>
                                <td style="text-align: center;">{{ $month['sundays'] }}</td>
                                <td style="text-align: right;">{{ number_format($month['basic_salary'], 2) }}</td>
                                <td style="text-align: right;">{{ number_format($month['salary_generated'], 2) }}</td>
                                <td style="text-align: right;">{{ number_format($month['amount_paid'], 2) }}</td>
                                <td style="text-align: right;">{{ number_format($month['loan_repayment'], 2) }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Footer -->
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
        <div style="background: white; padding: 40px; border-radius: 8px; text-align: center; box-shadow: 0 2px 4px rgba(0,0,0,0.1);">
            <span class="material-symbols-outlined" style="font-size: 64px; color: #999;">inbox</span>
            <p style="color: #666; margin-top: 15px; font-size: 16px;">No staff found matching the selected filters</p>
        </div>
    @endif

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
