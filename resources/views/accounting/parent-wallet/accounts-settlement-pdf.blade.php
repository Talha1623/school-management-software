<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Accounts Settlement PDF</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 11px; color: #111827; }
        .header { text-align: center; margin-bottom: 10px; }
        .logo { width: 72px; height: 72px; object-fit: contain; margin: 0 auto 6px auto; display: block; }
        .school { font-size: 24px; font-weight: 700; color: #003471; margin: 0; }
        .meta { margin: 2px 0; color: #374151; font-size: 11px; }
        .title { margin: 6px 0 2px; font-size: 18px; font-weight: 700; }
        .line { border-top: 1px solid #003471; margin: 8px 0; }
        table { width: 100%; border-collapse: collapse; table-layout: fixed; }
        th, td { border: 1px solid #9ca3af; padding: 6px; text-align: left; word-wrap: break-word; }
        th { background: #003471; color: #fff; }
        .right { text-align: right; }
        .footer { margin-top: 8px; font-size: 11px; }
    </style>
</head>
<body>
    <div class="header">
        @if(!empty($schoolLogoUrl))
            <img src="{{ $schoolLogoUrl }}" class="logo" alt="School Logo">
        @endif
        <p class="school">{{ $schoolName ?? 'Education Management System' }}</p>
        @if(!empty($schoolAddress))
            <p class="meta">{{ $schoolAddress }}</p>
        @endif
        @if(!empty($schoolPhone) || !empty($schoolEmail))
            <p class="meta">
                @if(!empty($schoolPhone)){{ $schoolPhone }}@endif
                @if(!empty($schoolPhone) && !empty($schoolEmail)) | @endif
                @if(!empty($schoolEmail)){{ $schoolEmail }}@endif
            </p>
        @endif
        <p class="title">ACCOUNTS SETTLEMENT — FULL LIST</p>
        <p class="meta">Generated: {{ $printedAt ?? now()->format('d-m-Y H:i') }} || Records: {{ $settlements->count() }}</p>
    </div>

    <div class="line"></div>
    <table>
        <thead>
            <tr>
                <th style="width:4%;">#</th>
                <th style="width:10%;">Date</th>
                <th style="width:15%;">Campus</th>
                <th style="width:16%;">User</th>
                <th style="width:14%;">Method</th>
                <th style="width:14%;">Transaction ID</th>
                <th style="width:13%;">Total Payment</th>
                <th style="width:14%;">Remarks</th>
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

    <div class="footer">
        <strong>Total:</strong> {{ $settlements->count() }}<br>
        @if(!empty($printNote))
            <strong>Note:</strong> {{ $printNote }}<br>
        @endif
        System Generated Report
    </div>
</body>
</html>
