<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Noticeboard List Print</title>
    <style>
        @page { size: A4; margin: 10mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #fff; color: #111827; }
        .print-container { width: 210mm; margin: 0 auto; }
        .header-section { border-bottom: 3px solid #003471; padding-bottom: 10px; text-align: center; }
        .school-name { font-size: 20px; font-weight: 800; color: #003471; }
        .school-details { font-size: 12px; color: #374151; margin-top: 4px; }
        .report-title { margin-top: 8px; font-size: 16px; font-weight: 800; color: #003471; text-transform: uppercase; }
        .meta-row { margin-top: 6px; font-size: 12px; color: #374151; }

        .no-print { text-align: right; margin: 10px 0; }
        .print-btn { border: 1px solid #003471; background: #003471; color: #fff; padding: 6px 12px; cursor: pointer; }

        table { width: 100%; border-collapse: collapse; margin-top: 14px; font-size: 11px; }
        th, td { border: 1px solid #003471; padding: 6px; vertical-align: top; }
        th { background: #003471; color: #fff; text-align: left; }
        .text-end { text-align: right; }

        .footer-section { border-top: 2px solid #003471; margin-top: 12px; padding-top: 10px; display: flex; justify-content: space-between; font-size: 12px; }

        @media print {
            .no-print { display: none !important; }
        }
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
        <div class="report-title">Noticeboard List</div>
        <div class="meta-row">
            Generated: {{ $printedAt ?? now()->format('d M Y, h:i A') }}
            @if(request('search'))
                <span> | Search: "{{ request('search') }}"</span>
            @endif
        </div>
    </div>

    <div class="no-print">
        <button class="print-btn" onclick="window.print()">Print</button>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:50px;">#</th>
                <th style="width:140px;">Campus</th>
                <th>Title</th>
                <th style="width:220px;">Notice</th>
                <th style="width:100px;">Date</th>
                <th style="width:90px;">Show On</th>
                <th style="width:110px;">Image</th>
            </tr>
        </thead>
        <tbody>
        @forelse($noticeboards as $i => $noticeboard)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td>{{ $noticeboard->campus ?? 'N/A' }}</td>
                <td><strong>{{ $noticeboard->title ?? 'N/A' }}</strong></td>
                <td>{{ $noticeboard->notice ? (strlen($noticeboard->notice) > 80 ? substr($noticeboard->notice, 0, 80) . '...' : $noticeboard->notice) : 'N/A' }}</td>
                <td>{{ $noticeboard->date ? \Carbon\Carbon::parse($noticeboard->date)->format('d M Y') : 'N/A' }}</td>
                <td>{{ $noticeboard->show_on ?? 'N/A' }}</td>
                <td>
                    @if(!empty($noticeboard->image))
                        <img src="{{ asset('storage/' . $noticeboard->image) }}" alt="Notice" style="width:80px; height:50px; object-fit:cover; border:1px solid #d1d5db;">
                    @else
                        N/A
                    @endif
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="7" style="text-align:center; color:#6b7280;">No noticeboards found.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="footer-section">
        <div><strong>Total:</strong> {{ $noticeboards->count() }}</div>
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

