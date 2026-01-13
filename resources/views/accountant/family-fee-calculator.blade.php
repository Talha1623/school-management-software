@extends('layouts.accountant')

@section('title', 'Fee Calculator')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3">
            <h3 class="mb-3 fw-semibold" style="color: #003471;">Fee Calculator</h3>
            
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

            <!-- Search Section -->
            <div class="card bg-light border-0 rounded-10 p-4 mb-4" style="text-align: center;">
                <h4 class="mb-3 fw-semibold" style="color: #003471;">Search Unpaid Invoices Via Father Id Card</h4>
                
                <form id="fatherIdCardForm" class="mb-4">
                    <div class="row justify-content-center">
                        <div class="col-md-8">
                            <div class="input-group input-group-lg">
                                <input type="text" 
                                       class="form-control form-control-lg" 
                                       id="father_id_card" 
                                       name="father_id_card" 
                                       placeholder="Type Father ID Card Number..." 
                                       style="height: 50px; font-size: 16px;">
                                <button type="submit" 
                                        class="btn btn-primary" 
                                        id="calculateBtn" 
                                        style="background-color: #003471; border-color: #003471; color: white; height: 50px; padding: 0 30px;">
                                    <span class="material-symbols-outlined me-2" style="font-size: 20px; vertical-align: middle; color: white;">search</span>
                                    <span style="color: white;">Calculate</span>
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- ID Card Graphic -->
                <div class="mb-4" style="display: flex; justify-content: center; align-items: center;">
                    <div style="position: relative; width: 300px; height: 300px;">
                        <!-- Yellow Circle Background -->
                        <div style="position: absolute; width: 100%; height: 100%; background: #FFD700; border-radius: 50%; display: flex; align-items: center; justify-content: center; box-shadow: 0 4px 15px rgba(0,0,0,0.2);">
                            <!-- White Cards -->
                            <div style="position: absolute; width: 80px; height: 100px; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); left: 20%; top: 20%; transform: rotate(-15deg);"></div>
                            <div style="position: absolute; width: 80px; height: 100px; background: white; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.15); right: 20%; top: 20%; transform: rotate(15deg);"></div>
                            
                            <!-- Red ID Card -->
                            <div style="position: absolute; width: 120px; height: 150px; background: #DC143C; border-radius: 10px; box-shadow: 0 4px 12px rgba(0,0,0,0.3); display: flex; align-items: center; justify-content: space-between; padding: 15px;">
                                <!-- Person Icon -->
                                <div style="width: 40px; height: 40px; background: #1a1a2e; border-radius: 50%; display: flex; align-items: center; justify-content: center;">
                                    <div style="width: 30px; height: 30px; background: #1a1a2e; border-radius: 50%; border: 2px solid white;"></div>
                                </div>
                                <!-- Lines -->
                                <div style="flex: 1; margin-left: 10px;">
                                    <div style="width: 100%; height: 3px; background: white; margin-bottom: 5px; border-radius: 2px;"></div>
                                    <div style="width: 80%; height: 3px; background: white; margin-bottom: 5px; border-radius: 2px;"></div>
                                    <div style="width: 90%; height: 3px; background: white; border-radius: 2px;"></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <p class="text-muted mb-0" style="font-size: 14px;">Scan Father ID Card For Quick Calculations...!</p>
            </div>

            <!-- Loading State -->
            <div id="loadingState" class="text-center" style="display: none;">
                <div class="spinner-border text-primary" role="status">
                    <span class="visually-hidden">Loading...</span>
                </div>
                <p class="mt-2 text-muted">Searching for father and students...</p>
            </div>

            <!-- Error Message -->
            <div id="errorMessage" class="alert alert-danger" style="display: none;">
                <span id="errorText"></span>
            </div>

            <!-- Results Section -->
            <div id="resultsSection" style="display: none;">
                <!-- Father Information -->
                <div class="card bg-light border-0 rounded-10 p-3 mb-3">
                    <h5 class="mb-3 fw-semibold" style="color: #003471;">Father Information</h5>
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Name:</strong> <span id="fatherName"></span></p>
                            <p class="mb-2"><strong>ID Card:</strong> <span id="fatherIdCard"></span></p>
                            <p class="mb-2"><strong>Phone:</strong> <span id="fatherPhone"></span></p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-2"><strong>Email:</strong> <span id="fatherEmail"></span></p>
                            <p class="mb-2"><strong>Address:</strong> <span id="fatherAddress"></span></p>
                        </div>
                    </div>
                </div>

                <!-- Students List - Receipt Style (Terminal Print) -->
                <div class="card border-2 border-dark rounded-0 p-4 mb-3" id="receiptSection" style="max-width: 350px; margin: 0 auto; font-family: 'Courier New', monospace; box-shadow: 0 2px 8px rgba(0,0,0,0.1); background-color: #1a1a2e; color: white;">
                    <div class="text-center mb-3" style="color: white;">
                        <h4 class="mb-1" style="font-weight: bold; font-size: 16px; letter-spacing: 1px; text-transform: uppercase; color: white;">ROYAL GRAMMAR SCHOOL</h4>
                        <p class="mb-1" style="font-size: 10px; margin: 0; color: white;">Fee Calculator Receipt</p>
                        <p class="mb-1" style="font-size: 9px; margin: 2px 0; color: white;" id="receiptContactInfo">
                            Phone: {{ config('app.phone', 'N/A') }} | Email: {{ config('app.email', 'N/A') }}
                        </p>
                        <div style="border-top: 2px dashed #fff; margin: 6px 0;"></div>
                    </div>
                    
                    <div class="mb-2" style="font-size: 10px; line-height: 1.6; color: white;">
                        <div style="color: white;"><strong style="color: white;">Father:</strong> <span id="receiptFatherName" style="color: white;"></span></div>
                        <div style="color: white;"><strong style="color: white;">ID Card:</strong> <span id="receiptFatherIdCard" style="color: white;"></span></div>
                        <div style="color: white;"><strong style="color: white;">Date:</strong> <span id="receiptDate" style="color: white;"></span></div>
                        <div style="border-top: 1px dashed #fff; margin: 6px 0; padding-top: 4px;"></div>
                    </div>
                    
                    <div class="mb-2" style="font-size: 10px; color: white;">
                        <div style="border-bottom: 1px solid #fff; padding-bottom: 3px; margin-bottom: 5px; text-align: center; font-weight: bold; color: white;">
                            CHILDREN INFORMATION
                        </div>
                        <div id="receiptStudentsList" style="line-height: 1.8; color: white;">
                            <!-- Students will be populated here -->
                        </div>
                        <div style="border-top: 2px solid #fff; margin-top: 8px; padding-top: 6px;">
                            <div style="display: flex; justify-content: space-between; font-weight: bold; font-size: 12px; letter-spacing: 0.5px; color: white;">
                                <span style="color: white;">TOTAL AMOUNT:</span>
                                <span id="receiptTotalAmount" style="font-size: 14px; color: white;">0.00</span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-3" style="font-size: 9px; border-top: 1px dashed #fff; padding-top: 8px; line-height: 1.6; color: white;">
                        <div style="font-weight: bold; color: white;">Thank You!</div>
                        <div style="margin-top: 4px; color: white;">This is a computer generated receipt</div>
                    </div>
                </div>
                
                <!-- Action Buttons -->
                <div class="card bg-light border-0 rounded-10 p-3 mb-3">
                    <div class="d-flex justify-content-center gap-3 flex-wrap">
                        <button type="button" class="btn btn-primary btn-sm px-3 py-2" onclick="printReceipt()" id="printBtn" style="display: none;">
                            <span class="material-symbols-outlined me-1" style="font-size: 16px; vertical-align: middle;">print</span>
                            Print
                        </button>
                        <button type="button" class="btn btn-success btn-sm px-3 py-2" onclick="payAllFees()" id="payAllBtn" style="display: none;">
                            <span class="material-symbols-outlined me-1" style="font-size: 16px; vertical-align: middle;">payments</span>
                            Pay All
                        </button>
                        <button type="button" class="btn btn-warning btn-sm px-3 py-2" onclick="partialPayment()" id="partialPaymentBtn" style="display: none;">
                            <span class="material-symbols-outlined me-1" style="font-size: 16px; vertical-align: middle;">account_balance_wallet</span>
                            Partial Payment
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
    .input-group-text {
        border-right: none;
    }
    
    .form-control:focus {
        border-color: #003471;
        box-shadow: 0 0 0 0.2rem rgba(0, 52, 113, 0.25);
    }
    
    table thead th {
        font-size: 13px;
        font-weight: 600;
        padding: 12px 15px;
    }
    
    table tbody td {
        font-size: 13px;
        padding: 12px 15px;
        vertical-align: middle;
    }
    
    table tfoot {
        background-color: #e8e8e8;
    }
    
    table tfoot td {
        font-size: 14px;
        padding: 12px 15px;
        font-weight: bold;
        border-top: 2px solid #003471;
    }
    
    #totalAmount {
        color: #003471;
        font-size: 16px;
        font-weight: bold;
    }
    
    /* Receipt Print Styles */
    @media print {
        .no-print-receipt {
            display: none !important;
        }
        #receiptSection {
            max-width: 100% !important;
            border: none !important;
            box-shadow: none !important;
            page-break-inside: avoid;
        }
        body {
            background: white !important;
        }
    }
    
    #receiptSection {
        box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    }
