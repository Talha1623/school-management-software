<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Fee Vouchers — Print</title>
    @php
        $type = $type ?? 'three_copies';
        $copyCount = max(1, count($copyLabels ?? ['Bank Copy']));
        $isThermal = $type === 'thermal_copies';
        $logoUrl = !empty($settings->logo)
            ? asset('storage/' . $settings->logo)
            : asset('assets/images/logo-icon.png');
        $displaySchool = trim((string) ($settings->school_name ?? ''))
            ?: trim((string) ($settings->system_name ?? ''))
            ?: 'Education Management System';
        $notice = trim((string) ($settings->fee_voucher_notice ?? ''));
        if ($notice === '') {
            $notice = 'This is a computer generated fee voucher';
        }
        $feeHistoryMonthOrder = ['December', 'November', 'October', 'September', 'August', 'July', 'June', 'May', 'April', 'March', 'February', 'January'];
        $formatMoney = fn ($amount, int $decimals = 2) => $settings->formatCurrency($amount, $decimals);
    @endphp
    <style>
        * { box-sizing: border-box; }
        body {
            margin: 0;
            padding: 12px;
            font-family: Arial, Helvetica, sans-serif;
            font-size: 10px;
            color: #111;
            background: #e5e7eb;
        }
        .toolbar {
            max-width: 1200px;
            margin: 0 auto 12px;
            display: flex;
            justify-content: flex-end;
            gap: 8px;
        }
        .toolbar button {
            padding: 6px 14px;
            font-size: 13px;
            cursor: pointer;
            background: #003471;
            color: #fff;
            border: 1px solid #003471;
            border-radius: 3px;
        }
        .toolbar button:hover { background: #0b4a89; }

        .print-root {
            max-width: 1200px;
            margin: 0 auto;
            background: #fff;
            padding: 12px;
        }

        .student-voucher-sheet {
            display: grid;
            gap: 10px;
            margin-bottom: 16px;
        }

        .print-root:not(.thermal) .student-voucher-sheet {
            grid-template-columns: repeat({{ $copyCount }}, minmax(0, 1fr));
        }
        .print-root.thermal .student-voucher-sheet {
            grid-template-columns: minmax(0, 1fr);
            max-width: 360px;
            margin-left: auto;
            margin-right: auto;
        }

        .voucher-copy {
            border: 1px solid #9ca3af;
            padding: 8px 6px;
            min-width: 0;
        }

        .copy-banner {
            text-align: center;
            font-weight: 700;
            font-size: 11px;
            border: 2px solid #dc2626;
            color: #b91c1c;
            padding: 4px 6px;
            margin-bottom: 8px;
            letter-spacing: 0.04em;
        }

        .logo-wrap { text-align: center; margin-bottom: 4px; }
        .logo-wrap img {
            max-height: 48px;
            max-width: 100%;
            width: auto;
            height: auto;
            object-fit: contain;
        }

        .school-title {
            text-align: center;
            font-weight: 700;
            font-size: 11px;
            margin: 0 0 8px;
            line-height: 1.25;
            text-transform: uppercase;
        }

        .kv { margin: 0 0 3px; font-size: 9px; line-height: 1.35; word-wrap: break-word; }
        .kv strong { font-weight: 700; }

        .voucher-no-box {
            text-align: center;
            font-size: 14px;
            font-weight: 700;
            border: 2px solid #111;
            padding: 6px 4px;
            margin: 8px 0;
            letter-spacing: 0.05em;
        }

        .bank-block {
            font-size: 8.5px;
            margin: 6px 0;
            line-height: 1.35;
            word-wrap: break-word;
        }

        table.fee-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
            table-layout: fixed;
        }
        table.fee-table th,
        table.fee-table td {
            border: 1px solid #6b7280;
            padding: 3px 4px;
            vertical-align: top;
            word-wrap: break-word;
        }
        table.fee-table th {
            background: #1d4ed8;
            color: #fff;
            font-weight: 700;
        }
        table.fee-table td:last-child { text-align: right; white-space: nowrap; }

        .totals-row td { font-weight: 700; }

        .summary-line { margin-top: 6px; font-size: 9px; }
        .summary-line strong { font-weight: 700; }

        .history-title {
            margin: 8px 0 4px;
            font-size: 9px;
            font-weight: 700;
        }

        table.history-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7px;
            table-layout: fixed;
        }
        table.history-table th,
        table.history-table td {
            border: 1px solid #9ca3af;
            padding: 2px 1px;
            text-align: center;
            word-wrap: break-word;
        }
        table.history-table th:first-child,
        table.history-table td:first-child {
            text-align: left;
            font-weight: 700;
            width: 22%;
        }

        .footer-note {
            margin-top: 8px;
            font-size: 7.5px;
            font-style: italic;
            color: #374151;
        }

        @if ($isThermal)
        @@page { size: A4 portrait; margin: 8mm; }
        @else
        @@page { size: A4 landscape; margin: 6mm; }
        @endif

        @media print {
            body {
                background: #fff;
                padding: 0;
            }
            .toolbar { display: none !important; }
            .print-root {
                max-width: none;
                padding: 0;
                margin: 0;
            }
            .student-voucher-sheet {
                margin-bottom: 0;
            }
            .voucher-copy {
                border-color: #6b7280;
                break-inside: avoid;
                page-break-inside: avoid;
            }

            .logo-wrap img { max-height: 56px; }
        }
    </style>
