<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detailed Income Report Print</title>
    <style>
        @page { size: A4 landscape; margin: 10mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #fff; color: #111827; }
        .print-container { width: 100%; margin: 0 auto; }
        :root { --theme-blue: #003471; }
        .header-section { border-bottom: 3px solid var(--theme-blue); padding-bottom: 10px; text-align: center; }
        .school-name { font-size: 20px; font-weight: 800; color: var(--theme-blue); }
        .school-details { font-size: 12px; color: #374151; margin-top: 4px; }
        .report-title { font-size: 16px; font-weight: 800; color: var(--theme-blue); margin-top: 8px; text-transform: uppercase; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin: 10px 0; font-size: 12px; color: #374151; }
        .print-btn { border: 1px solid var(--theme-blue); background: var(--theme-blue); color: #fff; padding: 6px 12px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 10px; }
        th, td { border: 1px solid var(--theme-blue); padding: 5px; vertical-align: top; }
        th { background: var(--theme-blue); color: #fff; text-align: left; }
        .text-end { text-align: right; }
        tfoot td { font-weight: 700; background: #f8f9fa; }
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
        <div class="report-title">Detailed Income Report</div>
        <div class="school-details">
            Generated: {{ $printedAt }}
            @if($filterCampus) | Campus: {{ $filterCampus }} @endif
            @if($filterClass) | Class: {{ $filterClass }} @endif
            @if($filterSection) | Section: {{ $filterSection }} @endif
            @if($filterMonth) | Month: {{ $months[$filterMonth] ?? $filterMonth }} @endif
            @if($filterDate) | Date: {{ $filterDate }} @endif
            @if($filterYear) | Year: {{ $filterYear }} @endif
            @if($filterMethod) | Method: {{ $filterMethod }} @endif
        </div>
    </div>
    <div class="top-bar">
        <div>Total Records: {{ $incomeRecords->count() }}</div>
        <div class="no-print"><button class="print-btn" onclick="window.print()">Print</button></div>
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
            <th class="text-end">Amount Paid</th>
            <th class="text-end">Discount</th>
            <th>Method</th>
            <th>Received By</th>
            <th>Payment Date/Time</th>
        </tr>
        </thead>
        <tbody>
        @forelse($incomeRecords as $index => $record)
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
                <td>{{ !empty($record['payment_date']) ? \Carbon\Carbon::parse($record['payment_date'])->format('d M Y h:i A') : 'N/A' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="11" class="text-center">No income records found.</td>
            </tr>
        @endforelse
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
        <div><strong>Total Amount:</strong> {{ number_format($incomeRecords->sum('payment_amount'), 2) }}</div>
        <div>System Generated Report</div>
    </div>
</div>
@if(request()->get('auto_print'))
<script>
    window.addEventListener('load', function () {
        setTimeout(function () { window.print(); }, 300);
    });
</script>
@endif
</body>
</html>
