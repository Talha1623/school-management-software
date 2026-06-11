<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff Salary Report</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 10px; margin: 14px; color: #111827; }
        .report-wrap { max-width: 1400px; margin: 0 auto; }
        .header-block { text-align: center; margin-bottom: 4px; }
        .header-logo-wrap { margin-bottom: 6px; }
        .header-logo { width: 100px; height: 100px; object-fit: contain; background: #fff; }
        .heading { text-align: center; color: #003471; margin: 0; font-size: 28px; font-weight: 700; }
        .subheading { text-align: center; margin: 2px 0 0; font-size: 18px; font-weight: 700; color: #0f172a; }
        .school-meta { text-align: center; margin-top: 2px; font-size: 11px; color: #374151; }
        .school-contact-line { text-align: center; margin-top: 4px; font-size: 11px; color: #1f2937; }
        .meta { text-align: center; margin: 4px 0 10px; font-size: 11px; color: #4b5563; }
        .rule { border-top: 2px solid #003471; margin: 8px 0; }
        .actions { display: flex; justify-content: flex-end; margin-bottom: 8px; }
        .print-btn { padding: 4px 12px; font-size: 12px; cursor: pointer; background: #003471; color: #fff; border: 1px solid #003471; border-radius: 2px; }
        .print-btn:hover { background: #0b4a89; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #9ca3af; padding: 4px 5px; text-align: left; vertical-align: top; word-wrap: break-word; font-size: 9px; }
        th { background: #003471; color: #fff; font-weight: 700; }
        .right { text-align: right; }
        tfoot td { font-weight: 700; background: #f3f4f6; }
        .footer-row { margin-top: 8px; font-size: 11px; color: #374151; }
        @media print {
            .print-btn { display: none; }
            body { margin: 8px; }
            @page { size: landscape; margin: 8mm; }
        }
    </style>
</head>
<body @if(request()->boolean('auto_print')) onload="window.print()" @endif>
    @php
        $printedAt = now()->format('d-m-Y H:i');
        $recordCount = $salaryRecords->count();
    @endphp

    <div class="report-wrap">
        <div class="header-block">
            @if(!empty($schoolLogoUrl))
                <div class="header-logo-wrap">
                    <img src="{{ $schoolLogoUrl }}" alt="School Logo" class="header-logo">
                </div>
            @endif
            <h2 class="heading">{{ $schoolName ?? 'Education Management System' }}</h2>
            @if(!empty($schoolAddress))
                <div class="school-meta">{{ $schoolAddress }}</div>
            @endif
            @if(!empty($schoolPhone) || !empty($schoolEmail))
                <div class="school-contact-line">
                    @if(!empty($schoolPhone)){{ $schoolPhone }}@endif
                    @if(!empty($schoolPhone) && !empty($schoolEmail)) &nbsp;|&nbsp; @endif
                    @if(!empty($schoolEmail)){{ $schoolEmail }}@endif
                </div>
            @endif
            <div class="subheading">STAFF SALARY REPORT</div>
        </div>
        <div class="meta">
            Filters: {{ $filterDescription ?? '—' }}
            &nbsp;||&nbsp; Generated: {{ $printedAt }}
            &nbsp;||&nbsp; Records: {{ $recordCount }}
        </div>
        <div class="rule"></div>

        <div class="actions">
            <button type="button" class="print-btn" onclick="window.print()">Print</button>
        </div>

        <table>
            <thead>
                <tr>
                    <th style="width:3%;">#</th>
                    <th style="width:11%;">Staff Name</th>
                    <th style="width:7%;">Emp ID</th>
                    <th style="width:10%;">Campus</th>
                    <th style="width:9%;">Designation</th>
                    <th style="width:8%;">Month</th>
                    <th style="width:5%;">Year</th>
                    <th style="width:4%;">Pr.</th>
                    <th style="width:4%;">Ab.</th>
                    <th style="width:4%;">Late</th>
                    <th style="width:4%;">Exit</th>
                    <th style="width:7%;">Basic</th>
                    <th style="width:8%;">Generated</th>
                    <th style="width:7%;">Paid</th>
                    <th style="width:7%;">Loan</th>
                    <th style="width:9%;">Status</th>
                </tr>
            </thead>
            <tbody>
                @forelse($salaryRecords as $index => $record)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $record['staff_name'] }}</td>
                        <td>{{ $record['emp_id'] }}</td>
                        <td>{{ $record['campus'] }}</td>
                        <td>{{ $record['designation'] }}</td>
                        <td>{{ $record['salary_month'] }}</td>
                        <td>{{ $record['year'] }}</td>
                        <td>{{ $record['present'] }}</td>
                        <td>{{ $record['absent'] }}</td>
                        <td>{{ $record['late'] }}</td>
                        <td>{{ $record['early_exit'] }}</td>
                        <td class="right">{{ $record['basic'] }}</td>
                        <td class="right">{{ $record['salary_generated'] }}</td>
                        <td class="right">{{ $record['amount_paid'] }}</td>
                        <td class="right">{{ $record['loan_repayment'] }}</td>
                        <td>{{ $record['status'] }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="15" style="text-align:center;">No salary records found for the selected filters.</td>
                    </tr>
                @endforelse
            </tbody>
            @if($salaryRecords->isNotEmpty())
            <tfoot>
                <tr>
                    <td colspan="11" class="right">TOTAL</td>
                    <td class="right">{{ number_format($totals['basic'] ?? 0, 2) }}</td>
                    <td class="right">{{ number_format($totals['salary_generated'] ?? 0, 2) }}</td>
                    <td class="right">{{ number_format($totals['amount_paid'] ?? 0, 2) }}</td>
                    <td class="right">{{ number_format($totals['loan_repayment'] ?? 0, 2) }}</td>
                    <td></td>
                </tr>
            </tfoot>
            @endif
        </table>

        <div class="footer-row">System Generated Report</div>
    </div>
</body>
</html>
