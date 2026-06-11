<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Blank Admission Form</title>
    <style>
        @page { size: A4; margin: 10mm; }
        * { box-sizing: border-box; }
        body { font-family: Arial, sans-serif; background: #fff; color: #111827; padding: 16px; }
        :root { --theme-blue: #003471; }
        .doc-header { border-bottom: 3px solid var(--theme-blue); padding-bottom: 10px; text-align: center; margin-bottom: 16px; }
        .school-name { font-size: 18px; font-weight: 800; color: var(--theme-blue); }
        .school-details { font-size: 11px; color: #374151; margin-top: 4px; }
        .top-bar { display: flex; justify-content: flex-end; margin-bottom: 10px; }
        .print-btn { border: 1px solid var(--theme-blue); background: var(--theme-blue); color: #fff; padding: 6px 12px; cursor: pointer; }
        @media print { .no-print { display: none !important; } }
        .form-container {
            border: 2px solid var(--theme-blue);
            padding: 24px;
            max-width: 800px;
            margin: 0 auto;
        }
        .form-header { text-align: center; margin-bottom: 24px; border-bottom: 2px solid var(--theme-blue); padding-bottom: 12px; }
        .form-header h2 { margin: 0; color: var(--theme-blue); font-size: 22px; }
        .form-section { margin-bottom: 22px; }
        .form-section-title { font-weight: bold; font-size: 15px; margin-bottom: 12px; border-bottom: 1px solid #ccc; padding-bottom: 4px; }
        .form-row { display: flex; margin-bottom: 12px; flex-wrap: wrap; }
        .form-group { flex: 1; margin-right: 12px; min-width: 140px; }
        .form-group:last-child { margin-right: 0; }
        .form-label { display: block; margin-bottom: 4px; font-weight: 500; font-size: 12px; }
        .form-field { border-bottom: 1px solid #000; min-height: 22px; padding: 4px 0; }
        .form-field-full { width: 100%; border-bottom: 1px solid #000; min-height: 22px; padding: 4px 0; }
        .signature-section { margin-top: 32px; display: flex; justify-content: space-between; gap: 16px; }
        .signature-box { flex: 1; text-align: center; }
        .signature-line { border-top: 1px solid #000; margin-top: 48px; padding-top: 4px; font-size: 12px; }
    </style>
</head>
<body>
<div class="doc-header">
    <div class="school-name">{{ $settings->school_name ?? 'School Name' }}</div>
    <div class="school-details">
        {{ $settings->address ?? '' }}
        @if(!empty($settings->school_phone)) | {{ $settings->school_phone }} @endif
    </div>
</div>
<div class="top-bar no-print">
    <button type="button" class="print-btn" onclick="window.print()">Print</button>
</div>

<div class="form-container">
    <div class="form-header">
        <h2>ADMISSION FORM</h2>
        <p style="margin: 6px 0 0 0; font-size: 12px; color: #374151;">{{ $printedAt }}</p>
    </div>

    <div class="form-section">
        <div class="form-section-title">Student Information</div>
        <div class="form-row">
            <div class="form-group">
                <span class="form-label">Student Name:</span>
                <div class="form-field"></div>
            </div>
            <div class="form-group">
                <span class="form-label">Student Code:</span>
                <div class="form-field"></div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <span class="form-label">Date of Birth:</span>
                <div class="form-field"></div>
            </div>
            <div class="form-group">
                <span class="form-label">Gender:</span>
                <div class="form-field"></div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <span class="form-label">Class:</span>
                <div class="form-field"></div>
            </div>
            <div class="form-group">
                <span class="form-label">Section:</span>
                <div class="form-field"></div>
            </div>
            <div class="form-group">
                <span class="form-label">Campus:</span>
                <div class="form-field"></div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group" style="flex: 1;">
                <span class="form-label">Address:</span>
                <div class="form-field-full"></div>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="form-section-title">Parent/Guardian Information</div>
        <div class="form-row">
            <div class="form-group">
                <span class="form-label">Father Name:</span>
                <div class="form-field"></div>
            </div>
            <div class="form-group">
                <span class="form-label">Father Phone:</span>
                <div class="form-field"></div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <span class="form-label">Mother Name:</span>
                <div class="form-field"></div>
            </div>
            <div class="form-group">
                <span class="form-label">Mother Phone:</span>
                <div class="form-field"></div>
            </div>
        </div>
        <div class="form-row">
            <div class="form-group">
                <span class="form-label">Guardian Name:</span>
                <div class="form-field"></div>
            </div>
            <div class="form-group">
                <span class="form-label">Guardian Phone:</span>
                <div class="form-field"></div>
            </div>
        </div>
    </div>

    <div class="form-section">
        <div class="form-section-title">Admission Details</div>
        <div class="form-row">
            <div class="form-group">
                <span class="form-label">Admission Date:</span>
                <div class="form-field"></div>
            </div>
            <div class="form-group">
                <span class="form-label">Session:</span>
                <div class="form-field"></div>
            </div>
        </div>
    </div>

    <div class="signature-section">
        <div class="signature-box">
            <div class="signature-line"><strong>Parent/Guardian Signature</strong></div>
        </div>
        <div class="signature-box">
            <div class="signature-line"><strong>Principal Signature</strong></div>
        </div>
    </div>
</div>

@if(request()->get('auto_print'))
<script>
window.addEventListener('load', function () {
    setTimeout(function () { window.print(); }, 300);
});
</script>
@endif
</body>
</html>
