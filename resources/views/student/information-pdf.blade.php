<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Students List - PDF Export</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
        }
        h2 {
            color: #003471;
            text-align: center;
            margin-bottom: 20px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 20px;
        }
        th, td {
            border: 1px solid #ddd;
            padding: 8px;
            text-align: left;
        }
        th {
            background-color: #003471;
            color: white;
            font-weight: bold;
        }
        tr:nth-child(even) {
            background-color: #f2f2f2;
        }
        .badge {
            padding: 3px 8px;
            border-radius: 3px;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <h2>Students List</h2>
    
    <table>
        <thead>
            <tr>
                <th>#</th>
                <th>Student Name</th>
                <th>Student Code</th>
                <th>Father Name</th>
                <th>Phone</th>
                <th>Class</th>
                <th>Section</th>
                <th>Gender</th>
                <th>Date of Birth</th>
                <th>Admission Date</th>
                <th>Campus</th>
            </tr>
        </thead>
        <tbody>
            @forelse($students as $index => $student)
                <tr>
                    <td>{{ $index + 1 }}</td>
                    <td>
                        <strong>{{ $student->student_name }}</strong>
                        @if($student->surname_caste)
                            ({{ $student->surname_caste }})
                        @endif
                    </td>
                    <td>{{ $student->student_code ?? 'N/A' }}</td>
                    <td>{{ $student->father_name ?? 'N/A' }}</td>
                    <td>{{ $student->father_phone ?? $student->whatsapp_number ?? 'N/A' }}</td>
                    <td>{{ $student->class ?? 'N/A' }}</td>
                    <td>{{ $student->section ?? 'N/A' }}</td>
                    <td>{{ ucfirst($student->gender ?? 'N/A') }}</td>
                    <td>{{ $student->date_of_birth ? $student->date_of_birth->format('d M Y') : 'N/A' }}</td>
                    <td>{{ $student->admission_date ? $student->admission_date->format('d M Y') : 'N/A' }}</td>
                    <td>{{ $student->campus ?? 'N/A' }}</td>
                </tr>
            @empty
                <tr>
                    <td colspan="11" class="text-center">No students found.</td>
                </tr>
            @endforelse
        </tbody>
    </table>
    
    <div style="margin-top: 20px; text-align: center; color: #666; font-size: 12px;">
        <p>Generated on: {{ date('d M Y, h:i A') }}</p>
        <p>Total Records: {{ $students->count() }}</p>
    </div>
</body>
</html>

