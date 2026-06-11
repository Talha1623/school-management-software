<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Detailed Expense Report</title>
    <style>
        @page { size: A4 landscape; margin: 8mm; }
        body { font-family: DejaVu Sans, sans-serif; color: #111827; font-size: 9px; }
        .header-section { border-bottom: 2px solid #003471; padding-bottom: 8px; text-align: center; }
        .school-name { font-size: 16px; font-weight: 700; color: #003471; }
        .school-details { font-size: 10px; color: #374151; margin-top: 3px; }
        .report-title { font-size: 13px; font-weight: 700; color: #003471; margin-top: 6px; text-transform: uppercase; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #003471; padding: 4px; }
        th { background: #003471; color: #fff; text-align: left; font-size: 8px; }
        .text-end { text-align: right; }
        tfoot td { font-weight: 700; background: #f8f9fa; }
        .summary { margin-top: 8px; font-size: 9px; }
        .footer-section { border-top: 2px solid #003471; margin-top: 10px; padding-top: 6px; font-size: 10px; }
    </style>
</head>
<body>
    <div class="header-section">
        <div class="school-name">{{ $settings->school_name ?? 'School Name' }}</div>
        <div class="school-details">
            {{ $settings->address ?? '' }}
            @if(!empty($settings->school_phone)) | {{ $settings->school_phone }} @endif
        </div>
        <div class="report-title">Detailed Expense Report</div>
        <div class="school-details">Generated: {{ $printedAt }}</div>
        <div class="school-details">
            Campus: {{ $filterCampus ?: 'All' }} |
            Month: {{ $filterMonth ? ($months[$filterMonth] ?? $filterMonth) : 'All' }} |
            Year: {{ $filterYear ?: 'All' }} |
            Method: {{ $filterMethod ?: 'All' }}
        </div>
    </div>

    <table>
        <thead>
        <tr>
            <th>#</th>
            <th>Title</th>
            <th>Categories</th>
            <th>Accountant</th>
            <th class="text-end">Amount</th>
            <th>Date & Time</th>
            <th>Description</th>
            <th>Method</th>
        </tr>
        </thead>
        <tbody>
        @foreach($expenseRecords as $index => $record)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $record['title'] ?? 'N/A' }}</td>
                <td>{{ $record['category'] ?? 'N/A' }}</td>
                <td>{{ $record['accountant'] ?? 'N/A' }}</td>
                <td class="text-end">{{ number_format($record['amount'] ?? 0, 2) }}</td>
                <td>{{ !empty($record['date']) ? \Carbon\Carbon::parse($record['date'])->format('d-m-Y H:i') : 'N/A' }}</td>
                <td>{{ \Illuminate\Support\Str::limit($record['description'] ?? 'N/A', 40) }}</td>
                <td>{{ $record['method'] ?? 'N/A' }}</td>
            </tr>
        @endforeach
        </tbody>
        @if($expenseRecords->count() > 0)
        <tfoot>
            <tr>
                <td colspan="4" class="text-end">Total:</td>
                <td class="text-end">{{ number_format($expenseRecords->sum('amount'), 2) }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="footer-section">
        Total Records: {{ $expenseRecords->count() }} |
        Total Amount: {{ number_format($expenseRecords->sum('amount'), 2) }}
    </div>
</body>
</html>
