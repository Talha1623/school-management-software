@php
    $settings = $settings ?? \App\Models\GeneralSetting::getSettings();
@endphp
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Parent Dues Report - {{ $parent->name ?? 'Parent' }}</title>
    <style>
        @page { size: A4; margin: 10mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #fff; color: #111827; margin: 0; }
        .print-container { width: 210mm; max-width: 100%; margin: 0 auto; padding: 16px; }
        :root { --theme-blue: #003471; }
        .header-section { border-bottom: 3px solid var(--theme-blue); padding-bottom: 10px; text-align: center; }
        .school-name { font-size: 20px; font-weight: 800; color: var(--theme-blue); }
        .school-details { font-size: 12px; color: #374151; margin-top: 4px; }
        .report-title { font-size: 16px; font-weight: 800; color: var(--theme-blue); margin-top: 8px; text-transform: uppercase; }
        .meta { font-size: 12px; color: #374151; margin-top: 4px; }
        .top-bar { display: flex; justify-content: flex-end; margin: 10px 0; }
        .print-btn { border: 1px solid var(--theme-blue); background: var(--theme-blue); color: #fff; padding: 6px 12px; cursor: pointer; }
        table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 11px; }
        th, td { border: 1px solid var(--theme-blue); padding: 6px; vertical-align: top; }
        th { background: var(--theme-blue); color: #fff; text-align: left; }
        .text-right { text-align: right; }
        .footer-section { border-top: 2px solid var(--theme-blue); margin-top: 14px; padding-top: 10px; display: flex; justify-content: space-between; font-size: 12px; }
        .empty { text-align: center; color: #6b7280; padding: 24px 0; }
        .total-row td { font-weight: 700; background: #f8fafc; }
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
        <div class="report-title">Parent Dues Report</div>
        <div class="meta">
            Parent: {{ $parent->name ?? 'N/A' }}
            @if(!empty($parent->id_card_number)) | ID Card: {{ $parent->id_card_number }} @endif
            | Generated: {{ $printedAt }}
        </div>
    </div>

    <div class="top-bar no-print">
        <button type="button" class="print-btn" onclick="window.print()">Print</button>
    </div>

    <table>
        <thead>
        <tr>
            <th style="width:5%;">#</th>
            <th style="width:18%;">Student</th>
            <th style="width:12%;">Student Code</th>
            <th style="width:12%;">Class/Section</th>
            <th style="width:16%;">Fee Title</th>
            <th class="text-right" style="width:8%;">Total</th>
            <th class="text-right" style="width:7%;">Dis</th>
            <th class="text-right" style="width:7%;">Late Fee</th>
            <th class="text-right" style="width:7%;">Paid</th>
            <th class="text-right" style="width:8%;">Due</th>
        </tr>
        </thead>
        <tbody>
        @forelse($dueRows as $index => $row)
            @php
                $student = $studentsByCode[$row['student_code'] ?? ''] ?? null;
                $total = (float) ($row['total'] ?? 0);
                $discount = (float) ($row['discount'] ?? 0);
                $lateFee = (float) ($row['late_fee'] ?? 0);
                $paid = (float) ($row['paid'] ?? 0);
                $due = (float) ($row['due'] ?? 0);
            @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $row['student_name'] ?? ($student->student_name ?? 'N/A') }}</td>
                <td>{{ $row['student_code'] ?? 'N/A' }}</td>
                <td>
                    {{ $row['class'] ?? ($student->class ?? 'N/A') }}
                    @if(!empty($row['section']) || ($student && $student->section)) / {{ $row['section'] ?? $student->section }} @endif
                </td>
                <td>{{ $row['fee_type'] ?? 'N/A' }}</td>
                <td class="text-right">{{ number_format($total, 2) }}</td>
                <td class="text-right">{{ number_format($discount, 2) }}</td>
                <td class="text-right">{{ number_format($lateFee, 2) }}</td>
                <td class="text-right">{{ number_format($paid, 2) }}</td>
                <td class="text-right">{{ number_format($due, 2) }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="10" class="empty">No pending dues found for this parent.</td>
            </tr>
        @endforelse
        @if($dueRows->isNotEmpty())
            <tr class="total-row">
                <td colspan="5" class="text-right">Grand Total</td>
                <td class="text-right">{{ number_format($tableTotals['total'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($tableTotals['discount'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($tableTotals['late_fee'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($tableTotals['paid'] ?? 0, 2) }}</td>
                <td class="text-right">{{ number_format($totalDue, 2) }}</td>
            </tr>
        @endif
        </tbody>
    </table>

    <div class="footer-section">
        <div>System generated report</div>
        <div><strong>Total Due:</strong> {{ number_format($totalDue, 2) }}</div>
    </div>
</div>

@if(!empty($autoPrint))
    <script>
        window.addEventListener('load', function() {
            window.print();
        });
    </script>
@endif
</body>
</html>
