<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Accounts Settlement</title>
    <style>
        body { font-family: Arial, sans-serif; font-size: 11px; margin: 14px; color: #111827; }
        .report-wrap { max-width: 1200px; margin: 0 auto; }
        .header-block { text-align: center; margin-bottom: 4px; }
        .header-logo-wrap { margin-bottom: 6px; }
        .header-logo { width: 120px; height: 120px; object-fit: contain; background: #fff; }
        .header-text { text-align: center; }
        .heading { text-align: center; color: #003471; margin: 0; font-size: 34px; font-weight: 700; }
        .subheading { text-align: center; margin: 2px 0 0; font-size: 20px; font-weight: 700; color: #0f172a; letter-spacing: 0.2px; }
        .school-meta { text-align: center; margin-top: 2px; font-size: 12px; color: #374151; line-height: 1.35; }
        .school-contact-line { text-align: center; margin-top: 4px; font-size: 12px; color: #1f2937; }
        .meta { text-align: center; margin: 4px 0 10px; font-size: 12px; color: #4b5563; }
        .rule { border-top: 2px solid #003471; margin: 8px 0; }
        .actions { display: flex; justify-content: flex-end; margin-bottom: 8px; }
        .print-btn { padding: 4px 12px; font-size: 12px; cursor: pointer; background: #003471; color: #fff; border: 1px solid #003471; border-radius: 2px; }
        .print-btn:hover { background: #0b4a89; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #9ca3af; padding: 5px 6px; text-align: left; vertical-align: top; word-wrap: break-word; }
        th { background: #003471; color: #fff; font-weight: 700; }
        .right { text-align: right; }
        .footer-row { display: flex; justify-content: space-between; margin-top: 8px; font-size: 12px; }
        .print-note { margin-top: 8px; font-size: 11px; color: #374151; text-align: left; }
        .print-note strong { color: #111827; }
        @media print { .print-btn { display: none; } }
    </style>
</head>
<body @if(request()->boolean('auto_print')) onload="window.print()" @endif>
    @php
        $printedAt = now()->format('d-m-Y H:i');
        $settlementCount = $settlements->count();
        $totalAmount = (float) $settlements->sum('total_payment');
        $addressText = trim((string) ($schoolAddress ?? ''));
        $phoneText = trim((string) ($schoolPhone ?? ''));
        $emailText = trim((string) ($schoolEmail ?? ''));
        $hasContact = ($phoneText !== '' || $emailText !== '');
    @endphp

    <div class="report-wrap">
        <div class="header-block">
            @if(!empty($schoolLogoUrl))
                <div class="header-logo-wrap">
                    <img src="{{ $schoolLogoUrl }}" alt="School Logo" class="header-logo">
                </div>
            @endif
            <div class="header-text">
                <h2 class="heading">{{ $schoolName ?? 'Education Management System' }}</h2>
                @if($addressText !== '')
                    <div class="school-meta">{{ $addressText }}</div>
                @endif
                @if($hasContact)
                    <div class="school-contact-line">
                        @if($phoneText !== '')
                            {{ $phoneText }}
                        @endif
                        @if($phoneText !== '' && $emailText !== '')
                            &nbsp; | &nbsp;
                        @endif
                        @if($emailText !== '')
                            {{ $emailText }}
                        @endif
                    </div>
                @endif
                <div class="subheading">ACCOUNTS SETTLEMENT — FULL LIST</div>
            </div>
        </div>
        <div class="meta">Date: {{ now()->format('d-m-Y') }} &nbsp;||&nbsp; Generated: {{ $printedAt }} &nbsp;||&nbsp; Records: {{ $settlementCount }}</div>
        <div class="rule"></div>
        <div class="actions">
            <button type="button" class="print-btn" onclick="window.print()">Print</button>
        </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Date</th>
                <th>Campus</th>
                <th>User</th>
                <th>Method</th>
                <th>Transaction ID</th>
                <th>Total Payment</th>
                <th>Remarks</th>
            </tr>
        </thead>
        <tbody>
            @forelse($settlements as $index => $settlement)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ \Carbon\Carbon::parse($settlement->settlement_date)->format('d-m-Y') }}</td>
                    <td>{{ $settlement->campus === 'all' ? 'All' : ucfirst($settlement->campus) }}</td>
                    <td>
                        {{ $settlement->created_by_name ?: ($settlement->user_name === 'all' ? 'All' : $settlement->user_name) }}
                        @if($settlement->created_by_type)
                            ({{ $settlement->created_by_type }})
                        @endif
                    </td>
                    <td>{{ ucwords(str_replace('_', ' ', $settlement->method)) }}</td>
                    <td>{{ $settlement->transaction_id ?: '-' }}</td>
                    <td class="right">{{ number_format((float) $settlement->total_payment, 2) }}</td>
                    <td>{{ $settlement->remarks ?: '-' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="8" style="text-align:center;">No settlement records found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
        <div class="rule"></div>
        <div class="footer-row">
            <div><strong>Total:</strong> {{ $settlementCount }}</div>
            <div>System Generated Report</div>
        </div>
        @if(!empty($printNote))
            <div class="print-note"><strong>Note:</strong> {{ $printNote }}</div>
        @endif
    </div>
</body>
</html>

