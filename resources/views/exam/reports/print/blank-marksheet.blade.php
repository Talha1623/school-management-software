<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Blank Marksheet</title>
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
            border-bottom: 2px solid #003471;
            padding-bottom: 15px;
        }
        .header h3 {
            margin: 0;
            color: #003471;
        }
        .student-info {
            margin-bottom: 20px;
        }
        .student-info p {
            margin: 5px 0;
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
            text-align: center;
        }
        .summary-section {
            margin-top: 20px;
            border-top: 2px solid #000;
            padding-top: 15px;
        }
    </style>
</head>
<body>
    <div class="header">
        <h3>Marksheet</h3>
        <p class="mb-0">Exam: _______________ | Date: _______________</p>
    </div>

    <div class="student-info">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Student Name:</strong> _______________</p>
                <p><strong>Student Code:</strong> _______________</p>
                <p><strong>Class:</strong> _______________</p>
            </div>
            <div class="col-md-6">
                <p><strong>Section:</strong> _______________</p>
                <p><strong>Parent Name:</strong> _______________</p>
                <p><strong>Campus:</strong> _______________</p>
            </div>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 5%;">Sr.</th>
                <th style="width: 25%;">Subject</th>
                <th style="width: 12%;">Total Marks</th>
                <th style="width: 12%;">Passing Marks</th>
                <th style="width: 12%;">Obtained Marks</th>
                <th style="width: 10%;">Percentage</th>
                <th style="width: 10%;">Grade</th>
                <th style="width: 14%;">Remarks</th>
            </tr>
        </thead>
        <tbody>
            @for($i = 1; $i <= 10; $i++)
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

    <div class="summary-section">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Total Marks:</strong> _______________</p>
                <p><strong>Obtained Marks:</strong> _______________</p>
                <p><strong>Percentage:</strong> _______________</p>
            </div>
            <div class="col-md-6">
                <p><strong>Grade:</strong> _______________</p>
                <p><strong>Status:</strong> _______________</p>
                <p><strong>Rank:</strong> _______________</p>
            </div>
        </div>
    </div>

    <div style="margin-top: 40px;">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Class Teacher Remarks:</strong></p>
                <div style="border: 1px solid #000; min-height: 60px; padding: 10px;"></div>
            </div>
            <div class="col-md-6 text-end">
                <p><strong>Signature:</strong></p>
                <div style="border-top: 2px solid #000; width: 200px; margin-left: auto; margin-top: 40px;"></div>
            </div>
        </div>
    </div>

    <script>
        window.onload = function() {
            window.print();
        };
    </script>
</body>
</html>
