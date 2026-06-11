<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fee Default Report</title>
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
        <div class="report-title">Fee Default Report</div>
        <div class="school-details">Generated: {{ $generatedAt }}</div>
        <div class="school-details">
            Campus: {{ $filters['campus'] ?: 'All' }} |
            Class: {{ $filters['class'] ?: 'All' }} |
            Section: {{ $filters['section'] ?: 'All' }} |
            Type: {{ $filters['type'] ?: 'All' }} |
            Status: {{ $filters['status'] ?: 'All' }}
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:35px;">#</th>
                <th>Student Code</th>
                <th>Student</th>
                <th>Parent</th>
                <th>Class</th>
                <th style="width:75px;">Due</th>
                <th style="width:80px;">Total</th>
                <th style="width:80px;">Paid</th>
                <th style="width:75px;">Late</th>
                <th style="width:85px;">Remaining</th>
            </tr>
        </thead>
        <tbody>
            @forelse($rows as $i => $row)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td>{{ $row['student_code'] ?? 'N/A' }}</td>
                    <td>{{ $row['student_name'] ?? 'N/A' }}</td>
                    <td>{{ $row['parent_name'] ?? 'N/A' }}</td>
                    <td>{{ $row['class'] ?? 'N/A' }}</td>
                    <td class="text-end">{{ $row['due_invoices'] ?? 0 }}</td>
                    <td class="text-end">{{ number_format((float)($row['total'] ?? 0), 2) }}</td>
                    <td class="text-end">{{ number_format((float)($row['paid'] ?? 0), 2) }}</td>
                    <td class="text-end">{{ number_format((float)($row['late'] ?? 0), 2) }}</td>
                    <td class="text-end">{{ number_format((float)($row['remaining'] ?? 0), 2) }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="10" style="text-align: center;">No records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-section">
        <div class="footer-left"><strong>Total:</strong> {{ $rows->count() }}</div>
        <div class="footer-right">System Generated Report</div>
    </div>
</body>
</html>
