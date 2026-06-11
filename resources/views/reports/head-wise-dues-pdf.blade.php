<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Head Wise Dues Summary PDF</title>
    <style>
        body { font-family: DejaVu Sans, Arial, sans-serif; font-size: 10px; color: #111827; }
        .header { text-align: center; margin-bottom: 8px; }
        .logo { width: 64px; height: 64px; object-fit: contain; margin: 0 auto 4px auto; display: block; }
        .school { font-size: 18px; font-weight: 700; color: #003471; margin: 0; }
        .meta { margin: 2px 0; color: #374151; font-size: 9px; }
        .title { margin: 4px 0 2px; font-size: 14px; font-weight: 700; }
        .line { border-top: 1px solid #003471; margin: 6px 0; }
        .campus-title { margin-top: 10px; font-size: 12px; font-weight: 700; color: #003471; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; table-layout: fixed; }
        th, td { border: 1px solid #9ca3af; padding: 4px; word-wrap: break-word; }
        th { background: #003471; color: #fff; font-size: 8px; }
        td { background: #f3f4f6; font-size: 8px; }
        .right { text-align: right; }
        .total-row td { font-weight: 700; }
        .footer { margin-top: 8px; font-size: 9px; }
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
        <p class="title">HEAD WISE DUES SUMMARY</p>
        <p class="meta">Filters: {{ $filterDescription ?? '—' }} | Generated: {{ $printedAt ?? now()->format('d-m-Y H:i') }}</p>
    </div>
    <div class="line"></div>

    @forelse($allCampusData as $campusData)
        @php $campusFeeHeads = $campusData['fee_heads'] ?? $feeHeads; @endphp
        <div class="campus-title">Campus: {{ $campusData['campus'] }}</div>
        <table>
            <thead>
                <tr>
                    <th>Class</th>
                    @foreach($campusFeeHeads as $head)
                        <th class="right">{{ $head }}</th>
                    @endforeach
                    <th class="right">Total Paid</th>
                    <th class="right">Total Due</th>
                </tr>
            </thead>
            <tbody>
                @foreach($campusData['rows'] as $row)
                    <tr>
                        <td>{{ $row['class'] }}</td>
                        @foreach($campusFeeHeads as $head)
                            @php $headData = $row['heads'][$head] ?? ['paid' => 0, 'due' => 0]; @endphp
                            <td class="right">{{ number_format($headData['paid'] ?? 0, 2) }} / {{ number_format($headData['due'] ?? 0, 2) }}</td>
                        @endforeach
                        <td class="right">{{ number_format($row['total_paid'] ?? 0, 2) }}</td>
                        <td class="right">{{ number_format($row['total'] ?? 0, 2) }}</td>
                    </tr>
                @endforeach
                <tr class="total-row">
                    <td>Total Paid</td>
                    @foreach($campusFeeHeads as $head)
                        <td class="right">{{ number_format($campusData['head_paid_totals'][$head] ?? 0, 2) }}</td>
                    @endforeach
                    <td class="right">{{ number_format($campusData['total_paid'] ?? 0, 2) }}</td>
                    <td></td>
                </tr>
                <tr class="total-row">
                    <td>Total Due</td>
                    @foreach($campusFeeHeads as $head)
                        <td class="right">{{ number_format($campusData['head_totals'][$head] ?? 0, 2) }}</td>
                    @endforeach
                    <td></td>
                    <td class="right">{{ number_format($campusData['total'] ?? 0, 2) }}</td>
                </tr>
            </tbody>
        </table>
    @empty
        <p style="text-align:center;color:#6b7280;">No data found for selected filters.</p>
    @endforelse

    @if(($allCampusData->count() ?? 0) > 1)
        <table style="margin-top:10px;">
            <tr class="total-row">
                <td><strong>GRAND TOTAL PAID</strong></td>
                @foreach($feeHeads as $head)
                    <td class="right"><strong>{{ number_format($grandTotal['heads_paid'][$head] ?? 0, 2) }}</strong></td>
                @endforeach
                <td class="right"><strong>{{ number_format($grandTotal['total_paid'] ?? 0, 2) }}</strong></td>
                <td></td>
            </tr>
            <tr class="total-row">
                <td><strong>GRAND TOTAL DUE</strong></td>
                @foreach($feeHeads as $head)
                    <td class="right"><strong>{{ number_format($grandTotal['heads'][$head] ?? 0, 2) }}</strong></td>
                @endforeach
                <td></td>
                <td class="right"><strong>{{ number_format($grandTotal['total'] ?? 0, 2) }}</strong></td>
            </tr>
        </table>
    @endif

    <div class="footer">System Generated Report</div>
</body>
</html>
