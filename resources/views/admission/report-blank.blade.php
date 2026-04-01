@if($isPrint)
<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Blank Admission Form</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        :root { --theme-blue: #003471; }
        @media print {
            @page {
                margin: 1cm;
            }
            * {
                margin: 0;
                padding: 0;
            }
            html, body {
                width: 100% !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            body {
                padding: 20px !important;
                margin: 0 !important;
                background: white !important;
            }
            /* Hide sidebar and all navigation elements - comprehensive list */
            .sidebar-area,
            #sidebar-area,
            .sidebar,
            aside,
            #layout-menu,
            .layout-menu,
            .menu-vertical,
            .menu,
            .navbar,
            .navbar-area,
            .header-area,
            #header-area,
            header,
            .main-header,
            .header-navbar,
            .left-header-content,
            .right-header-content,
            .theme-settings-area,
            .theme-settings,
            .settings-btn,
            .preloader,
            .footer-area,
            footer,
            nav,
            .nav,
            .breadcrumb,
            .page-header,
            .card-header,
            .btn,
            .btn-sm,
            .btn-primary,
            .d-flex.justify-content-between,
            h4.mb-0,
            .src-form,
            .header-burger-menu,
            .sidebar-burger-menu,
            .sidebar-burger-menu-close,
            .container-fluid > .row:first-child,
            .summary-cards,
            .row.mb-3:first-child {
                display: none !important;
                visibility: hidden !important;
                width: 0 !important;
                height: 0 !important;
                overflow: hidden !important;
                position: absolute !important;
                left: -9999px !important;
                opacity: 0 !important;
            }
        }
        body {
            font-family: Arial, sans-serif;
            padding: 20px;
        }
        .form-container {
            border: 2px solid var(--theme-blue);
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
        }
        .form-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid var(--theme-blue);
            padding-bottom: 15px;
        }
        .form-header h2 {
            margin: 0;
            color: #003471;
            font-size: 24px;
        }
        .form-section {
            margin-bottom: 25px;
        }
        .form-section-title {
            font-weight: bold;
            font-size: 16px;
            margin-bottom: 15px;
            border-bottom: 1px solid #ccc;
            padding-bottom: 5px;
        }
        .form-row {
            display: flex;
            margin-bottom: 15px;
        }
        .form-group {
            flex: 1;
            margin-right: 15px;
        }
        .form-group:last-child {
            margin-right: 0;
        }
        .form-label {
            display: block;
            margin-bottom: 5px;
            font-weight: 500;
        }
        .form-field {
            border-bottom: 1px solid #000;
            min-height: 25px;
            padding: 5px 0;
        }
        .form-field-full {
            width: 100%;
            border-bottom: 1px solid #000;
            min-height: 25px;
            padding: 5px 0;
        }
        .signature-section {
            margin-top: 40px;
            display: flex;
            justify-content: space-between;
        }
        .signature-box {
            width: 200px;
            text-align: center;
        }
        .signature-line {
            border-top: 1px solid #000;
            margin-top: 50px;
            padding-top: 5px;
        }
    </style>
</head>
<body>
    <div class="form-container">
        <div class="form-header">
            <h2>ADMISSION FORM</h2>
            <p style="margin: 5px 0 0 0;">School Management System</p>
        </div>

        <div class="form-section">
            <div class="form-section-title">Student Information</div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Student Name:</label>
                    <div class="form-field"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Student Code:</label>
                    <div class="form-field"></div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Date of Birth:</label>
                    <div class="form-field"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Gender:</label>
                    <div class="form-field"></div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Class:</label>
                    <div class="form-field"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Section:</label>
                    <div class="form-field"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Campus:</label>
                    <div class="form-field"></div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group" style="flex: 1;">
                    <label class="form-label">Address:</label>
                    <div class="form-field-full"></div>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title">Parent/Guardian Information</div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Father Name:</label>
                    <div class="form-field"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Father Phone:</label>
                    <div class="form-field"></div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Mother Name:</label>
                    <div class="form-field"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Mother Phone:</label>
                    <div class="form-field"></div>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Guardian Name:</label>
                    <div class="form-field"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Guardian Phone:</label>
                    <div class="form-field"></div>
                </div>
            </div>
        </div>

        <div class="form-section">
            <div class="form-section-title">Admission Details</div>
            <div class="form-row">
                <div class="form-group">
                    <label class="form-label">Admission Date:</label>
                    <div class="form-field"></div>
                </div>
                <div class="form-group">
                    <label class="form-label">Session:</label>
                    <div class="form-field"></div>
                </div>
            </div>
        </div>

        <div class="signature-section">
            <div class="signature-box">
                <div class="signature-line">
                    <strong>Parent/Guardian Signature</strong>
                </div>
            </div>
            <div class="signature-box">
                <div class="signature-line">
                    <strong>Principal Signature</strong>
                </div>
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
@else
@extends('layouts.app')

@section('title', 'Blank Admission Form')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Blank Admission Form</h4>
                <a href="{{ route('admission.report.blank', ['print' => 1]) }}" target="_blank" class="btn btn-sm btn-primary">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                    Print
                </a>
            </div>

            <div class="alert alert-info">
                <p class="mb-0">Click the "Print" button above to print a blank admission form.</p>
            </div>
        </div>
    </div>
</div>

<style>
@media print {
    /* Hide sidebar and all navigation elements - comprehensive list */
    .sidebar-area,
    #sidebar-area,
    .sidebar,
    aside,
    #layout-menu,
    .layout-menu,
    .menu-vertical,
    .menu,
    .navbar,
    .navbar-area,
    .header-area,
    #header-area,
    header,
    .main-header,
    .header-navbar,
    .left-header-content,
    .right-header-content,
    .theme-settings-area,
    .theme-settings,
    .settings-btn,
    .preloader,
    .footer-area,
    footer,
    .btn,
    .btn-sm,
    .btn-primary,
    .d-flex.justify-content-between,
    h4.mb-0,
    .src-form,
    .header-burger-menu,
    .sidebar-burger-menu,
    .sidebar-burger-menu-close,
    .alert {
        display: none !important;
        visibility: hidden !important;
        width: 0 !important;
        height: 0 !important;
        overflow: hidden !important;
        position: absolute !important;
        left: -9999px !important;
        opacity: 0 !important;
    }
    
    /* Reset body and container */
    body {
        background: white !important;
        padding: 0 !important;
        margin: 0 !important;
    }
    
    /* Hide container padding and margins */
    .container-fluid {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }
    
    .main-content,
    .main-content-container {
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        margin-left: 0 !important;
        max-width: 100% !important;
    }
    
    .row {
        margin: 0 !important;
        padding: 0 !important;
    }
    
    .col-12 {
        padding: 0 !important;
        width: 100% !important;
        max-width: 100% !important;
    }
    
    /* Card styles */
    .card {
        box-shadow: none !important;
        border: none !important;
        padding: 0 !important;
        margin: 0 !important;
        background: white !important;
    }
    
    /* Hide card header with buttons */
    .card > .d-flex.justify-content-between {
        display: none !important;
    }
    
    /* Ensure full page width */
    @page {
        margin: 1cm;
    }
}
</style>
@endsection
@endif
