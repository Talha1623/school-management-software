<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $title }}</title>
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
        .group-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin: 14px 0 6px 0;
            font-weight: 600;
            color: #003471;
        }
        .group-count {
            font-size: 12px;
            color: #666;
            font-weight: 400;
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
            <h1 class="title">{{ $title }}</h1>
            @if(!empty($subtitle))
                <p class="subtitle">{{ $subtitle }}</p>
            @endif
        </div>
        <div>
            <button type="button" class="print-btn no-print" onclick="window.print()">Print</button>
            <div class="printed-at">Printed: {{ $printedAt }}</div>
        </div>
    </div>

    @if($grouped)
        @forelse($groupedStudents as $groupName => $groupStudents)
            <div class="group-header">
                <div>{{ $groupName ?: 'N/A' }}</div>
                <div class="group-count">
                    {{ $groupStudents->count() }} {{ Str::plural('student', $groupStudents->count()) }}
                </div>
            </div>
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Student Name</th>
                        <th>Student Code</th>
                        <th>Father Name</th>
                        <th>Phone</th>
                        <th>Campus</th>
                        <th>Class</th>
                        <th>Section</th>
                        <th>Gender</th>
                        <th>Admission Date</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($groupStudents as $index => $student)
                        <tr>
                            <td>{{ $index + 1 }}</td>
                            <td>{{ $student->student_name ?? 'N/A' }}</td>
                            <td>{{ $student->student_code ?? 'N/A' }}</td>
                            <td>{{ $student->father_name ?? 'N/A' }}</td>
                            <td>{{ $student->father_phone ?? $student->whatsapp_number ?? 'N/A' }}</td>
                            <td>{{ $student->campus ?? 'N/A' }}</td>
                            <td>{{ $student->class ?? 'N/A' }}</td>
                            <td>{{ $student->section ?? 'N/A' }}</td>
                            <td>{{ ucfirst($student->gender ?? 'N/A') }}</td>
                            <td>{{ $student->admission_date ? $student->admission_date->format('d M Y') : 'N/A' }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        @empty
            <div class="empty">No students found.</div>
        @endforelse
    @else
        <table>
            <thead>
                <tr>
                    <th>#</th>
                    <th>Student Name</th>
                    <th>Student Code</th>
                    <th>Father Name</th>
                    <th>Phone</th>
                    <th>Campus</th>
                    <th>Class</th>
                    <th>Section</th>
                    <th>Gender</th>
                    <th>Admission Date</th>
                </tr>
            </thead>
            <tbody>
                @forelse($students as $index => $student)
                    <tr>
                        <td>{{ $index + 1 }}</td>
                        <td>{{ $student->student_name ?? 'N/A' }}</td>
                        <td>{{ $student->student_code ?? 'N/A' }}</td>
                        <td>{{ $student->father_name ?? 'N/A' }}</td>
                        <td>{{ $student->father_phone ?? $student->whatsapp_number ?? 'N/A' }}</td>
                        <td>{{ $student->campus ?? 'N/A' }}</td>
                        <td>{{ $student->class ?? 'N/A' }}</td>
                        <td>{{ $student->section ?? 'N/A' }}</td>
                        <td>{{ ucfirst($student->gender ?? 'N/A') }}</td>
                        <td>{{ $student->admission_date ? $student->admission_date->format('d M Y') : 'N/A' }}</td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="empty">No students found.</td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    @endif

    @if(request()->get('auto_print'))
        <script>
            window.addEventListener('load', function() {
                window.print();
            });
        </script>
    @endif
</body>
</html>
