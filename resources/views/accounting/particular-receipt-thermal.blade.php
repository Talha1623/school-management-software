<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Fee Receipt - {{ $studentCode ?? 'N/A' }}</title>
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

        .school-address {
            font-size: 9px;
            margin-bottom: 2px;
            line-height: 1.3;
        }

        .school-contact {
            font-size: 9px;
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
            width: 38%;
        }

        .section-title {
            text-align: center;
            font-weight: bold;
            font-size: 11px;
            margin: 8px 0 4px;
            text-transform: uppercase;
        }

        .item {
            margin: 4px 0;
            font-size: 10px;
        }

        .item-title {
            font-weight: bold;
            margin-bottom: 2px;
        }

        .flex-row {
            display: flex;
            justify-content: space-between;
            margin-top: 2px;
        }

        .small {
            font-size: 9px;
        }

        .total-box {
            border-top: 1px solid #000;
            margin-top: 6px;
            padding-top: 4px;
            font-weight: bold;
            display: flex;
            justify-content: space-between;
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
            top: 20px;
            right: 20px;
            padding: 8px 16px;
            background: #003471;
            color: #fff;
            border: none;
            border-radius: 4px;
            cursor: pointer;
            font-size: 13px;
        }

        .print-btn:hover {
            background: #004a9f;
        }

        .no-data {
            text-align: center;
            font-size: 10px;
            padding: 6px 0;
            font-style: italic;
        }
    </style>
</head>

<body onload="window.print()">

<button class="print-btn no-print" onclick="window.print()">Print</button>

<div class="receipt">

    <!-- HEADER -->
    <div class="header">
        <div class="school-name">{{ $schoolName }}</div>
        @if($schoolAddress)
        <div class="school-address">{{ $schoolAddress }}</div>
        @endif
        @if($schoolPhone || $schoolEmail)
        <div class="school-contact">
            @if($schoolPhone)
                Ph: {{ $schoolPhone }}
            @endif
            @if($schoolPhone && $schoolEmail)
                | 
            @endif
            @if($schoolEmail)
                Email: {{ $schoolEmail }}
            @endif
        </div>
        @endif
        <div class="receipt-title">Fee Receipt</div>
    </div>

    <div class="divider"></div>

    <!-- STUDENT INFO -->
    <table class="info-table">
        <tr>
            <td class="label">Student Code:</td>
            <td>{{ $studentCode ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Student Name:</td>
            <td>{{ $student->student_name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Father Name:</td>
            <td>{{ $student->father_name ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Class:</td>
            <td>
                @if($student && $student->class && $student->section)
                    {{ $student->class }}/{{ $student->section }}
                @elseif($student && $student->class)
                    {{ $student->class }}
                @else
                    N/A
                @endif
            </td>
        </tr>
        <tr>
            <td class="label">Campus:</td>
            <td>{{ $student->campus ?? 'N/A' }}</td>
        </tr>
        <tr>
            <td class="label">Generated:</td>
            <td>{{ now()->format('d-m-Y h:i A') }}</td>
        </tr>
    </table>

    <div class="divider"></div>

    <!-- PAID FEES -->
    @if($payments && count($payments) > 0)

        <div class="section-title">Paid Fees</div>

        @foreach($payments as $payment)
            <div class="item">
                <div class="item-title">{{ $payment->payment_title }}</div>

                <div class="flex-row small">
                    <span>Amount</span>
                    <span>{{ number_format($payment->payment_amount ?? 0, 2) }}</span>
                </div>

                @if($payment->late_fee > 0)
                <div class="flex-row small">
                    <span>Late Fee</span>
                    <span>{{ number_format($payment->late_fee, 2) }}</span>
                </div>
                @endif

                @if($payment->discount > 0)
                <div class="flex-row small">
                    <span>Discount</span>
                    <span>{{ number_format($payment->discount, 2) }}</span>
                </div>
                @endif

                <div class="small">
                    {{ $payment->payment_date ? $payment->payment_date->format('d-m-Y h:i A') : 'N/A' }}
                    @if($payment->accountant)
                        | By: {{ $payment->accountant }}
                    @endif
                </div>
            </div>
        @endforeach

        <div class="total-box">
            <span>Total Paid</span>
            <span>{{ number_format($totalPaid ?? 0, 2) }}</span>
        </div>

        @if($totalLate > 0)
        <div class="flex-row small bold">
            <span>Total Late Fee</span>
            <span>{{ number_format($totalLate, 2) }}</span>
        </div>
        @endif

        @if($totalDiscount > 0)
        <div class="flex-row small bold">
            <span>Total Discount</span>
            <span>{{ number_format($totalDiscount, 2) }}</span>
        </div>
        @endif

    @else
        <div class="no-data">No paid fees found.</div>
    @endif


    <!-- REMAINING FEES -->
    <div class="divider"></div>

    @if($pendingFees && count($pendingFees) > 0)

        <div class="section-title">Remaining Fees</div>

        @foreach($pendingFees as $fee)
            <div class="item">
                <div class="item-title">{{ $fee['title'] ?? 'N/A' }}</div>

                <div class="flex-row small">
                    <span>Amount</span>
                    <span>{{ number_format($fee['amount'] ?? 0, 2) }}</span>
                </div>

                @if(isset($fee['late_fee']) && $fee['late_fee'] > 0)
                <div class="flex-row small">
                    <span>Late Fee</span>
                    <span>{{ number_format($fee['late_fee'], 2) }}</span>
                </div>
                @endif

                <div class="flex-row small bold">
                    <span>Total Due</span>
                    <span>{{ number_format($fee['total'] ?? 0, 2) }}</span>
                </div>
            </div>
        @endforeach

        <div class="total-box">
            <span>Grand Total Due</span>
            <span>{{ number_format($totalDue ?? 0, 2) }}</span>
        </div>

    @else
        <div class="no-data">No remaining fees.</div>
    @endif


    <!-- FOOTER -->
    <div class="divider"></div>

    <div class="footer">
        <div class="thank-you">Thank You!</div>
        <div>System Generated Receipt</div>
    </div>

</div>

</body>
</html>
