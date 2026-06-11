<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Loan Management — PDF</title>
    <style>
        @page { size: A4 landscape; margin: 8mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #fff; color: #111827; margin: 12px; }
        .print-container { width: 100%; max-width: 297mm; margin: 0 auto; }
        :root { --theme-blue: #003471; }
        .header-section { border-bottom: 3px solid var(--theme-blue); padding-bottom: 10px; text-align: center; margin-bottom: 10px; }
        .school-name { font-size: 18px; font-weight: 800; color: var(--theme-blue); }
        .school-details { font-size: 11px; color: #374151; margin-top: 4px; }
        .report-title { font-size: 14px; font-weight: 800; color: var(--theme-blue); margin-top: 6px; text-transform: uppercase; }
        .meta { font-size: 11px; color: #374151; margin-top: 4px; }
        table { width: 100%; border-collapse: collapse; font-size: 8px; table-layout: fixed; }
        th, td { border: 1px solid var(--theme-blue); padding: 4px 3px; vertical-align: top; word-wrap: break-word; }
        th { background: var(--theme-blue); color: #fff; text-align: left; font-weight: 700; }
        .num { text-align: right; }
        .footer-section { border-top: 2px solid var(--theme-blue); margin-top: 12px; padding-top: 8px; font-size: 11px; display: flex; justify-content: space-between; flex-wrap: wrap; gap: 8px; }
    </style>
</head>
<body>
@php
    $currency = $settings->currency ?? 'PKR';
@endphp
<div class="print-container">
    <div class="header-section">
        <div class="school-name">{{ $settings->school_name ?? 'School Name' }}</div>
        <div class="school-details">
            {{ $settings->address ?? '' }}
            @if(!empty($settings->school_phone)) | {{ $settings->school_phone }} @endif
            @if(!empty($settings->school_email)) | {{ $settings->school_email }} @endif
        </div>
        <div class="report-title">Loan Management</div>
        <div class="meta">Generated: {{ $printedAt }} &nbsp;|&nbsp; Records: {{ $loans->count() }}</div>
    </div>

    <table>
        <thead>
        <tr>
            <th style="width:3%;">#</th>
            <th style="width:5%;">ID</th>
            <th style="width:7%;">Emp ID</th>
            <th style="width:14%;">Staff name</th>
            <th style="width:9%;">Campus</th>
            <th class="num" style="width:10%;">Requested</th>
            <th class="num" style="width:10%;">Approved</th>
            <th class="num" style="width:8%;">Instalments</th>
            <th style="width:8%;">Status</th>
            <th style="width:11%;">Applied</th>
        </tr>
        </thead>
        <tbody>
        @forelse($loans as $index => $loan)
            @php $s = $loan->staff; @endphp
            <tr>
                <td>{{ $index + 1 }}</td>
                <td>{{ $loan->id }}</td>
                <td>{{ $s->emp_id ?? 'N/A' }}</td>
                <td>{{ $s->name ?? 'N/A' }}</td>
                <td>{{ $s->campus ?? 'N/A' }}</td>
                <td class="num">{{ $currency }} {{ number_format((float) $loan->requested_amount, 2) }}</td>
                <td class="num">
                    @if($loan->approved_amount !== null)
                        {{ $currency }} {{ number_format((float) $loan->approved_amount, 2) }}
                    @else
                        —
                    @endif
                </td>
                <td class="num">{{ $loan->repayment_instalments }}</td>
                <td>{{ $loan->status }}</td>
                <td>{{ $loan->created_at?->format('d M Y, h:i A') ?? '—' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="10" style="text-align:center;color:#6b7280;">No loan records found.</td>
            </tr>
        @endforelse
        @if($loans->count() > 0)
            <tr>
                <td colspan="5" class="num" style="font-weight:700;background:#f3f4f6;">Totals</td>
                <td class="num" style="font-weight:700;background:#f3f4f6;">{{ $currency }} {{ number_format($totalRequested, 2) }}</td>
                <td class="num" style="font-weight:700;background:#f3f4f6;">{{ $currency }} {{ number_format($totalApproved, 2) }}</td>
                <td colspan="3" style="background:#f3f4f6;"></td>
            </tr>
        @endif
        </tbody>
    </table>

    <div class="footer-section">
        <div>
            <strong>Total requested:</strong> {{ $currency }} {{ number_format($totalRequested, 2) }}
            &nbsp;|&nbsp;
            <strong>Total approved:</strong> {{ $currency }} {{ number_format($totalApproved, 2) }}
        </div>
        <div>System generated report</div>
    </div>
</div>
</body>
</html>
