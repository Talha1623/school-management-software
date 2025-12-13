@extends('layouts.app')

@section('title', 'Leave Request')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Student Leave Request</h4>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            @if ($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <ul class="mb-0">
                        @foreach ($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            @endif

            <!-- Leave Request Form -->
            <form action="{{ route('leave-request.store') }}" method="POST" id="leaveRequestForm">
                @csrf
                <div class="row g-3">
                    <!-- Parent Phone Number -->
                    <div class="col-md-6">
                        <label for="parent_phone" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                            Parent Phone Number <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 15px;">phone</span>
                            </span>
                            <input type="text" 
                                   class="form-control" 
                                   id="parent_phone" 
                                   name="parent_phone" 
                                   placeholder="Enter parent phone number"
                                   value="{{ old('parent_phone') }}"
                                   style="height: 38px; border-left: none;"
                                   onblur="loadStudents()">
                        </div>
                        <small class="text-muted">Enter the phone number registered with the school</small>
                    </div>

                    <!-- Student Selection -->
                    <div class="col-md-6">
                        <label for="student_id" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                            Select Student <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 15px;">person</span>
                            </span>
                            <select class="form-select" 
                                    id="student_id" 
                                    name="student_id" 
                                    required
                                    style="height: 38px; border-left: none;">
                                <option value="">-- Select Student --</option>
                            </select>
                        </div>
                        <div id="studentLoading" class="text-muted fs-12 mt-1" style="display: none;">
                            <span class="spinner-border spinner-border-sm" role="status"></span> Loading students...
                        </div>
                        <div id="noStudents" class="text-danger fs-12 mt-1" style="display: none;">
                            No students found for this phone number.
                        </div>
                    </div>

                    <!-- Leave Reason -->
                    <div class="col-md-12">
                        <label for="leave_reason" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                            Leave Reason <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 15px;">description</span>
                            </span>
                            <textarea class="form-control" 
                                      id="leave_reason" 
                                      name="leave_reason" 
                                      rows="3" 
                                      placeholder="Enter reason for leave"
                                      required
                                      style="border-left: none; resize: vertical;">{{ old('leave_reason') }}</textarea>
                        </div>
                    </div>

                    <!-- From Date -->
                    <div class="col-md-6">
                        <label for="from_date" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                            From Date <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 15px;">calendar_today</span>
                            </span>
                            <input type="date" 
                                   class="form-control" 
                                   id="from_date" 
                                   name="from_date" 
                                   value="{{ old('from_date') }}"
                                   required
                                   min="{{ date('Y-m-d') }}"
                                   style="height: 38px; border-left: none;">
                        </div>
                    </div>

                    <!-- To Date -->
                    <div class="col-md-6">
                        <label for="to_date" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">
                            To Date <span class="text-danger">*</span>
                        </label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 15px;">event</span>
                            </span>
                            <input type="date" 
                                   class="form-control" 
                                   id="to_date" 
                                   name="to_date" 
                                   value="{{ old('to_date') }}"
                                   required
                                   min="{{ date('Y-m-d') }}"
                                   style="height: 38px; border-left: none;">
                        </div>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="row mt-4">
                    <div class="col-12 text-end">
                        <button type="submit" class="btn btn-primary px-4 py-2 rounded-8">
                            <span class="material-symbols-outlined me-2" style="font-size: 18px; vertical-align: middle;">send</span>
                            Submit Leave Request
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function loadStudents() {
    const phone = document.getElementById('parent_phone').value.trim();
    const studentSelect = document.getElementById('student_id');
    const studentLoading = document.getElementById('studentLoading');
    const noStudents = document.getElementById('noStudents');

    // Clear previous options
    studentSelect.innerHTML = '<option value="">-- Select Student --</option>';
    noStudents.style.display = 'none';

    if (!phone) {
        return;
    }

    // Show loading
    studentLoading.style.display = 'block';

    // Fetch students
    fetch(`{{ route('leave-request.get-students') }}?phone=${encodeURIComponent(phone)}`)
        .then(response => response.json())
        .then(data => {
            studentLoading.style.display = 'none';

            if (data.students && data.students.length > 0) {
                data.students.forEach(student => {
                    const option = document.createElement('option');
                    option.value = student.id;
                    option.textContent = `${student.name} (${student.code}) - ${student.class} ${student.section}`;
                    studentSelect.appendChild(option);
                });
            } else {
                noStudents.style.display = 'block';
            }
        })
        .catch(error => {
            console.error('Error:', error);
            studentLoading.style.display = 'none';
            noStudents.style.display = 'block';
            noStudents.textContent = 'Error loading students. Please try again.';
        });
}

// Validate to_date is after from_date
document.getElementById('from_date').addEventListener('change', function() {
    const fromDate = this.value;
    const toDateInput = document.getElementById('to_date');
    if (fromDate) {
        toDateInput.min = fromDate;
        if (toDateInput.value && toDateInput.value < fromDate) {
            toDateInput.value = fromDate;
        }
    }
});

document.getElementById('to_date').addEventListener('change', function() {
    const fromDate = document.getElementById('from_date').value;
    const toDate = this.value;
    if (fromDate && toDate && toDate < fromDate) {
        alert('To date must be equal to or after from date.');
        this.value = fromDate;
    }
});
</script>
@endsection

