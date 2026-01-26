<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Staff List</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background: #fff;
            color: #111;
            margin: 0;
            padding: 16px;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            margin-bottom: 12px;
        }
        .title {
            font-size: 18px;
            font-weight: 600;
            margin: 0 0 4px 0;
        }
        .subtitle {
            font-size: 12px;
            color: #666;
            margin: 0;
        }
        .print-btn {
            font-size: 12px;
            padding: 6px 10px;
            border: 1px solid #003471;
            background: #003471;
            color: #fff;
            border-radius: 4px;
            cursor: pointer;
        }
        .printed-at {
            font-size: 11px;
            color: #666;
            margin-top: 4px;
            text-align: right;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        th, td {
            border: 1px solid #ccc;
            padding: 6px 8px;
            text-align: left;
        }
        th {
            background: #f5f5f5;
        }
        .empty {
            text-align: center;
            color: #666;
            padding: 24px 0;
        }
        @media print {
            .no-print {
                display: none !important;
            }
            table {
                page-break-inside: auto;
            }
            tr {
                page-break-inside: avoid;
                page-break-after: auto;
            }
            thead {
                display: table-header-group;
            }
        }
    </style>
</head>
<body>
    <div class="header">
        <div>
            <h1 class="title">Staff List</h1>
            <p class="subtitle">List of all staff members</p>
        </div>
        <div>
            <button type="button" class="print-btn no-print" onclick="window.print()">Print</button>
            <div class="printed-at">Printed: {{ $printedAt }}</div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Name</th>
                <th>Employee ID</th>
                <th>Designation</th>
                <th>Campus</th>
                <th>Phone</th>
                <th>Email</th>
                <th>Gender</th>
                <th>Joining Date</th>
            </tr>
        </thead>
        <tbody>
            @forelse($staff as $index => $member)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $member->name ?? 'N/A' }}</td>
                    <td>{{ $member->emp_id ?? 'N/A' }}</td>
                    <td>{{ $member->designation ?? 'N/A' }}</td>
                    <td>{{ $member->campus ?? 'N/A' }}</td>
                    <td>{{ $member->phone ?? 'N/A' }}</td>
                    <td>{{ $member->email ?? 'N/A' }}</td>
                    <td>{{ $member->gender ?? 'N/A' }}</td>
                    <td>{{ $member->joining_date ? \Carbon\Carbon::parse($member->joining_date)->format('d M Y') : 'N/A' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="9" class="empty">No staff found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>

    @if(request()->get('auto_print'))
        <script>
            window.addEventListener('load', function() {
                window.print();
            });
        </script>
    @endif
</body>
</html>
