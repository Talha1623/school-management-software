<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Student Details Print</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            color: #212529;
            margin: 20px;
        }
        .header {
            text-align: center;
            margin-bottom: 16px;
        }
        .header h2 {
            margin: 0;
            color: #003471;
        }
        .meta {
            font-size: 12px;
            color: #6c757d;
        }
        .section {
            margin-top: 16px;
            border: 1px solid #dee2e6;
            border-radius: 8px;
            padding: 12px;
        }
        .section h4 {
            margin: 0 0 8px 0;
            color: #003471;
            font-size: 14px;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            font-size: 12px;
        }
        td {
            padding: 6px 8px;
            border-bottom: 1px solid #f1f1f1;
            vertical-align: top;
        }
        td.label {
            width: 180px;
            color: #495057;
            font-weight: 600;
        }
        .photo {
            text-align: right;
        }
        .photo img {
            width: 120px;
            height: 140px;
            object-fit: cover;
            border: 1px solid #dee2e6;
            border-radius: 6px;
        }
        .no-print {
            margin-bottom: 12px;
            text-align: right;
        }
        @media print {
            .no-print {
                display: none;
            }
            body {
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="no-print">
        <button type="button" onclick="window.print()">Print</button>
    </div>

    <div class="header">
        <h2>Student Details</h2>
        <div class="meta">Printed at: {{ $printedAt }}</div>
    </div>

    <div class="section">
        <h4>Student Information</h4>
        <table>
            <tr>
                <td class="label">Student Name</td>
                <td>{{ $student->student_name ?? 'N/A' }}</td>
                <td class="photo" rowspan="6">
                    @if(!empty($student->photo))
                        <img src="{{ asset('storage/' . $student->photo) }}" alt="Student Photo">
                    @else
                        <div style="width: 120px; height: 140px; border: 1px solid #dee2e6; border-radius: 6px; display: inline-flex; align-items: center; justify-content: center; color: #6c757d; font-size: 11px;">
                            No Photo
                        </div>
                    @endif
                </td>
            </tr>
            <tr>
                <td class="label">Student Code</td>
                <td>{{ $student->student_code ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">G.R Number</td>
                <td>{{ $student->gr_number ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Gender</td>
                <td>{{ ucfirst($student->gender ?? 'N/A') }}</td>
            </tr>
            <tr>
                <td class="label">Date of Birth</td>
                <td>{{ $student->date_of_birth ? $student->date_of_birth->format('d-m-Y') : 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Place of Birth</td>
                <td>{{ $student->place_of_birth ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h4>Academic Information</h4>
        <table>
            <tr>
                <td class="label">Campus</td>
                <td>{{ $student->campus ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Class</td>
                <td>{{ $student->class ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Section</td>
                <td>{{ $student->section ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Admission Date</td>
                <td>{{ $student->admission_date ? $student->admission_date->format('d-m-Y') : 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Previous School</td>
                <td>{{ $student->previous_school ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h4>Parent Information</h4>
        <table>
            <tr>
                <td class="label">Father Name</td>
                <td>{{ $student->father_name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Father ID Card</td>
                <td>{{ $student->father_id_card ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Father Phone</td>
                <td>{{ $student->father_phone ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Mother Phone</td>
                <td>{{ $student->mother_phone ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">WhatsApp Number</td>
                <td>{{ $student->whatsapp_number ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Email</td>
                <td>{{ $student->father_email ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Home Address</td>
                <td>{{ $student->home_address ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h4>Fee & Transport</h4>
        <table>
            <tr>
                <td class="label">Monthly Fee</td>
                <td>{{ $student->monthly_fee !== null ? number_format((float) $student->monthly_fee, 2) : 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Discounted Student</td>
                <td>{{ $student->discounted_student ? 'Yes' : 'No' }}</td>
            </tr>
            <tr>
                <td class="label">Transport Route</td>
                <td>{{ $student->transport_route ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Transport Fare</td>
                <td>{{ $student->transport_fare !== null ? number_format((float) $student->transport_fare, 2) : 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <div class="section">
        <h4>Other Details</h4>
        <table>
            <tr>
                <td class="label">B-Form Number</td>
                <td>{{ $student->b_form_number ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Religion</td>
                <td>{{ $student->religion ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Reference / Remarks</td>
                <td>{{ $student->reference_remarks ?? 'N/A' }}</td>
            </tr>
        </table>
    </div>

    <script>
        window.addEventListener('load', function() {
            window.print();
        });
    </script>
</body>
</html>
