<!DOCTYPE html>
<html>
<head>
    <title>Noticeboards Report</title>
    <style>
        body { font-family: Arial, sans-serif; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #003471; color: white; }
    </style>
</head>
<body>
    <h2>Noticeboards Report</h2>
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Campus</th>
                <th>Title</th>
                <th>Notice</th>
                <th>Date</th>
                <th>Show On</th>
            </tr>
        </thead>
        <tbody>
            @foreach($noticeboards as $index => $noticeboard)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>{{ $noticeboard->campus ?? 'N/A' }}</td>
                    <td>{{ $noticeboard->title }}</td>
                    <td>{{ $noticeboard->notice ? (strlen($noticeboard->notice) > 100 ? substr($noticeboard->notice, 0, 100) . '...' : $noticeboard->notice) : 'N/A' }}</td>
                    <td>{{ $noticeboard->date->format('d M Y') }}</td>
                    <td>{{ $noticeboard->show_on ? str_replace(',', ', ', $noticeboard->show_on) : 'N/A' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>

