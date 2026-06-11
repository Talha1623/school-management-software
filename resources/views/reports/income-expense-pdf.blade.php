<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Income & Expense Report</title>
    <style>
        @page { size: A4; margin: 10mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 11px; }
        .header-section { border-bottom: 2px solid #003471; padding-bottom: 8px; text-align: center; }
        .school-name { font-size: 18px; font-weight: 700; color: #003471; }
        .school-details { font-size: 11px; color: #374151; margin-top: 3px; }
        .report-title { font-size: 14px; font-weight: 700; color: #003471; margin-top: 6px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #003471; padding: 6px; }
        th { background: #003471; color: #fff; text-align: left; font-size: 10px; }
        .text-end { text-align: right; }
        .footer-section { border-top: 2px solid #003471; margin-top: 12px; padding-top: 8px; display: table; width: 100%; }
        .footer-left { display: table-cell; text-align: left; }
        .footer-right { display: table-cell; text-align: right; }
    </style>
</head>
<body>
    <div class="header-section">
        <div class="school-name">{{ $settings->school_name ?? 'School Name' }}</div>
        <div class="school-details">
            {{ $settings->address ?? '' }}
            @if(!empty($settings->school_phone)) | {{ $settings->school_phone }} @endif
            @if(!empty($settings->school_email)) | {{ $settings->school_email }} @endif
        </div>
        <div class="report-title">Income & Expense Report</div>
        <div class="school-details">
            Generated: {{ $printedAt }}
            @if(!empty($filters['campus'])) | Campus: {{ $filters['campus'] }} @endif
            @if(!empty($filters['user_type'])) | User Type: {{ $filters['user_type'] }} @endif
            @if(!empty($filters['user'])) | User: {{ $filters['user'] }} @endif
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th><th>Type</th><th>Source</th><th>User</th><th>Campus</th><th>Title</th><th>Status</th><th>Paid</th><th>Disc.</th><th>Due</th><th>Date</th><th>Method</th>
            </tr>
        </thead>
        <tbody>
            @forelse($incomeRecords as $i => $record)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $record['type'] }}</td>
                    <td>{{ $record['source'] }}</td>
                    <td>{{ $record['user'] }}</td>
                    <td>{{ $record['campus'] ?? 'N/A' }}</td>
                    <td>{{ $record['title'] }}</td>
                    <td>{{ $record['status'] ?? '' }}</td>
                    <td class="text-end">{{ number_format($record['amount'] ?? 0, 2) }}</td>
                    <td class="text-end">{{ number_format($record['discount'] ?? 0, 2) }}</td>
                    <td class="text-end">{{ number_format($record['remaining'] ?? 0, 2) }}</td>
                    <td>{{ !empty($record['date']) ? date('d M Y', strtotime($record['date'])) : 'N/A' }}</td>
                    <td>{{ $record['method'] }}</td>
                </tr>
            @empty
                <tr><td colspan="12" style="text-align:center;">No records found.</td></tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-section">
        <div class="footer-left"><strong>Total:</strong> {{ $incomeRecords->count() }}</div>
        <div class="footer-right">System Generated Report</div>
    </div>
</body>
</html>