</style>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    const form = document.getElementById('fatherIdCardForm');
    const fatherIdCardInput = document.getElementById('father_id_card');
    const loadingState = document.getElementById('loadingState');
    const errorMessage = document.getElementById('errorMessage');
    const errorText = document.getElementById('errorText');
    const resultsSection = document.getElementById('resultsSection');

    form.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const fatherIdCard = fatherIdCardInput.value.trim();
        
        if (!fatherIdCard) {
            errorText.textContent = 'Please enter Father ID Card Number';
            errorMessage.style.display = 'block';
            resultsSection.style.display = 'none';
            return;
        }

        // Show loading state
        loadingState.style.display = 'block';
        errorMessage.style.display = 'none';
        resultsSection.style.display = 'none';

        // Make AJAX call to search by Father ID Card
        fetch(`{{ route('accountant.family-fee-calculator.search-by-id-card') }}?father_id_card=${encodeURIComponent(fatherIdCard)}`, {
            method: 'GET',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'Accept': 'application/json',
            }
        })
        .then(response => response.json())
        .then(data => {
            loadingState.style.display = 'none';
            
            // Debug logging
            console.log('API Response:', data);
            console.log('Students found:', data.students ? data.students.length : 0);
            
            if (data.success) {
                if (data.found) {
                    // Display father information
                    document.getElementById('fatherName').textContent = data.father.name || 'N/A';
                    document.getElementById('fatherIdCard').textContent = data.father.id_card_number || 'N/A';
                    document.getElementById('fatherPhone').textContent = data.father.phone || 'N/A';
                    document.getElementById('fatherEmail').textContent = data.father.email || 'N/A';
                    document.getElementById('fatherAddress').textContent = data.father.address || 'N/A';

                    // Display students in receipt style
                    console.log('Students array:', data.students);
                    if (data.students && Array.isArray(data.students) && data.students.length > 0) {
                        let totalAmount = 0;
                        const receiptStudentsList = document.getElementById('receiptStudentsList');
                        receiptStudentsList.innerHTML = '';
                        
                        // Store students data for payment processing
                        window.feeCalculatorData = {
                            father: data.father,
                            students: data.students
                        };
                        
                        data.students.forEach((student, index) => {
                            const monthlyFee = student.monthly_fee ? parseFloat(student.monthly_fee) : 0;
                            totalAmount += monthlyFee;
                            
                            // Receipt style display (Terminal Print Style)
                            const receiptItem = document.createElement('div');
                            receiptItem.style.cssText = 'margin-bottom: 5px; padding-bottom: 3px; border-bottom: 1px dotted rgba(255,255,255,0.5);';
                            receiptItem.innerHTML = `
                                <div style="display: flex; justify-content: space-between; font-size: 10px; color: white;">
                                    <span style="color: white;">${index + 1}. ${student.student_name || 'N/A'}</span>
                                    <span style="font-weight: bold; font-size: 11px; color: white;">${monthlyFee.toFixed(2)}</span>
                                </div>
                                <div style="font-size: 9px; color: rgba(255,255,255,0.8); margin-left: 12px; margin-top: 2px;">
                                    ${student.student_code || ''} | ${student.class || ''}/${student.section || ''} | ${student.campus || ''}
                                </div>
                            `;
                            receiptStudentsList.appendChild(receiptItem);
                        });
                        
                        // Update total amount
                        document.getElementById('receiptTotalAmount').textContent = totalAmount.toFixed(2);
                        
                        // Update receipt header
                        document.getElementById('receiptFatherName').textContent = data.father.name || 'N/A';
                        document.getElementById('receiptFatherIdCard').textContent = data.father.id_card_number || 'N/A';
                        document.getElementById('receiptDate').textContent = new Date().toLocaleDateString('en-GB');
                        
                        // Show buttons
                        document.getElementById('printBtn').style.display = 'inline-block';
                        document.getElementById('payAllBtn').style.display = 'inline-block';
                        document.getElementById('partialPaymentBtn').style.display = 'inline-block';
                    } else {
                        document.getElementById('receiptStudentsList').innerHTML = '<div class="text-center" style="padding: 10px; color: white;">No students found for this father.</div>';
                        document.getElementById('receiptTotalAmount').textContent = '0.00';
                        
                        // Hide buttons
                        document.getElementById('printBtn').style.display = 'none';
                        document.getElementById('payAllBtn').style.display = 'none';
                        document.getElementById('partialPaymentBtn').style.display = 'none';
                    }

                    resultsSection.style.display = 'block';
                } else {
                    errorText.textContent = data.message || 'No father found with this ID Card Number';
                    errorMessage.style.display = 'block';
                }
            } else {
                errorText.textContent = data.message || 'An error occurred. Please try again.';
                errorMessage.style.display = 'block';
            }
        })
        .catch(error => {
            loadingState.style.display = 'none';
            console.error('Error:', error);
            errorText.textContent = 'An error occurred while searching. Please try again.';
            errorMessage.style.display = 'block';
        });
    });
});

