<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Head Wise Dues Summary Print</title>
    <style>
        @page { size: A4; margin: 10mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #fff; color: #111827; }
        .print-container { width: 210mm; margin: 0 auto; }
        :root { --theme-blue: #003471; --light-gray: #D3D3D3; }

        .header-section { border-bottom: 3px solid var(--theme-blue); padding-bottom: 10px; text-align: center; }
        .school-name { font-size: 20px; font-weight: 800; color: var(--theme-blue); }
        .school-details { font-size: 12px; color: #374151; margin-top: 4px; }
        .report-title { font-size: 16px; font-weight: 800; color: var(--theme-blue); margin-top: 8px; text-transform: uppercase; }
        .top-bar { display: flex; justify-content: space-between; align-items: center; margin: 10px 0; font-size: 12px; color: #374151; }
        .print-btn { border: 1px solid var(--theme-blue); background: var(--theme-blue); color: #fff; padding: 6px 12px; cursor: pointer; }

        .campus-title { margin-top: 14px; font-size: 14px; font-weight: 700; color: var(--theme-blue); }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; font-size: 11px; }
        th, td { border: 1px solid #b7bcc3; padding: 6px; vertical-align: top; }
        th { background: var(--light-gray); color: #111827; text-align: left; }
        td { background: var(--light-gray); }
        .text-end { text-align: right; }
        .total-row td { font-weight: 700; }
        .no-print { text-align: right; }

        .footer-section { border-top: 2px solid var(--theme-blue); margin-top: 14px; padding-top: 10px; display: flex; justify-content: space-between; font-size: 12px; }
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
        <div class="report-title">Head Wise Dues Summary</div>
        <div class="school-details">
            Generated: {{ $printedAt }}
            @if($filterCampus) | Campus: {{ $filterCampus }} @endif
            @if($filterClass) | Class: {{ $filterClass }} @endif
            @if($filterSection) | Section: {{ $filterSection }} @endif
        </div>
    </div>

    <div class="top-bar">
        <div></div>
        <div class="no-print"><button class="print-btn" onclick="window.print()">Print</button></div>
    </div>

    @forelse($allCampusData as $campusData)
        @php $campusFeeHeads = $campusData['fee_heads'] ?? $feeHeads; @endphp
        <div class="campus-title">Campus: {{ $campusData['campus'] }}</div>
        <table>
            <thead>
            <tr>
                <th>Class</th>
                @foreach($campusFeeHeads as $head)
                    <th class="text-end">{{ $head }}</th>
                @endforeach
                <th class="text-end">Total Paid</th>
                <th class="text-end">Total Due</th>
            </tr>
            </thead>
            <tbody>
            @foreach($campusData['rows'] as $row)
                <tr>
                    <td>{{ $row['class'] }}</td>
                    @foreach($campusFeeHeads as $head)
                        @php $headData = $row['heads'][$head] ?? ['paid' => 0, 'due' => 0]; @endphp
                        <td class="text-end">
                            {{ number_format($headData['paid'] ?? 0, 2) }} / {{ number_format($headData['due'] ?? 0, 2) }}
                            <br><small>Paid / Due</small>
                        </td>
                    @endforeach
                    <td class="text-end">{{ number_format($row['total_paid'] ?? 0, 2) }}</td>
                    <td class="text-end">{{ number_format($row['total'] ?? 0, 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td>Total Paid</td>
                @foreach($campusFeeHeads as $head)
                    <td class="text-end">{{ number_format($campusData['head_paid_totals'][$head] ?? 0, 2) }}</td>
                @endforeach
                <td class="text-end">{{ number_format($campusData['total_paid'] ?? 0, 2) }}</td>
                <td></td>
            </tr>
            <tr class="total-row">
                <td>Total Due</td>
                @foreach($campusFeeHeads as $head)
                    <td class="text-end">{{ number_format($campusData['head_totals'][$head] ?? 0, 2) }}</td>
                @endforeach
                <td></td>
                <td class="text-end">{{ number_format($campusData['total'] ?? 0, 2) }}</td>
            </tr>
            </tbody>
        </table>
    @empty
        <div style="margin-top: 18px; text-align: center; color: #6b7280;">No data found for selected filters.</div>
    @endforelse

    <div class="footer-section">
        <div><strong>Total Campuses:</strong> {{ $allCampusData->count() }}</div>
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
