<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Staff Attendance Overview</title>
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

        .attendance-report {
            page-break-after: always;
            margin-bottom: 24px;
            padding: 0;
            border: none;
            background: transparent;
        }
        
        .attendance-report:last-child {
            page-break-after: auto;
        }
        
        .report-header {
            text-align: center;
            margin-bottom: 16px;
        }
        
        .report-title {
            font-size: 18px;
            font-weight: bold;
            margin-bottom: 10px;
        }
        
        .teacher-info {
            display: flex;
            justify-content: space-between;
            margin-bottom: 16px;
            font-size: 13px;
            flex-wrap: wrap;
            gap: 10px;
        }
        
        .attendance-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 16px;
            font-size: 11px;
            background: white;
        }
        
        .attendance-table th,
        .attendance-table td {
            border: 1px solid #ddd;
            padding: 4px;
            text-align: center;
        }
        
        .attendance-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        
        .summary-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
            background: white;
        }
        
        .summary-table th,
        .summary-table td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: center;
        }
        
        .summary-table th {
            background-color: #f5f5f5;
            font-weight: bold;
        }
        
        .no-print {
            margin-bottom: 16px;
            padding: 12px 16px;
            background: #f8f9fa;
            border-radius: 8px;
            border: 1px solid #e9ecef;
        }

        .card {
            border: 1px solid #e9ecef;
            border-radius: 10px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.06);
        }

        .card-header {
            background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
            color: white;
            border-bottom: none;
        }

        .card-title {
            font-size: 15px;
            font-weight: 600;
            margin: 0;
        }

        .info-card {
            background: #f8f9fa;
            border: 1px solid #e9ecef;
            border-radius: 8px;
            padding: 8px 12px;
        }

        .section-title {
            font-size: 14px;
            font-weight: 600;
            color: #003471;
            margin-bottom: 10px;
        }
        
        @media print {
            .no-print {
                display: none;
            }
            
            body {
                padding: 0;
            }

            .card {
                box-shadow: none;
                border: 1px solid #ddd;
            }
        }
    </style>
</head>
<body>
    <div class="container-fluid">
        <div class="no-print">
            <div class="d-flex justify-content-between align-items-center">
                <h4 class="mb-0">Staff Attendance Overview - {{ $year }}</h4>
                <div class="d-flex gap-2">
                    <form method="GET" action="{{ route('staff.attendance.overview') }}" class="d-inline">
                        <select name="year" class="form-select form-select-sm" onchange="this.form.submit()" style="width: auto;">
                            @foreach($years as $y)
                                <option value="{{ $y }}" {{ $year == $y ? 'selected' : '' }}>{{ $y }}</option>
                            @endforeach
                        </select>
                    </form>
                    <button onclick="window.print()" class="btn btn-sm btn-primary">
                        <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                        Print
                    </button>
                </div>
            </div>
        </div>

        @foreach($staffReports as $report)
        <div class="attendance-report">
            <div class="card">
                <div class="card-header">
                    <div class="d-flex align-items-center justify-content-between flex-wrap gap-2">
                        <h5 class="card-title">Staff Attendance Overview | {{ $report['staff']->name }} | {{ $year }}</h5>
                        <span class="small">EMP Code: {{ $report['staff']->emp_id ?? 'N/A' }}</span>
                    </div>
                </div>
                <div class="card-body">
                    <div class="teacher-info">
                        <div class="info-card">
                            <strong>Campus:</strong> {{ $report['staff']->campus ?? 'N/A' }}
                        </div>
                        <div class="info-card">
                            <strong>Name:</strong> {{ $report['staff']->name }}
                        </div>
                        <div class="info-card">
                            <strong>Father/Husband:</strong> {{ $report['staff']->father_husband_name ?? 'N/A' }}
                        </div>
                        <div class="info-card">
                            <strong>EMP Code:</strong> {{ $report['staff']->emp_id ?? 'N/A' }}
                        </div>
                    </div>

                    <div class="section-title">Daily Attendance</div>
                    <div class="table-responsive">
                        <table class="attendance-table">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    @for($day = 1; $day <= 31; $day++)
                                    <th style="min-width: 25px;">{{ $day }}</th>
                                    @endfor
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($monthNames as $monthNum => $monthName)
                                <tr>
                                    <td><strong>{{ $monthName }}</strong></td>
                                    @php
                                        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, (int)$monthNum, (int)$year);
                                        $monthData = $report['daily_attendance'][$monthNum]['days'] ?? [];
                                    @endphp
                                    @for($day = 1; $day <= 31; $day++)
                                    <td>
                                        @if($day <= $daysInMonth)
                                            {{ $monthData[$day] ?? '--' }}
                                        @else
                                            --
                                        @endif
                                    </td>
                                    @endfor
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>

                    <div class="section-title mt-4">Summarized Attendance Report - {{ $year }}</div>
                    <div class="table-responsive">
                        <table class="summary-table">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    <th>Presents</th>
                                    <th>Absentes</th>
                                    <th>Leaves</th>
                                    <th>Holidays</th>
                                    <th>Sundays</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($report['monthly_summary'] as $summary)
                                <tr>
                                    <td><strong>{{ $summary['month_name'] }}</strong></td>
                                    <td>{{ $summary['present'] }}</td>
                                    <td>{{ $summary['absent'] }}</td>
                                    <td>{{ $summary['leave'] }}</td>
                                    <td>{{ $summary['holiday'] }}</td>
                                    <td>{{ $summary['sunday'] }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        @endforeach
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
