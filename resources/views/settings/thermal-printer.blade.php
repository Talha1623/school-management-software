@extends('layouts.app')

@section('title', 'Thermal Printer Settings')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-4 mb-4">
            <div class="d-flex align-items-center mb-4">
                <span class="material-symbols-outlined me-2" style="font-size: 28px; color: #003471;">print</span>
                <h3 class="mb-0 fw-bold" style="color: #003471;">Thermal Printer Settings</h3>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form method="POST" action="#">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm rounded-10 p-4 h-100">
                            <h5 class="mb-4 fw-semibold" style="color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">settings</span>
                                Printer Configuration
                            </h5>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Printer Type</label>
                                <select class="form-select" name="printer_type">
                                    <option value="usb">USB Printer</option>
                                    <option value="network">Network Printer</option>
                                    <option value="bluetooth">Bluetooth Printer</option>
                                    <option value="serial">Serial Port Printer</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Printer Name / IP Address</label>
                                <input type="text" class="form-control" name="printer_name" placeholder="Enter printer name or IP">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Port</label>
                                <input type="text" class="form-control" name="printer_port" placeholder="Enter port number">
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Paper Width (mm)</label>
                                <select class="form-select" name="paper_width">
                                    <option value="58">58mm</option>
                                    <option value="80">80mm</option>
                                    <option value="110">110mm</option>
                                </select>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="auto_cut" name="auto_cut">
                                <label class="form-check-label" for="auto_cut">
                                    Enable Auto Cut
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm rounded-10 p-4 h-100">
                            <h5 class="mb-4 fw-semibold" style="color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">receipt</span>
                                Receipt Settings
                            </h5>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Header Text</label>
                                <textarea class="form-control" name="header_text" rows="3" placeholder="Enter header text for receipts"></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Footer Text</label>
                                <textarea class="form-control" name="footer_text" rows="2" placeholder="Enter footer text for receipts"></textarea>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="print_logo" name="print_logo">
                                <label class="form-check-label" for="print_logo">
                                    Print School Logo
                                </label>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="print_barcode" name="print_barcode">
                                <label class="form-check-label" for="print_barcode">
                                    Print Barcode
                                </label>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="print_qr" name="print_qr">
                                <label class="form-check-label" for="print_qr">
                                    Print QR Code
                                </label>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm rounded-10 p-4 h-100">
                            <h5 class="mb-4 fw-semibold" style="color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">print_disabled</span>
                                Print Options
                            </h5>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="print_fee_receipt" name="print_fee_receipt">
                                <label class="form-check-label" for="print_fee_receipt">
                                    Auto-print Fee Receipt
                                </label>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="print_id_card" name="print_id_card">
                                <label class="form-check-label" for="print_id_card">
                                    Print ID Cards
                                </label>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="print_certificate" name="print_certificate">
                                <label class="form-check-label" for="print_certificate">
                                    Print Certificates
                                </label>
                            </div>
                            
                            <div class="form-check form-switch mb-3">
                                <input class="form-check-input" type="checkbox" id="print_report" name="print_report">
                                <label class="form-check-label" for="print_report">
                                    Print Reports
                                </label>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="button" class="btn btn-outline-secondary">Cancel</button>
                    <button type="button" class="btn btn-outline-primary">
                        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">print</span>
                        Test Print
                    </button>
                    <button type="submit" class="btn btn-primary">
                        <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">save</span>
                        Save Settings
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// Ensure sidebar stays open on Thermal Printer Settings page
document.addEventListener('DOMContentLoaded', function() {
    document.body.setAttribute("sidebar-data-theme", "sidebar-show");
    const sidebarArea = document.getElementById('sidebar-area');
    if (sidebarArea) {
        sidebarArea.style.display = '';
        sidebarArea.classList.remove('sidebar-hide');
        sidebarArea.classList.add('sidebar-show');
    }
    window.addEventListener('resize', function() {
        if (window.innerWidth > 992) {
            document.body.setAttribute("sidebar-data-theme", "sidebar-show");
        }
    });
});
</script>
@endsection

