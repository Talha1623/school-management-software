<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Detailed Income Report</title>
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
        <div class="report-title">Detailed Income Report</div>
        <div class="school-details">Generated: {{ $printedAt }}</div>
        <div class="school-details">
            Campus: {{ $filterCampus ?: 'All' }} |
            Class: {{ $filterClass ?: 'All' }} |
            Section: {{ $filterSection ?: 'All' }} |
            Month: {{ $filterMonth ? ($months[$filterMonth] ?? $filterMonth) : 'All' }} |
            Year: {{ $filterYear ?: 'All' }}
        </div>
    </div>

    <table>
        <thead>
        <tr>
            <th>#</th>
            <th>Code</th>
            <th>Student</th>
            <th>Parent</th>
            <th>Class</th>
            <th>Title</th>
            <th class="text-end">Amount</th>
            <th class="text-end">Dis</th>
            <th>Method</th>
            <th>Received By</th>
            <th>Date/Time</th>
        </tr>
        </thead>
        <tbody>
        @foreach($incomeRecords as $index => $record)
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $record['student_code'] ?? 'N/A' }}</td>
                <td>{{ $record['student_name'] ?? 'N/A' }}</td>
                <td>{{ $record['parent_name'] ?? 'N/A' }}</td>
                <td>{{ $record['class'] ?? 'N/A' }}</td>
                <td>{{ $record['payment_title'] ?? 'N/A' }}</td>
                <td class="text-end">{{ number_format($record['payment_amount'] ?? 0, 2) }}</td>
                <td class="text-end">{{ number_format($record['discount'] ?? 0, 2) }}</td>
                <td>{{ $record['method'] ?? 'N/A' }}</td>
                <td>{{ $record['received_by'] ?? 'N/A' }}</td>
                <td>{{ !empty($record['payment_date']) ? \Carbon\Carbon::parse($record['payment_date'])->format('d-m-Y H:i') : 'N/A' }}</td>
            </tr>
        @endforeach
        </tbody>
        @if($incomeRecords->count() > 0)
        <tfoot>
            <tr>
                <td colspan="6" class="text-end">Total:</td>
                <td class="text-end">{{ number_format($incomeRecords->sum('payment_amount'), 2) }}</td>
                <td class="text-end">{{ number_format($incomeRecords->sum('discount'), 2) }}</td>
                <td colspan="3"></td>
            </tr>
        </tfoot>
        @endif
    </table>

    <div class="footer-section">
        Total Records: {{ $incomeRecords->count() }} |
        Total Amount: {{ number_format($incomeRecords->sum('payment_amount'), 2) }}
    </div>
</body>
</html>