</head>
<body>
    <div class="toolbar no-print">
        <button type="button" onclick="window.print()">Print</button>
    </div>

    <div class="print-root {{ $isThermal ? 'thermal' : '' }}">
        @forelse ($vouchers ?? [] as $v)
            @php
                $student = $v['student'] ?? null;
                $yearLabel = $v['year_label'] ?? (($settings->running_session ?? '') !== '' ? $settings->running_session : (string) ($v['year'] ?? ($currentYear ?? date('Y'))));
            @endphp
            <section class="student-voucher-sheet">
                @foreach ($copyLabels as $copyLabel)
                    <article class="voucher-copy">
                        <div class="copy-banner">{{ $copyLabel }}</div>

                        <div class="logo-wrap">
                            <img src="{{ $logoUrl }}" alt="School Logo">
                        </div>
                        <p class="school-title">{{ $displaySchool }}</p>

                        @if(!empty($v['is_family']))
                            <p class="kv"><strong>NAME:</strong> {{ $v['family_label']['names'] ?? '—' }}</p>
                            <p class="kv"><strong>PARENT:</strong> {{ $v['family_label']['parent'] ?? '—' }}</p>
                            <p class="kv"><strong>CLASS/SEC:</strong> {{ $v['family_label']['classes'] ?? '—' }}</p>
                            <p class="kv"><strong>ROLL NO:</strong> {{ $v['family_label']['roll_nos'] ?? '—' }}</p>
                            <p class="kv"><strong>CAMPUS:</strong> {{ $v['family_label']['campus'] ?? '—' }}</p>
                        @else
                            <p class="kv"><strong>NAME:</strong> {{ $student->student_name ?? '—' }}</p>
                            <p class="kv"><strong>PARENT:</strong> {{ $student->father_name ?? '—' }}</p>
                            <p class="kv"><strong>CLASS/SEC:</strong> {{ trim(($student->class ?? '') . '/' . ($student->section ?? ''), '/') }}</p>
                            <p class="kv"><strong>ROLL NO:</strong> {{ $student->student_code ?? '—' }}</p>
                            <p class="kv"><strong>CAMPUS:</strong> {{ $student->campus ?? '—' }}</p>
                        @endif
                        <p class="kv"><strong>VOUCHER:</strong> {{ $v['voucher_number'] ?? '—' }}</p>

                        <div class="voucher-no-box">{{ $v['voucher_number'] ?? '—' }}</div>

                        <div class="bank-block">
                            <div><strong>Account Title:</strong> {{ $settings->fee_voucher_account_title ?? '—' }}</div>
                            <div><strong>Account Number:</strong> {{ $settings->fee_voucher_account_number ?? '—' }}</div>
                            <div><strong>IBAN:</strong> {{ $settings->fee_voucher_iban ?? '—' }}</div>
                            <div><strong>Bank Name:</strong> {{ $settings->fee_voucher_bank_name ?? '—' }}</div>
                        </div>

                        <table class="fee-table">
                            <thead>
                                <tr>
                                    <th style="width:72%">FEE DESCRIPTION</th>
                                    <th style="width:28%">AMOUNT</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach ($v['pending_fees'] ?? [] as $fee)
                                    @php $amt = (float) ($fee['amount'] ?? 0); @endphp
                                    <tr>
                                        <td>{{ $fee['description'] ?? '' }}</td>
                                        <td>{{ $formatMoney($amt, 2) }}</td>
                                    </tr>
                                @endforeach
                                <tr class="totals-row">
                                    <td><strong>CURRENT FEES SUBTOTAL</strong></td>
                                    <td><strong>{{ $formatMoney((float) ($v['current_fees_subtotal'] ?? 0), 0) }}</strong></td>
                                </tr>
                                <tr class="totals-row">
                                    <td><strong>SUBTOTAL</strong></td>
                                    <td><strong>{{ $formatMoney((float) ($v['subtotal'] ?? 0), 0) }}</strong></td>
                                </tr>
                                <tr>
                                    <td>LATE FEE (PREVIOUS DUES)</td>
                                    <td>{{ $formatMoney((float) ($v['late_fee'] ?? 0), 0) }}</td>
                                </tr>
                                <tr class="totals-row">
                                    <td><strong>TOTAL:</strong></td>
                                    <td><strong>{{ $formatMoney((float) ($v['total'] ?? 0), 0) }}</strong></td>
                                </tr>
                                <tr class="totals-row">
                                    <td><strong>AFTER DUE DATE:</strong></td>
                                    <td><strong>{{ $formatMoney((float) ($v['after_due_date'] ?? 0), 0) }}</strong></td>
                                </tr>
                            </tbody>
                        </table>

                        <div class="summary-line">
                            <strong>Total Amount</strong> {{ $formatMoney((float) ($v['total'] ?? 0), 0) }}
                        </div>
                        <div class="summary-line">
                            <strong>Voucher Validity:</strong>
                            @if(!empty($v['voucher_validity']))
                                {{ $v['voucher_validity'] instanceof \Carbon\Carbon ? $v['voucher_validity']->format('d/m/Y') : \Carbon\Carbon::parse($v['voucher_validity'])->format('d/m/Y') }}
                            @else
                                —
                            @endif
                        </div>
                        <div class="summary-line">
                            <strong>Due Date:</strong>
                            @if(!empty($v['due_date']))
                                {{ $v['due_date'] instanceof \Carbon\Carbon ? $v['due_date']->format('d/m/Y') : \Carbon\Carbon::parse($v['due_date'])->format('d/m/Y') }}
                            @else
                                —
                            @endif
                        </div>

                        <div class="history-title">{{ !empty($v['is_family']) ? 'FAMILY FEE HISTORY' : 'STUDENT FEE HISTORY' }} — Year {{ $yearLabel }}</div>
                        <table class="history-table">
                            <thead>
                                <tr>
                                    <th>Month</th>
                                    @foreach ($feeHistoryMonthOrder as $m)
                                        <th>{{ substr($m, 0, 3) }}</th>
                                    @endforeach
                                </tr>
                            </thead>
                            <tbody>
                                <tr>
                                    <td>Total</td>
                                    @foreach ($feeHistoryMonthOrder as $m)
                                        @php $h = ($v['fee_history'] ?? [])[$m] ?? ['total' => 0, 'paid' => 0]; @endphp
                                        <td>{{ $formatMoney((float) ($h['total'] ?? 0), 0) }}</td>
                                    @endforeach
                                </tr>
                                <tr>
                                    <td>Paid</td>
                                    @foreach ($feeHistoryMonthOrder as $m)
                                        @php $h = ($v['fee_history'] ?? [])[$m] ?? ['total' => 0, 'paid' => 0]; @endphp
                                        <td>{{ $formatMoney((float) ($h['paid'] ?? 0), 0) }}</td>
                                    @endforeach
                                </tr>
                            </tbody>
                        </table>

                        <p class="footer-note"><strong>NOTICE:</strong> * {{ $notice }}</p>
                    </article>
                @endforeach
            </section>
        @empty
            <p style="padding:16px;text-align:center;">No fee vouchers to print for the current filters.</p>
        @endforelse
    </div>
</body>
</html>
