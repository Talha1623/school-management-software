<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: DejaVu Sans, sans-serif; font-size: 6px; color: #111827; margin: 0; padding: 8px; }
        .header-section { text-align: center; border-bottom: 2px solid #003471; padding-bottom: 6px; margin-bottom: 8px; }
        .school-name { font-size: 12px; font-weight: bold; color: #003471; }
        .school-details { font-size: 7px; color: #374151; margin-top: 2px; }
        .report-title { font-size: 10px; font-weight: bold; color: #003471; margin-top: 4px; text-transform: uppercase; }
        .meta { font-size: 7px; color: #374151; margin-top: 2px; }
        table { width: 100%; border-collapse: collapse; margin-top: 6px; table-layout: fixed; }
        th, td { border: 1px solid #003471; padding: 2px; vertical-align: top; word-wrap: break-word; }
        th { background-color: #003471; color: #fff; font-weight: bold; text-align: left; }
        .footer { margin-top: 8px; font-size: 7px; color: #374151; border-top: 1px solid #003471; padding-top: 4px; }
    </style>
</head>
<body>
<div class="header-section">
    <div class="school-name">{{ $settings->school_name ?? 'School Name' }}</div>
    <div class="school-details">
        {{ $settings->address ?? '' }}
        @if(!empty($settings->school_phone)) | {{ $settings->school_phone }} @endif
        @if(!empty($settings->school_email)) | {{ $settings->school_email }} @endif
    </div>
    <div class="report-title">Staff Management — Full List</div>
    <div class="meta">Generated: {{ $generatedAt }} &nbsp;|&nbsp; Records: {{ $rows->count() }}</div>
</div>

<table>
    <thead>
    <tr>
        @foreach($headers as $h)
            <th>{{ $h }}</th>
        @endforeach
    </tr>
    </thead>
    <tbody>
    @forelse($rows as $row)
        <tr>
            @foreach($row as $cell)
                <td>{{ is_scalar($cell) || $cell === null ? (string) ($cell ?? '') : '' }}</td>
            @endforeach
        </tr>
    @empty
        <tr>
            <td colspan="{{ count($headers) }}" style="text-align:center;">No staff found.</td>
        </tr>
    @endforelse
    </tbody>
</table>

<div class="footer">
    <strong>Total:</strong> {{ $rows->count() }} &nbsp;|&nbsp; System Generated Report
</div>
</body>
</html>
