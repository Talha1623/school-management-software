<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Blank Tabulation Sheet</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        @media print {
            @page {
                margin: 1cm;
            }
            body {
                padding: 0 !important;
                margin: 0 !important;
            }
        }
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 20px;
        }
        .header h3 {
            margin: 0;
            color: #003471;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        table th,
        table td {
            border: 1px solid #000;
            padding: 8px;
            text-align: left;
        }
        table th {
            background-color: #f0f0f0;
            font-weight: bold;
        }
    </style>
</head>
<body>
    <div class="header">
        <h3>Tabulation Sheet</h3>
        <p class="mb-0">Date: _______________</p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">Sr.</th>
                <th style="width: 15%;">Student Code</th>
                <th style="width: 20%;">Student Name</th>
                <th style="width: 15%;">Class</th>
                <th style="width: 10%;">Section</th>
                <th style="width: 15%;">Marks</th>
                <th style="width: 10%;">Grade</th>
                <th style="width: 10%;">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @for($i = 1; $i <= 30; $i++)
            <tr>
                <td>{{ $i }}</td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
                <td></td>
            </tr>
            @endfor
        </tbody>
    </table>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
