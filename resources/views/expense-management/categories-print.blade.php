<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Expense Categories Print</title>
    <style>
        @page { size: A4; margin: 10mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #fff; color: #111827; }
        .print-container { width: 210mm; margin: 0 auto; }
        .header-section { border-bottom: 3px solid #003471; padding-bottom: 10px; text-align: center; }
        .school-name { font-size: 20px; font-weight: 800; color: #003471; }
        .school-details { font-size: 12px; color: #374151; margin-top: 4px; }
        .report-title { font-size: 16px; font-weight: 800; color: #003471; margin-top: 8px; text-transform: uppercase; }
        .meta-row { margin-top: 6px; font-size: 12px; color: #374151; }

        .no-print { margin-top: 10px; text-align: right; }
        .print-btn { border: 1px solid #003471; background: #003471; color: #fff; padding: 6px 12px; cursor: pointer; }

        table { width: 100%; border-collapse: collapse; margin-top: 14px; font-size: 11px; }
        th, td { border: 1px solid #003471; padding: 6px; vertical-align: top; }
        th { background: #003471; color: #fff; text-align: left; }
        td.text-end, th.text-end { text-align: right; }

        .footer-section { border-top: 2px solid #003471; margin-top: 12px; padding-top: 10px; display: flex; justify-content: space-between; font-size: 12px; color: #111827; }

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
        <div class="report-title">Expense Categories List</div>
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
                <th>Category Name</th>
                <th style="width:160px;">Campus</th>
            </tr>
        </thead>
        <tbody>
            @forelse($categories as $i => $category)
                <tr>
                    <td>{{ $i + 1 }}</td>
                    <td><strong>{{ $category->category_name ?? 'N/A' }}</strong></td>
                    <td>{{ $category->campus ?? '' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="3" style="text-align:center; color:#6b7280;">
                        No categories found.
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>

    <div class="footer-section">
        <div><strong>Total:</strong> {{ $categories->count() }}</div>
        <div>System Generated Report</div>
    </div>
</div>

<script>
    window.addEventListener('load', function () {
        const params = new URLSearchParams(window.location.search);
        if (params.get('auto_print') === '1') {
            window.print();
        }
    });
</script>
</body>
</html>

