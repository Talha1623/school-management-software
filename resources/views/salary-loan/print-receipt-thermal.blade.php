<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Salary Payment Receipt - {{ $salary->staff->name ?? 'N/A' }}</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <style>
        @media print {
            @page {
                size: 80mm auto;
                margin: 0;
            }
            body {
                margin: 0;
                padding: 5mm;
            }
            .no-print {
                display: none !important;
            }
        }

        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: monospace;
            font-size: 11px;
            width: 80mm;
            margin: 0 auto;
            padding: 5mm;
            background: #fff;
            color: #000;
        }

        .receipt {
            width: 100%;
        }

        .center {
            text-align: center;
        }

        .bold {
            font-weight: bold;
        }

        .divider {
            border-top: 1px dashed #000;
            margin: 6px 0;
        }

        .header {
            text-align: center;
            margin-bottom: 6px;
        }

        .school-name {
            font-size: 15px;
            font-weight: bold;
            text-transform: uppercase;
            margin-bottom: 3px;
        }

        .receipt-title {
            font-size: 12px;
            font-weight: bold;
            margin-top: 3px;
        }

        .info-table {
            width: 100%;
            margin-top: 6px;
            font-size: 10px;
        }

        .info-table td {
            padding: 2px 0;
            vertical-align: top;
        }

        .label {
            font-weight: bold;
            width: 45%;
        }

        .value {
            width: 55%;
        }

        .section-title {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            margin: 8px 0 4px;
            text-transform: uppercase;
        }

        .flex-row {
            display: flex;
            justify-content: space-between;
            margin-top: 2px;
            font-size: 10px;
        }

        .total-box {
            border-top: 2px solid #000;
            margin-top: 6px;
            padding-top: 4px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
            font-size: 12px;
        }

        .footer {
            text-align: center;
            margin-top: 10px;
            font-size: 9px;
        }

        .thank-you {
            font-weight: bold;
            margin-top: 6px;
        }

        .print-btn {
            position: fixed;
            bottom: 20px;
            right: 20px;
            z-index: 1000;
        }

        .btn-print {
            background: #003471;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 12px;
        }
    </style>
</head>
<body>
    <div class="receipt">
        <div class="header">
            <div class="school-name">ROYAL GRAMMAR SCHOOL</div>
            <div class="receipt-title">SALARY PAYMENT RECEIPT</div>
        </div>

        <div class="divider"></div>

        <table class="info-table">
            <tr>
                <td class="label">Receipt #:</td>
                <td class="value">SAL-{{ str_pad($salary->id, 6, '0', STR_PAD_LEFT) }}</td>
            </tr>
            <tr>
                <td class="label">Date:</td>
                <td class="value">{{ $salary->updated_at->format('d-m-Y H:i') }}</td>
            </tr>
            <tr>
                <td class="label">Employee ID:</td>
                <td class="value">{{ $salary->staff->emp_id ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Name:</td>
                <td class="value">{{ $salary->staff->name ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Designation:</td>
                <td class="value">{{ $salary->staff->designation ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Campus:</td>
                <td class="value">{{ $salary->staff->campus ?? 'N/A' }}</td>
            </tr>
            <tr>
                <td class="label">Month:</td>
                <td class="value">{{ $salary->salary_month }} {{ $salary->year }}</td>
            </tr>
        </table>

        <div class="divider"></div>

        <div class="section-title">Salary Details</div>

        @php
            $salaryType = strtolower(trim($salary->staff->salary_type ?? ''));
            $isPerHour = $salaryType === 'per hour';
            $isPerLecture = $salaryType === 'lecture';
            $isFullTime = !$isPerHour && !$isPerLecture;
        @endphp

        @if($isPerHour && isset($attendanceSummary['total_minutes']))
            @php
                $totalHours = round($attendanceSummary['total_minutes'] / 60, 2);
                $totalClasses = $attendanceSummary['present'] ?? 0;
            @endphp
            <div class="flex-row">
                <span>Type:</span>
                <span>Per Hour</span>
            </div>
            <div class="flex-row">
                <span>Total Hours:</span>
                <span>{{ number_format($totalHours, 2) }} hrs</span>
            </div>
            <div class="flex-row">
                <span>Total Classes:</span>
                <span>{{ $totalClasses }}</span>
            </div>
        @elseif($isPerLecture && isset($attendanceSummary['total_lectures']))
            <div class="flex-row">
                <span>Type:</span>
                <span>Per Lecture</span>
            </div>
            <div class="flex-row">
                <span>Total Lectures:</span>
                <span>{{ $attendanceSummary['total_lectures'] }}</span>
            </div>
        @else
            <div class="flex-row">
                <span>Type:</span>
                <span>Full Time</span>
            </div>
            <div class="flex-row">
                <span>Present Days:</span>
                <span>{{ $salary->present ?? 0 }}</span>
            </div>
            <div class="flex-row">
                <span>Absent Days:</span>
                <span>{{ $salary->absent ?? 0 }}</span>
            </div>
        @endif

        <div class="flex-row">
            <span>Basic Salary:</span>
            <span>₹{{ number_format($salary->basic ?? 0, 2) }}</span>
        </div>

        @if(($salary->bonus_amount ?? 0) > 0)
        <div class="flex-row">
            <span>Bonus:</span>
            <span>+ ₹{{ number_format($salary->bonus_amount ?? 0, 2) }}</span>
        </div>
        @endif

        @if(($salary->deduction_amount ?? 0) > 0)
        <div class="flex-row">
            <span>Deduction:</span>
            <span>- ₹{{ number_format($salary->deduction_amount ?? 0, 2) }}</span>
        </div>
        @endif

        @if($salary->loan_repayment > 0)
        <div class="flex-row">
            <span>Loan Repayment:</span>
            <span>- ₹{{ number_format($salary->loan_repayment ?? 0, 2) }}</span>
        </div>
        @endif

        <div class="flex-row" style="border-top: 1px dashed #000; margin-top: 4px; padding-top: 4px; font-weight: bold;">
            <span>Salary Generated:</span>
            <span>₹{{ number_format($salary->salary_generated ?? 0, 2) }}</span>
        </div>

        <div class="divider"></div>

        <div class="footer">
            <div class="thank-you">Thank You!</div>
            <div style="margin-top: 4px;">This is a computer generated receipt.</div>
        </div>

        <div class="divider"></div>

        <div class="total-box">
            <span>Amount Paid:</span>
            <span>₹{{ number_format($salary->amount_paid ?? 0, 2) }}</span>
        </div>
    </div>

    <div class="print-btn no-print">
        <button class="btn-print" onclick="window.print()">Print</button>
    </div>

    <script>
        // Auto print when page loads
        window.onload = function() {
            setTimeout(function() {
                window.print();
            }, 500);
        };
    </script>
</body>
</html>
