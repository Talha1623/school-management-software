<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Roles Print</title>
    <style>
        @page { size: A4; margin: 10mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #fff; color: #111827; }
        .print-container { width: 210mm; margin: 0 auto; }

        :root { --theme-blue: #003471; --theme-blue-2: #004a9f; }

        .header-section {
            border-bottom: 3px solid var(--theme-blue);
            padding-bottom: 10px;
            text-align: center;
        }
        .school-name {
            font-size: 20px;
            font-weight: 800;
            color: var(--theme-blue);
        }
        .school-details {
            font-size: 12px;
            color: #374151;
            margin-top: 4px;
        }
        .report-title {
            font-size: 16px;
            font-weight: 800;
            color: var(--theme-blue);
            margin-top: 8px;
            text-transform: uppercase;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 10px 0;
            font-size: 12px;
            color: #374151;
        }

        table { width: 100%; border-collapse: collapse; margin-top: 12px; font-size: 11px; }
        th, td { border: 1px solid var(--theme-blue); padding: 6px; vertical-align: top; }
        th { background: var(--theme-blue); color: #fff; text-align: left; }
        .text-end { text-align: right; }

        .footer-section {
            border-top: 2px solid var(--theme-blue);
            margin-top: 14px;
            padding-top: 10px;
            display: flex;
            justify-content: space-between;
            font-size: 12px;
        }

        .no-print { text-align: right; }
        .print-btn {
            border: 1px solid var(--theme-blue);
            background: var(--theme-blue);
            color: #fff;
            padding: 6px 12px;
            cursor: pointer;
        }

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
        <div class="report-title">Admin Roles List</div>
        <div class="school-details" style="margin-top:6px;">
            Generated: {{ $printedAt ?? now()->format('d M Y, h:i A') }}
            @if(request('search'))
                <span> | Search: "{{ request('search') }}"</span>
            @endif
        </div>
    </div>

    <div class="top-bar">
        <div></div>
        <div class="no-print">
            <button class="print-btn" onclick="window.print()">Print</button>
        </div>
    </div>

    <table>
        <thead>
        <tr>
            <th style="width:60px;">#</th>
            <th>Name</th>
            <th style="width:140px;">Phone</th>
            <th style="width:180px;">Email</th>
            <th style="width:160px;">Admin Of</th>
            <th style="width:120px;">Super Admin</th>
        </tr>
        </thead>
        <tbody>
        @forelse($adminRoles as $i => $adminRole)
            <tr>
                <td>{{ $i + 1 }}</td>
                <td><strong>{{ $adminRole->name ?? 'N/A' }}</strong></td>
                <td>{{ $adminRole->phone ?? 'N/A' }}</td>
                <td>{{ $adminRole->email ?? 'N/A' }}</td>
                <td>{{ $adminRole->admin_of ?? 'N/A' }}</td>
                <td>{{ $adminRole->super_admin ? 'Yes' : 'No' }}</td>
            </tr>
        @empty
            <tr>
                <td colspan="6" style="text-align:center; color:#6b7280;">No admin roles found.</td>
            </tr>
        @endforelse
        </tbody>
    </table>

    <div class="footer-section">
        <div><strong>Total:</strong> {{ $adminRoles->count() }}</div>
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

