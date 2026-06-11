<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounts Summary Report Print</title>
    <style>
        @page { size: A4; margin: 10mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #fff; color: #111827; }
        .print-container { width: 210mm; margin: 0 auto; }
        :root { --theme-blue: #003471; }
        .header-section { border-bottom: 3px solid var(--theme-blue); padding-bottom: 10px; text-align: center; }
        .school-name { font-size: 20px; font-weight: 800; color: var(--theme-blue); }
        .school-details { font-size: 12px; color: #374151; margin-top: 4px; }
        .report-title { font-size: 16px; font-weight: 800; color: var(--theme-blue); margin-top: 8px; text-transform: uppercase; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin: 10px 0; font-size: 12px; color: #374151; }
        .print-btn { border: 1px solid var(--theme-blue); background: var(--theme-blue); color: #fff; padding: 6px 12px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 11px; }
        th, td { border: 1px solid var(--theme-blue); padding: 6px; vertical-align: top; }
        th { background: var(--theme-blue); color: #fff; text-align: left; }
        .text-end { text-align: right; }
        .footer-section { border-top: 2px solid var(--theme-blue); margin-top: 14px; padding-top: 10px; display: flex; justify-content: space-between; font-size: 12px; }
        .no-print { text-align: right; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
<div class="print-container">
    <div class="header-section">
        <div class="school-name">{{ $settings->school_name ?? 'School Name' }}</div>
        <div class="school-details">
            {{ $settings->address ?? '' }}
            @if(!empty($settings->school_phone)) | {{ $settings->school_phone }} @endif
            @if(!empty($settings->school_email)) | {{ $settings->school_email }} @endif
        </div>
        <div class="report-title">Accounts Summary Report</div>
        <div class="school-details">
            Generated: {{ $printedAt }}
            @if($filterCampus) | Campus: {{ $filterCampus }} @endif
            @if($filterMonth) | Month: {{ $months[$filterMonth] ?? $filterMonth }} @endif
            @if($filterYear) | Year: {{ $filterYear }} @endif
            | Type: {{ ($filterType ?? 'day_by_day') === 'month_by_month' ? 'Month By Month' : 'Day By Day' }}
        </div>
    </div>
    <div class="top-bar">
        <div></div>
        <div class="no-print"><button class="print-btn" onclick="window.print()">Print</button></div>
    </div>
    <table>
        <thead>
        <tr>
            <th>#</th>
            <th>Campus</th>
            <th>Month</th>
            @if(($filterType ?? 'day_by_day') === 'day_by_day')
            <th>Date</th>
            @endif
            <th>Cash Income</th>
            <th>Discount</th>
            <th>Total Expense</th>
            <th>Profit/Lose</th>
            <th>Year</th>
        </tr>
        </thead>
        <tbody>
        @forelse($summaryRecords as $index => $record)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $record['campus'] }}</td>
                <td>{{ $record['month'] }}</td>
                @if(($filterType ?? 'day_by_day') === 'day_by_day')
                <td>{{ $record['date'] ?? 'N/A' }}</td>
                @endif
                <td class="text-end">{{ number_format($record['total_income'] ?? 0, 2) }}</td>
                <td class="text-end">{{ number_format($record['total_discount'] ?? 0, 2) }}</td>
                <td class="text-end">{{ number_format($record['total_expense'] ?? 0, 2) }}</td>
                <td class="text-end">{{ number_format($record['profit_loss'] ?? 0, 2) }}</td>
                <td>{{ $record['year'] }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="{{ ($filterType ?? 'day_by_day') === 'day_by_day' ? 8 : 7 }}" style="text-align:center;color:#6b7280;">No records found.</td>
            </tr>
        @endforelse
        </tbody>
    </table>
    <div class="footer-section">
        <div><strong>Total Records:</strong> {{ $summaryRecords->count() }}</div>
        <div>System Generated Report</div>
    </div>
</div>
@if(request()->get('auto_print'))
<script>
window.addEventListener('load', function () { setTimeout(function () { window.print(); }, 300); });
</script>
@endif
</body>
</html>
