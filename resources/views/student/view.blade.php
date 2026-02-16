@extends('layouts.app')

@php
use Illuminate\Support\Facades\Storage;
@endphp

@section('title', 'Student Details')

@section('content')
<style>
    .student-detail-card {
        border-radius: 12px;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08);
        transition: all 0.3s ease;
        border: none;
    }
    
    .student-detail-card:hover {
        box-shadow: 0 4px 16px rgba(0, 0, 0, 0.12);
    }
    
    .info-label {
        font-size: 12px;
        font-weight: 600;
        color: #6c757d;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
    }
    
    .info-value {
        font-size: 14px;
        font-weight: 500;
        color: #212529;
        margin-bottom: 0;
    }
    
    .photo-container {
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.1);
        background: linear-gradient(135deg, #f8f9fa 0%, #e9ecef 100%);
    }
    
    .photo-placeholder {
        background: linear-gradient(135deg, #003471 0%, #0056b3 100%);
        display: flex;
        align-items: center;
        justify-content: center;
        height: 250px;
    }
    
    .info-item {
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }
    
    .info-item:last-child {
        border-bottom: none;
    }
    
    .badge-custom {
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
    }
    
    .back-btn {
        background: linear-gradient(135deg, #6c757d 0%, #5a6268 100%);
        border: none;
        border-radius: 8px;
        padding: 8px 16px;
        color: white;
        font-weight: 500;
        transition: all 0.3s ease;
    }
    
    .back-btn:hover {
        background: linear-gradient(135deg, #5a6268 0%, #495057 100%);
        transform: translateY(-1px);
        box-shadow: 0 4px 8px rgba(0, 0, 0, 0.15);
        color: white;
    }

    /* Tab Styles */
    .tab-nav {
        display: flex;
        gap: 8px;
        border-bottom: 2px solid #e9ecef;
        margin-bottom: 20px;
        flex-wrap: wrap;
    }

    .tab-btn {
        padding: 10px 20px;
        background: transparent;
        border: none;
        border-bottom: 3px solid transparent;
        color: #6c757d;
        font-weight: 500;
        font-size: 14px;
        cursor: pointer;
        transition: all 0.3s ease;
        position: relative;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    .tab-btn:hover {
        color: #003471;
        background-color: #f8f9fa;
    }

    .tab-btn.active {
        color: #003471;
        border-bottom-color: #003471;
        background-color: #f0f4ff;
    }

    .tab-content {
        display: none;
        animation: fadeIn 0.3s ease;
    }

    .tab-content.active {
        display: block;
    }

    @keyframes fadeIn {
        from {
            opacity: 0;
            transform: translateY(10px);
        }
        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .info-row {
        display: flex;
        padding: 12px 0;
        border-bottom: 1px solid #f0f0f0;
    }

    .info-row:last-child {
        border-bottom: none;
    }

    .info-row-label {
        flex: 0 0 40%;
        font-weight: 600;
        color: #6c757d;
        font-size: 13px;
    }

    .info-row-value {
        flex: 1;
        color: #212529;
        font-size: 14px;
    }

    .marks-table {
        width: 100%;
        border-collapse: collapse;
    }

    .marks-table th,
    .marks-table td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid #e9ecef;
    }

    .marks-table th {
        background-color: #f8f9fa;
        font-weight: 600;
        color: #003471;
        font-size: 13px;
    }

    .marks-table td {
        font-size: 13px;
    }

    .payment-table {
        width: 100%;
        border-collapse: collapse;
    }

    .payment-table th,
    .payment-table td {
        padding: 10px;
        text-align: left;
        border-bottom: 1px solid #e9ecef;
    }

    .payment-table th {
        background-color: #f8f9fa;
        font-weight: 600;
        color: #003471;
        font-size: 13px;
    }

    .payment-table td {
        font-size: 13px;
    }
</style>

<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-4 mb-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <div>
                    <h4 class="mb-0 fs-18 fw-bold" style="color: #003471;">Student Details</h4>
                </div>
                <a href="{{ route('student.information') }}" class="back-btn">
                    <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle;">arrow_back</span>
                    <span>Back to List</span>
                </a>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert" style="border-radius: 8px;">
                    <span class="material-symbols-outlined" style="font-size: 18px; vertical-align: middle; margin-right: 8px;">check_circle</span>
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Student Header with Photo -->
            <div class="row mb-4">
                <div class="col-12">
                    <div class="d-flex align-items-center gap-4 p-3" style="background: linear-gradient(135deg, #f8f9fa 0%, #ffffff 100%); border-radius: 8px;">
                        <!-- Photo -->
                        <div class="photo-container" style="width: 100px; height: 100px; flex-shrink: 0;">
                            @if($student->photo)
                                <img src="{{ Storage::url($student->photo) }}" alt="Student Photo" class="img-fluid w-100 h-100" style="object-fit: cover;">
                            @else
                                <div class="photo-placeholder w-100 h-100 d-flex align-items-center justify-content-center">
                                    <span class="material-symbols-outlined" style="font-size: 40px; color: rgba(255, 255, 255, 0.7);">person</span>
                                </div>
                            @endif
                        </div>
                        
                        <!-- Student Info -->
                        <div class="flex-grow-1">
                            <h4 class="mb-1 fw-bold" style="color: #003471;">{{ $student->student_name }}</h4>
                            <p class="mb-0 text-muted" style="font-size: 14px;">
                                Class: {{ $student->class ?? 'N/A' }} | Section: {{ $student->section ?? 'N/A' }}
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabs Navigation -->
            <div class="tab-nav">
                <button class="tab-btn active" onclick="switchTab('basic-info')">
                    <span class="material-symbols-outlined" style="font-size: 18px;">person</span>
                    Basic Info
                </button>
                <button class="tab-btn" onclick="switchTab('parent-info')">
                    <span class="material-symbols-outlined" style="font-size: 18px;">family_restroom</span>
                    Parent Info
                </button>
                <button class="tab-btn" onclick="switchTab('exam-marks')">
                    <span class="material-symbols-outlined" style="font-size: 18px;">quiz</span>
                    Exam Marks
                </button>
                <button class="tab-btn" onclick="switchTab('test-marks')">
                    <span class="material-symbols-outlined" style="font-size: 18px;">assignment</span>
                    Test Marks
                </button>
                <button class="tab-btn" onclick="switchTab('payment-year')">
                    <span class="material-symbols-outlined" style="font-size: 18px;">payments</span>
                    Payment This Year
                </button>
            </div>

            <!-- Tab Contents -->
            <!-- Basic Info Tab -->
            <div id="basic-info" class="tab-content active">
                <div class="card student-detail-card">
                    <div class="card-body p-4">
                        <div class="info-row">
                            <div class="info-row-label">Class:</div>
                            <div class="info-row-value">{{ $student->class ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-row-label">Section:</div>
                            <div class="info-row-value">{{ $student->section ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-row-label">Student Code:</div>
                            <div class="info-row-value">{{ $student->student_code ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-row-label">G.R Number:</div>
                            <div class="info-row-value">{{ $student->gr_number ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-row-label">Surname/Caste:</div>
                            <div class="info-row-value">{{ $student->surname_caste ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-row-label">Birthday:</div>
                            <div class="info-row-value">{{ $student->date_of_birth ? $student->date_of_birth->format('d-m-Y') : 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-row-label">Place Of Birth:</div>
                            <div class="info-row-value">{{ $student->place_of_birth ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-row-label">B-Form No:</div>
                            <div class="info-row-value">{{ $student->b_form_number ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-row-label">Gender:</div>
                            <div class="info-row-value">
                                @php
                                    $genderClass = match($student->gender) {
                                        'male' => 'bg-info',
                                        'female' => 'bg-danger',
                                        default => 'bg-secondary'
                                    };
                                @endphp
                                <span class="badge {{ $genderClass }} text-white text-capitalize badge-custom">
                                    {{ $student->gender ?? 'N/A' }}
                                </span>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-row-label">Email:</div>
                            <div class="info-row-value">{{ $student->email ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-row-label">Monthly Fee:</div>
                            <div class="info-row-value"><strong style="color: #28a745;">PKR {{ number_format($student->monthly_fee ?? 0, 2) }}</strong></div>
                        </div>
                        <div class="info-row">
                            <div class="info-row-label">Campus:</div>
                            <div class="info-row-value">
                                <span class="badge bg-primary text-white badge-custom">{{ $student->campus ?? 'N/A' }}</span>
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-row-label">Admission Date:</div>
                            <div class="info-row-value">{{ $student->admission_date ? $student->admission_date->format('d-m-Y') : 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-row-label">Home Address:</div>
                            <div class="info-row-value">{{ $student->home_address ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Parent Info Tab -->
            <div id="parent-info" class="tab-content">
                <div class="card student-detail-card">
                    <div class="card-body p-4">
                        <div class="info-row">
                            <div class="info-row-label">Father Name:</div>
                            <div class="info-row-value">{{ $student->father_name ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-row-label">Father Email:</div>
                            <div class="info-row-value">{{ $student->father_email ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-row-label">Father Phone:</div>
                            <div class="info-row-value">
                                @if($student->father_phone)
                                    <a href="tel:{{ $student->father_phone }}" class="text-decoration-none" style="color: #003471;">
                                        {{ $student->father_phone }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-row-label">Mother Phone:</div>
                            <div class="info-row-value">
                                @if($student->mother_phone)
                                    <a href="tel:{{ $student->mother_phone }}" class="text-decoration-none" style="color: #003471;">
                                        {{ $student->mother_phone }}
                                    </a>
                                @else
                                    N/A
                                @endif
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-row-label">WhatsApp Number:</div>
                            <div class="info-row-value">
                                @if($student->whatsapp_number)
                                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $student->whatsapp_number) }}" target="_blank" class="text-decoration-none" style="color: #25D366;">
                                        {{ $student->whatsapp_number }}
                                        <span class="material-symbols-outlined" style="font-size: 14px; vertical-align: middle;">open_in_new</span>
                                    </a>
                                @else
                                    N/A
                                @endif
                            </div>
                        </div>
                        <div class="info-row">
                            <div class="info-row-label">Father ID Card:</div>
                            <div class="info-row-value">{{ $student->father_id_card ?? 'N/A' }}</div>
                        </div>
                        <div class="info-row">
                            <div class="info-row-label">Religion:</div>
                            <div class="info-row-value">{{ $student->religion ?? 'N/A' }}</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Exam Marks Tab -->
            <div id="exam-marks" class="tab-content">
                <div class="card student-detail-card">
                    <div class="card-body p-4">
                        @if($examMarks && $examMarks->count() > 0)
                            <table class="marks-table">
                                <thead>
                                    <tr>
                                        <th>Exam Name</th>
                                        <th>Subject</th>
                                        <th>Marks Obtained</th>
                                        <th>Total Marks</th>
                                        <th>Passing Marks</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($examMarks as $mark)
                                    <tr>
                                        <td>{{ $mark->test_name }}</td>
                                        <td>{{ $mark->subject ?? 'N/A' }}</td>
                                        <td>{{ $mark->marks_obtained ?? 'N/A' }}</td>
                                        <td>{{ $mark->total_marks ?? 'N/A' }}</td>
                                        <td>{{ $mark->passing_marks ?? 'N/A' }}</td>
                                        <td>
                                            @if($mark->marks_obtained && $mark->passing_marks)
                                                @if($mark->marks_obtained >= $mark->passing_marks)
                                                    <span class="badge bg-success">Pass</span>
                                                @else
                                                    <span class="badge bg-danger">Fail</span>
                                                @endif
                                            @else
                                                <span class="badge bg-secondary">N/A</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="text-center py-5">
                                <span class="material-symbols-outlined" style="font-size: 48px; color: #dee2e6;">quiz</span>
                                <p class="text-muted mt-3 mb-0">No exam marks found for this student.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Test Marks Tab -->
            <div id="test-marks" class="tab-content">
                <div class="card student-detail-card">
                    <div class="card-body p-4">
                        @if($testMarks && $testMarks->count() > 0)
                            <table class="marks-table">
                                <thead>
                                    <tr>
                                        <th>Test Name</th>
                                        <th>Subject</th>
                                        <th>Marks Obtained</th>
                                        <th>Total Marks</th>
                                        <th>Passing Marks</th>
                                        <th>Status</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($testMarks as $mark)
                                    <tr>
                                        <td>{{ $mark->test_name }}</td>
                                        <td>{{ $mark->subject ?? 'N/A' }}</td>
                                        <td>{{ $mark->marks_obtained ?? 'N/A' }}</td>
                                        <td>{{ $mark->total_marks ?? 'N/A' }}</td>
                                        <td>{{ $mark->passing_marks ?? 'N/A' }}</td>
                                        <td>
                                            @if($mark->marks_obtained && $mark->passing_marks)
                                                @if($mark->marks_obtained >= $mark->passing_marks)
                                                    <span class="badge bg-success">Pass</span>
                                                @else
                                                    <span class="badge bg-danger">Fail</span>
                                                @endif
                                            @else
                                                <span class="badge bg-secondary">N/A</span>
                                            @endif
                                        </td>
                                    </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        @else
                            <div class="text-center py-5">
                                <span class="material-symbols-outlined" style="font-size: 48px; color: #dee2e6;">assignment</span>
                                <p class="text-muted mt-3 mb-0">No test marks found for this student.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- Payment This Year Tab -->
            <div id="payment-year" class="tab-content">
                <div class="card student-detail-card">
                    <div class="card-body p-4">
                        @if($paymentsThisYear && $paymentsThisYear->count() > 0)
                            @php
                                $totalPaid = $paymentsThisYear->sum('payment_amount');
                                $totalDiscount = $paymentsThisYear->sum('discount');
                                $totalLateFee = $paymentsThisYear->sum('late_fee');
                            @endphp
                            <table class="payment-table">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Payment Title</th>
                                        <th>Amount</th>
                                        <th>Discount</th>
                                        <th>Late Fee</th>
                                        <th>Method</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach($paymentsThisYear as $payment)
                                    <tr>
                                        <td>{{ $payment->payment_date ? \Carbon\Carbon::parse($payment->payment_date)->format('d-m-Y') : 'N/A' }}</td>
                                        <td>{{ $payment->payment_title }}</td>
                                        <td><strong style="color: #28a745;">PKR {{ number_format($payment->payment_amount, 2) }}</strong></td>
                                        <td>{{ $payment->discount > 0 ? 'PKR ' . number_format($payment->discount, 2) : '-' }}</td>
                                        <td>{{ $payment->late_fee > 0 ? 'PKR ' . number_format($payment->late_fee, 2) : '-' }}</td>
                                        <td><span class="badge bg-info">{{ $payment->method }}</span></td>
                                    </tr>
                                    @endforeach
                                </tbody>
                                <tfoot>
                                    <tr style="background-color: #f8f9fa; font-weight: bold;">
                                        <td colspan="2" class="text-end">Total:</td>
                                        <td><strong style="color: #28a745;">PKR {{ number_format($totalPaid, 2) }}</strong></td>
                                        <td><strong>PKR {{ number_format($totalDiscount, 2) }}</strong></td>
                                        <td><strong>PKR {{ number_format($totalLateFee, 2) }}</strong></td>
                                        <td></td>
                                    </tr>
                                </tfoot>
                            </table>
                        @else
                            <div class="text-center py-5">
                                <span class="material-symbols-outlined" style="font-size: 48px; color: #dee2e6;">payments</span>
                                <p class="text-muted mt-3 mb-0">No payments found for this year.</p>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
function switchTab(tabId) {
    // Hide all tab contents
    const allTabs = document.querySelectorAll('.tab-content');
    allTabs.forEach(tab => {
        tab.classList.remove('active');
    });

    // Remove active class from all buttons
    const allButtons = document.querySelectorAll('.tab-btn');
    allButtons.forEach(btn => {
        btn.classList.remove('active');
    });

    // Show selected tab content
    const selectedTab = document.getElementById(tabId);
    if (selectedTab) {
        selectedTab.classList.add('active');
    }

    // Add active class to clicked button
    event.target.closest('.tab-btn').classList.add('active');
}
</script>
@endsection
