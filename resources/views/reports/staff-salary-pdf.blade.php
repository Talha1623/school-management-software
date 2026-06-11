<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Staff Salary Report PDF</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 9px; color: #111827; }
        .header { text-align: center; margin-bottom: 8px; }
        .logo { width: 64px; height: 64px; object-fit: contain; margin: 0 auto 4px auto; display: block; }
        .school { font-size: 18px; font-weight: 700; color: #003471; margin: 0; }
        .meta { margin: 2px 0; color: #374151; font-size: 9px; }
        .title { margin: 4px 0 2px; font-size: 14px; font-weight: 700; }
        .line { border-top: 1px solid #003471; margin: 6px 0; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #9ca3af; padding: 4px; text-align: left; word-wrap: break-word; }
        th { background: #003471; color: #fff; font-size: 8px; }
        .right { text-align: right; }
        .footer { margin-top: 6px; font-size: 9px; }
        tfoot td { font-weight: 700; background: #f3f4f6; }
    </style>
</head>
<body>
    <div class="header">
        @if(!empty($schoolLogoUrl))
            <img src="{{ $schoolLogoUrl }}" class="logo" alt="School Logo">
        @endif
        <p class="school">{{ $schoolName ?? 'Education Management System' }}</p>
        @if(!empty($schoolAddress))
            <p class="meta">{{ $schoolAddress }}</p>
        @endif
        @if(!empty($schoolPhone) || !empty($schoolEmail))
            <p class="meta">
                @if(!empty($schoolPhone)){{ $schoolPhone }}@endif
                @if(!empty($schoolPhone) && !empty($schoolEmail)) | @endif
                @if(!empty($schoolEmail)){{ $schoolEmail }}@endif
            </p>
        @endif
        <p class="title">STAFF SALARY REPORT</p>
        <p class="meta">Filters: {{ $filterDescription ?? '—' }}</p>
        <p class="meta">Generated: {{ $printedAt ?? now()->format('d-m-Y H:i') }} | Records: {{ $rows->count() }}</p>
    </div>

    <div class="line"></div>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Staff</th>
                <th>Emp ID</th>
                <th>Campus</th>
                <th>Designation</th>
                <th>Month</th>
                <th>Year</th>
                <th>Pr.</th>
                <th>Ab.</th>
                <th>Late</th>
                <th>Exit</th>
                <th>Basic</th>
                <th>Generated</th>
                <th>Paid</th>
                <th>Loan</th>
                <th>Status</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $row)
                <tr>
                    <td>{{ $row['#'] }}</td>
                    <td>{{ $row['Staff Name'] }}</td>
                    <td>{{ $row['Emp ID'] }}</td>
                    <td>{{ $row['Campus'] }}</td>
                    <td>{{ $row['Designation'] }}</td>
                    <td>{{ $row['Salary Month'] }}</td>
                    <td>{{ $row['Year'] }}</td>
                    <td>{{ $row['Present'] }}</td>
                    <td>{{ $row['Absent'] }}</td>
                    <td>{{ $row['Late'] }}</td>
                    <td>{{ $row['Early Exit'] }}</td>
                    <td class="right">{{ $row['Basic'] }}</td>
                    <td class="right">{{ $row['Salary Generated'] }}</td>
                    <td class="right">{{ $row['Amount Paid'] }}</td>
                    <td class="right">{{ $row['Loan Repayment'] }}</td>
                    <td>{{ $row['Status'] }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="15" style="text-align:center;">No salary records found.</td>
                </tr>
            @endforelse
        </tbody>
        @if($rows->isNotEmpty())
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

    <div class="footer">System Generated Report</div>
</body>
</html>