// Print Receipt Function (Terminal Print Style) - Global function
function printReceipt() {
    const receiptSection = document.getElementById('receiptSection');
    
    if (!receiptSection) {
        alert('Receipt section not found. Please search for a father first.');
        return;
    }
    
    const printWindow = window.open('', '_blank', 'width=800,height=600');
    
    if (!printWindow) {
        alert('Please allow popups for this site to print the receipt.');
        return;
    }
        
        printWindow.document.write(`
            <!DOCTYPE html>
            <html>
            <head>
                <title>Fee Calculator Receipt</title>
                <style>
                    @media print {
                        @page { 
                            margin: 5mm; 
                            size: A4;
                            background: white;
                        }
                        body {
                            margin: 0;
                            padding: 0;
                            background: white !important;
                            color: black !important;
                        }
                        .receipt,
                        .receipt * {
                            background-color: white !important;
                            background: white !important;
                            color: black !important;
                        }
                    }
                    body {
                        font-family: 'Courier New', monospace;
                        margin: 0;
                        padding: 15px;
                        font-size: 10px;
                        background: white !important;
                        color: black !important;
                    }
                    .receipt {
                        max-width: 300px;
                        margin: 0 auto;
                        border: 2px solid #000;
                        padding: 15px;
                        background-color: white !important;
                        background: white !important;
                        color: black !important;
                    }
                    .receipt,
                    .receipt *,
                    .receipt div,
                    .receipt p,
                    .receipt span,
                    .receipt h4,
                    .receipt strong,
                    .card {
                        color: black !important;
                        background-color: white !important;
                        background: white !important;
                    }
                    .receipt div[style*="background-color"],
                    .receipt div[style*="background"],
                    .card[style*="background-color"],
                    .card[style*="background"] {
                        background-color: white !important;
                        background: white !important;
                    }
                    .receipt div[style*="color: white"],
                    .receipt span[style*="color: white"],
                    .receipt p[style*="color: white"],
                    .receipt h4[style*="color: white"] {
                        color: black !important;
                    }
                    .header {
                        text-align: center;
                        margin-bottom: 12px;
                        color: black !important;
                    }
                    .header h4 {
                        font-weight: bold;
                        font-size: 16px;
                        margin: 3px 0;
                        letter-spacing: 1px;
                        text-transform: uppercase;
                        color: black !important;
                    }
                    .divider {
                        border-top: 2px dashed #000 !important;
                        margin: 6px 0;
                    }
                    .item {
                        margin-bottom: 5px;
                        padding-bottom: 3px;
                        border-bottom: 1px dotted #999 !important;
                        font-size: 10px;
                        line-height: 1.6;
                        color: black !important;
                    }
                    .total {
                        border-top: 2px solid #000 !important;
                        margin-top: 8px;
                        padding-top: 6px;
                        font-weight: bold;
                        font-size: 12px;
                        color: black !important;
                    }
                </style>
            </head>
            <body>
                <div class="receipt">
                    ${receiptSection.innerHTML}
                </div>
            </body>
            </html>
        `);
        
        printWindow.document.close();
        
        // Wait for content to load, then trigger print
        setTimeout(() => {
            printWindow.focus();
            printWindow.print();
        }, 500);
    }
    
    // Pay All Fees Function
    function payAllFees() {
        if (!window.feeCalculatorData || !window.feeCalculatorData.students || window.feeCalculatorData.students.length === 0) {
            alert('No students found to process payment.');
            return;
        }
        
        const totalAmount = window.feeCalculatorData.students.reduce((sum, student) => {
            return sum + (parseFloat(student.monthly_fee) || 0);
        }, 0);
        
        if (confirm(`Are you sure you want to pay all fees for all children?\n\nTotal Amount: ${totalAmount.toFixed(2)}\nNumber of Students: ${window.feeCalculatorData.students.length}`)) {
            // Store data in sessionStorage for payment page
            sessionStorage.setItem('feeCalculatorPayment', JSON.stringify({
                students: window.feeCalculatorData.students,
                totalAmount: totalAmount,
                paymentType: 'pay_all'
            }));
            
            // Redirect to payment page
            window.location.href = `{{ route('accountant.direct-payment.student') }}`;
        }
    }
    
    // Partial Payment Function
    function partialPayment() {
        if (!window.feeCalculatorData || !window.feeCalculatorData.students || window.feeCalculatorData.students.length === 0) {
            alert('No students found to process payment.');
            return;
        }
        
        const totalAmount = window.feeCalculatorData.students.reduce((sum, student) => {
            return sum + (parseFloat(student.monthly_fee) || 0);
        }, 0);
        
        const paymentAmount = prompt(`Enter payment amount:\n\nTotal Due: ${totalAmount.toFixed(2)}\n\nNote: This will be distributed proportionally among all students.`, totalAmount.toFixed(2));
        
        if (paymentAmount && !isNaN(paymentAmount) && parseFloat(paymentAmount) > 0) {
            const amount = parseFloat(paymentAmount);
            if (amount > totalAmount) {
                alert('Payment amount cannot be greater than total amount.');
                return;
            }
            
            // Store data in sessionStorage for payment page
            sessionStorage.setItem('feeCalculatorPayment', JSON.stringify({
                students: window.feeCalculatorData.students,
                totalAmount: totalAmount,
                paymentAmount: amount,
                paymentType: 'partial'
            }));
            
            // Redirect to payment page
            window.location.href = `{{ route('accountant.direct-payment.student') }}`;
        }
    }
});
</script>
@endpush
@endsection
