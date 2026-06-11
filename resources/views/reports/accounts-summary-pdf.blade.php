<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounts Summary Report PDF</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; color: #111827; font-size: 12px; }
        .header-section { border-bottom: 2px solid #003471; padding-bottom: 8px; text-align: center; margin-bottom: 10px; }
        .school-name { font-size: 18px; font-weight: 700; color: #003471; }
        .meta { font-size: 11px; color: #374151; margin-top: 3px; }
        .title { font-size: 14px; font-weight: 700; color: #003471; margin-top: 6px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #003471; padding: 6px; }
        th { background: #003471; color: #fff; text-align: left; }
        .text-end { text-align: right; }
    </style>
</head>
<body>
    <div class="header-section">
        <div class="school-name">{{ $settings->school_name ?? 'School Name' }}</div>
        <div class="meta">{{ $settings->address ?? '' }}</div>
        <div class="title">Accounts Summary Report</div>
        <div class="meta">
            Generated: {{ $printedAt }}
            @if($filterCampus) | Campus: {{ $filterCampus }} @endif
            @if($filterMonth) | Month: {{ $months[$filterMonth] ?? $filterMonth }} @endif
            @if($filterYear) | Year: {{ $filterYear }} @endif
            | Type: {{ ($filterType ?? 'day_by_day') === 'month_by_month' ? 'Month By Month' : 'Day By Day' }}
        </div>
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
                <td colspan="{{ ($filterType ?? 'day_by_day') === 'day_by_day' ? 9 : 8 }}" style="text-align:center;color:#6b7280;">No records found.</td>
            </tr>
            @endforelse
        </tbody>
    </table>
</body>
</html>
