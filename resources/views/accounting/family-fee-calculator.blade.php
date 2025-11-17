@extends('layouts.app')

@section('title', 'Family Fee Calculator')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3">
            <h3 class="mb-3 fw-semibold" style="color: #003471;">Family Fee Calculator</h3>
            
            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif
            
            @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <div class="card bg-light border-0 rounded-10 p-3 mb-3">
                <form id="familyFeeCalculatorForm">
                    <div class="row mb-3">
                        <div class="col-md-12">
                            <label for="family_id" class="form-label mb-1 fs-13 fw-medium">Family ID / Parent Name <span class="text-danger">*</span></label>
                            <select class="form-select form-select-sm py-2" id="family_id" name="family_id" required style="height: 38px;">
                                <option value="">Select Family / Parent Name</option>
                                @if(isset($families) && $families->count() > 0)
                                    @foreach($families as $family)
                                        <option value="{{ $family['id'] }}">{{ $family['name'] }}</option>
                                    @endforeach
                                @endif
                            </select>
                            <small class="text-muted" style="font-size: 11px;">Select a parent to automatically load connected students</small>
                        </div>
                    </div>
                </form>
            </div>

            <!-- Students List -->
            <div id="studentsList" class="card bg-light border-0 rounded-10 p-3 mb-3" style="display: none;">
                <h5 class="mb-3 fw-semibold" style="color: #003471;">Family Students</h5>
                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead style="background-color: #003471; color: white;">
                            <tr>
                                <th>Select</th>
                                <th>Student Name</th>
                                <th>Class</th>
                                <th>Section</th>
                                <th>Admission No</th>
                            </tr>
                        </thead>
                        <tbody id="studentsTableBody">
                            <!-- Students will be populated here -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- Fee Calculation Section -->
            <div id="feeCalculationSection" class="card bg-light border-0 rounded-10 p-3 mb-3" style="display: none;">
                <!-- Print Header (Hidden on screen, visible in print) -->
                <div id="printHeader" style="display: none;">
                    <div style="text-align: center; margin-bottom: 20px; padding-bottom: 15px; border-bottom: 2px solid #003471;">
                        <div style="display: flex; align-items: center; justify-content: center; gap: 15px; margin-bottom: 10px;">
                            <img src="{{ asset('assets/images/logo-icon.png') }}" alt="Logo" style="width: 60px; height: 60px; object-fit: contain;" onerror="this.style.display='none'">
                            <div style="text-align: left;">
                                <h3 style="margin: 0; color: #003471; font-weight: 700; font-size: 24px;">{{ config('app.name', 'ICMS Management System') }}</h3>
                                @if(config('app.url'))
                                <p style="margin: 2px 0; color: #666; font-size: 12px;">Website: {{ config('app.url') }}</p>
                                @endif
                                @if(config('app.contact'))
                                <p style="margin: 2px 0; color: #666; font-size: 12px;">Phone: {{ config('app.contact') }}</p>
                                @endif
                            </div>
                        </div>
                        <h4 style="margin: 10px 0 0 0; color: #003471; font-weight: 600; font-size: 18px;">Family Fee Calculator</h4>
                    </div>
                </div>
                
                <h5 class="mb-3 fw-semibold no-print" style="color: #003471;">Fee Calculation</h5>
                
                <div class="row mb-3 no-print">
                    <div class="col-md-4">
                        <label for="fee_month" class="form-label mb-1 fs-13 fw-medium">Fee Month</label>
                        <select class="form-select form-select-sm py-2" id="fee_month" name="fee_month" style="height: 38px;">
                            <option value="">Select Month</option>
                            <option value="January">January</option>
                            <option value="February">February</option>
                            <option value="March">March</option>
                            <option value="April">April</option>
                            <option value="May">May</option>
                            <option value="June">June</option>
                            <option value="July">July</option>
                            <option value="August">August</option>
                            <option value="September">September</option>
                            <option value="October">October</option>
                            <option value="November">November</option>
                            <option value="December">December</option>
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="fee_year" class="form-label mb-1 fs-13 fw-medium">Fee Year</label>
                        <select class="form-select form-select-sm py-2" id="fee_year" name="fee_year" style="height: 38px;">
                            <option value="">Select Year</option>
                            @for($year = date('Y'); $year >= date('Y') - 5; $year--)
                                <option value="{{ $year }}" {{ $year == date('Y') ? 'selected' : '' }}>{{ $year }}</option>
                            @endfor
                        </select>
                    </div>
                    <div class="col-md-4">
                        <label for="discount_percentage" class="form-label mb-1 fs-13 fw-medium">Discount (%)</label>
                        <input type="number" class="form-control form-control-sm py-2" id="discount_percentage" name="discount_percentage" placeholder="Enter discount %" min="0" max="100" value="0" style="height: 38px;">
                    </div>
                </div>
                
                <!-- Print Info Section (Hidden on screen, visible in print) -->
                <div id="printInfo" style="display: none; margin-bottom: 15px; padding: 10px; background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px;">
                    <div style="display: flex; justify-content: space-between; flex-wrap: wrap; gap: 10px; font-size: 12px;">
                        <div><strong>Fee Month:</strong> <span id="printFeeMonth"></span></div>
                        <div><strong>Fee Year:</strong> <span id="printFeeYear"></span></div>
                        <div><strong>Discount:</strong> <span id="printDiscount"></span>%</div>
                        <div><strong>Parent Name:</strong> <span id="printParentName"></span></div>
                        <div><strong>Date:</strong> <span id="printDate"></span></div>
                    </div>
                </div>

                <div class="table-responsive">
                    <table class="table table-bordered table-sm">
                        <thead style="background-color: #003471; color: white;">
                            <tr>
                                <th>Student Name</th>
                                <th>Class</th>
                                <th>Section</th>
                                <th>Monthly Fee</th>
                                <th>Transport Fee</th>
                                <th>Custom Fee</th>
                                <th>Total Fee</th>
                                <th>Discount</th>
                                <th>Final Amount</th>
                            </tr>
                        </thead>
                        <tbody id="feeCalculationTableBody">
                            <!-- Fee calculation will be populated here -->
                        </tbody>
                        <tfoot>
                            <tr class="fw-bold" style="background-color: #f0f0f0;">
                                <td colspan="8" class="text-end">Grand Total:</td>
                                <td id="grandTotal">0.00</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>

                <div class="mt-3 text-end">
                    <button type="button" class="btn btn-sm px-4 py-2" id="calculateBtn" style="background-color: #003471; color: white;">
                        <i class="fas fa-calculator me-1"></i> Calculate Fee
                    </button>
                    <button type="button" class="btn btn-sm px-4 py-2 btn-success ms-2" id="printBtn" style="display: none;">
                        <i class="fas fa-print me-1"></i> Print
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    /* Print Styles */
    @media print {
        /* Hide sidebar, header, footer, and other non-printable elements */
        .sidebar-menu,
        .header,
        .footer,
        .preloader,
        .theme-settings,
        .btn,
        .card:not(#feeCalculationSection),
        #studentsList,
        #familyFeeCalculatorForm,
        .alert,
        h3:not(#printHeader h3),
        h4:not(#printHeader h4) {
            display: none !important;
        }
        
        /* Show print header and info */
        #printHeader,
        #printInfo {
            display: block !important;
        }
        
        /* Show fee calculation section */
        #feeCalculationSection {
            display: block !important;
            page-break-inside: avoid;
        }
        
        /* Remove card styling for print */
        #feeCalculationSection.card {
            border: none !important;
            box-shadow: none !important;
            background: white !important;
            padding: 0 !important;
        }
        
        /* Hide form inputs in print, show values */
        #feeCalculationSection select,
        #feeCalculationSection input[type="number"] {
            border: none !important;
            background: transparent !important;
            padding: 0 !important;
            appearance: none !important;
            -webkit-appearance: none !important;
            -moz-appearance: none !important;
        }
        
        /* Table styling for print */
        #feeCalculationSection table {
            width: 100% !important;
            border-collapse: collapse !important;
        }
        
        #feeCalculationSection table th,
        #feeCalculationSection table td {
            border: 1px solid #000 !important;
            padding: 8px !important;
            text-align: left !important;
        }
        
        #feeCalculationSection table thead th {
            background-color: #003471 !important;
            color: white !important;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }
        
        /* Page setup */
        @page {
            margin: 1cm;
            size: A4;
        }
        
        body {
            background: white !important;
            color: black !important;
        }
        
        /* Hide screen-only elements */
        .no-print {
            display: none !important;
        }
    }
    
    /* Screen styles - hide print header */
    @media screen {
        #printHeader {
            display: none !important;
        }
    }
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const feeCalculationSection = document.getElementById('feeCalculationSection');
    const calculateBtn = document.getElementById('calculateBtn');
    const printBtn = document.getElementById('printBtn');
    
    let selectedStudents = [];
    const familySelect = document.getElementById('family_id');
    const studentsList = document.getElementById('studentsList');
    const tbody = document.getElementById('studentsTableBody');

    // Function to load students for selected parent
    function loadStudents(familyId) {
        if (!familyId) {
            studentsList.style.display = 'none';
            return;
        }

        // Show loading state
        studentsList.style.display = 'block';
        tbody.innerHTML = '<tr><td colspan="5" class="text-center py-3"><i class="fas fa-spinner fa-spin me-2"></i>Loading students...</td></tr>';

        // Make AJAX call to fetch students
        fetch(`{{ route('accounting.family-fee-calculator.students') }}?family_id=${encodeURIComponent(familyId)}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.students && data.students.length > 0) {
                displayStudents(data.students);
                studentsList.style.display = 'block';
            } else {
                tbody.innerHTML = '<tr><td colspan="5" class="text-center text-muted py-3">No students found for this family.</td></tr>';
                studentsList.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            tbody.innerHTML = '<tr><td colspan="5" class="text-center text-danger py-3">Error loading students. Please try again.</td></tr>';
            studentsList.style.display = 'block';
        });
    }

    // Auto-load students when parent is selected
    familySelect.addEventListener('change', function() {
        const familyId = this.value;
        loadStudents(familyId);
    });

    function displayStudents(students) {
        const tbody = document.getElementById('studentsTableBody');
        tbody.innerHTML = '';

        students.forEach(student => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>
                    <input type="checkbox" class="form-check-input student-checkbox" value="${student.id}" data-name="${student.name}" data-class="${student.class}" data-section="${student.section}">
                </td>
                <td>${student.name}</td>
                <td>${student.class}</td>
                <td>${student.section}</td>
                <td>${student.admission_no}</td>
            `;
            tbody.appendChild(row);
        });

        // Add event listeners to checkboxes
        document.querySelectorAll('.student-checkbox').forEach(checkbox => {
            checkbox.addEventListener('change', function() {
                updateSelectedStudents();
            });
        });
    }

    function updateSelectedStudents() {
        selectedStudents = [];
        document.querySelectorAll('.student-checkbox:checked').forEach(checkbox => {
            selectedStudents.push({
                id: checkbox.value,
                name: checkbox.dataset.name,
                class: checkbox.dataset.class,
                section: checkbox.dataset.section
            });
        });

        if (selectedStudents.length > 0) {
            feeCalculationSection.style.display = 'block';
            populateFeeCalculation();
        } else {
            feeCalculationSection.style.display = 'none';
        }
    }

    function populateFeeCalculation() {
        const tbody = document.getElementById('feeCalculationTableBody');
        tbody.innerHTML = '';

        selectedStudents.forEach(student => {
            const row = document.createElement('tr');
            row.innerHTML = `
                <td>${student.name}</td>
                <td>${student.class}</td>
                <td>${student.section}</td>
                <td><input type="number" class="form-control form-control-sm monthly-fee" value="0" min="0" step="0.01"></td>
                <td><input type="number" class="form-control form-control-sm transport-fee" value="0" min="0" step="0.01"></td>
                <td><input type="number" class="form-control form-control-sm custom-fee" value="0" min="0" step="0.01"></td>
                <td class="total-fee">0.00</td>
                <td class="discount-amount">0.00</td>
                <td class="final-amount">0.00</td>
            `;
            tbody.appendChild(row);
        });

        // Add event listeners to fee inputs
        document.querySelectorAll('.monthly-fee, .transport-fee, .custom-fee').forEach(input => {
            input.addEventListener('input', calculateFees);
        });
    }

    calculateBtn.addEventListener('click', function() {
        calculateFees();
        printBtn.style.display = 'inline-block';
    });

    function calculateFees() {
        const discountPercentage = parseFloat(document.getElementById('discount_percentage').value) || 0;
        let grandTotal = 0;

        document.querySelectorAll('#feeCalculationTableBody tr').forEach(row => {
            const monthlyFee = parseFloat(row.querySelector('.monthly-fee').value) || 0;
            const transportFee = parseFloat(row.querySelector('.transport-fee').value) || 0;
            const customFee = parseFloat(row.querySelector('.custom-fee').value) || 0;
            
            const totalFee = monthlyFee + transportFee + customFee;
            const discountAmount = (totalFee * discountPercentage) / 100;
            const finalAmount = totalFee - discountAmount;

            row.querySelector('.total-fee').textContent = totalFee.toFixed(2);
            row.querySelector('.discount-amount').textContent = discountAmount.toFixed(2);
            row.querySelector('.final-amount').textContent = finalAmount.toFixed(2);

            grandTotal += finalAmount;
        });

        document.getElementById('grandTotal').textContent = grandTotal.toFixed(2);
    }

    printBtn.addEventListener('click', function() {
        // Update print info
        const feeMonth = document.getElementById('fee_month').value || 'N/A';
        const feeYear = document.getElementById('fee_year').value || 'N/A';
        const discount = document.getElementById('discount_percentage').value || '0';
        const parentName = familySelect.options[familySelect.selectedIndex].text || 'N/A';
        const currentDate = new Date().toLocaleDateString('en-GB', { 
            day: '2-digit', 
            month: '2-digit', 
            year: 'numeric' 
        });
        
        document.getElementById('printFeeMonth').textContent = feeMonth;
        document.getElementById('printFeeYear').textContent = feeYear;
        document.getElementById('printDiscount').textContent = discount;
        document.getElementById('printParentName').textContent = parentName;
        document.getElementById('printDate').textContent = currentDate;
        
        // Show print header and info
        document.getElementById('printHeader').style.display = 'block';
        document.getElementById('printInfo').style.display = 'block';
        
        // Trigger print
        window.print();
        
        // Hide print header and info after print (for screen view)
        setTimeout(function() {
            document.getElementById('printHeader').style.display = 'none';
            document.getElementById('printInfo').style.display = 'none';
        }, 100);
    });
});
</script>
@endpush
@endsection

