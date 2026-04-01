<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Campuses Print</title>
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
        <div class="report-title">Campuses List</div>
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
                <th>Campus Name</th>
                <th style="width:180px;">Campus Address</th>
                <th>Description</th>
            </tr>
        </thead>
        <tbody>
        @forelse($campuses as $i => $campus)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td><strong>{{ $campus->campus_name ?? 'N/A' }}</strong></td>
                <td>{{ $campus->campus_address ?? 'N/A' }}</td>
                <td>{{ $campus->description ?? 'N/A' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="4" style="text-align:center; color:#6b7280;">No campuses found.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="footer-section">
        <div><strong>Total:</strong> {{ $campuses->count() }}</div>
        <div>System Generated Report</div>
    </div>
</div>

<script>
    window.addEventListener('load', function () {
        const params = new URLSearchParams(window.location.search);
        if (params.get('auto_print') === '1') {
            setTimeout(function () { window.print(); }, 300);
        }
    });
</script>
</body>
</html>

