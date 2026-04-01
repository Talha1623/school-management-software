<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Academic Calendar Print</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; margin: 0; color: #1f2937; background: #fff; }
        .page { width: 210mm; min-height: 297mm; margin: 0 auto; padding: 12mm; }
        .header { border-bottom: 2px solid #003471; padding-bottom: 10px; margin-bottom: 10px; text-align: center; }
        .school-name { font-size: 22px; color: #003471; font-weight: 700; }
        .meta { font-size: 12px; color: #374151; margin-top: 4px; }
        .title { font-size: 16px; font-weight: 700; color: #003471; margin-top: 8px; }
        .toolbar { display: flex; justify-content: space-between; align-items: center; margin-bottom: 10px; }
        .print-btn { border: 1px solid #003471; background: #003471; color: #fff; padding: 6px 12px; cursor: pointer; }
        .month { margin-bottom: 10px; border: 1px solid #003471; border-radius: 6px; overflow: hidden; }
        .month-head { background: #003471; color: #fff; padding: 6px 10px; font-weight: 700; }
        table { width: 100%; border-collapse: collapse; font-size: 11px; }
        th, td { border: 1px solid #c9d5ea; padding: 6px; text-align: left; vertical-align: top; }
        th { background: #eef4ff; color: #003471; }
        .empty { text-align: center; color: #6b7280; padding: 8px; }
        @media print {
            .no-print { display: none !important; }
            @page { size: A4; margin: 10mm; }
            .month { page-break-inside: avoid; }
        }
    </style>
</head>
<body>
@php
    $months = [
        1 => 'January', 2 => 'February', 3 => 'March', 4 => 'April',
        5 => 'May', 6 => 'June', 7 => 'July', 8 => 'August',
        9 => 'September', 10 => 'October', 11 => 'November', 12 => 'December'
    ];
@endphp
<div class="page">
    <div class="header">
        <div class="school-name">{{ $settings->school_name ?? 'School Name' }}</div>
        <div class="meta">{{ $settings->address ?? '' }}</div>
        <div class="meta">{{ $settings->school_phone ?? '' }} {{ !empty($settings->school_email) ? '| '.$settings->school_email : '' }}</div>
        <div class="title">Academic Calendar - {{ $year }}</div>
    </div>

    <div class="toolbar">
        <div class="meta">Generated: {{ now()->format('d M Y, h:i A') }}</div>
        <button class="print-btn no-print" onclick="window.print()">Print</button>
    </div>

    @foreach($months as $monthNum => $monthName)
        @php $monthEvents = $eventsByMonth[$monthNum] ?? []; @endphp
        <div class="month">
            <div class="month-head">{{ $monthName }} ({{ count($monthEvents) }})</div>
            <table>
                <thead>
                <tr>
                    <th style="width:40px;">#</th>
                    <th style="width:120px;">Date</th>
                    <th style="width:180px;">Title</th>
                    <th style="width:120px;">Type</th>
                    <th>Details</th>
                </tr>
                </thead>
                <tbody>
                @forelse($monthEvents as $idx => $event)
                    <tr>
                        <td>{{ $idx + 1 }}</td>
                        <td>{{ $event->event_date->format('d M Y') }}</td>
                        <td>{{ $event->event_title }}</td>
                        <td>{{ $event->event_type ?? 'N/A' }}</td>
                        <td>{{ $event->event_details ?? 'N/A' }}</td>
                    </tr>
                @empty
                    <tr><td colspan="5" class="empty">No events</td></tr>
                @endforelse
                </tbody>
            </table>
        </div>
    @endforeach
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

