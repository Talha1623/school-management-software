@extends('layouts.app')

@section('title', 'SMS to Parent')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">SMS to Parent</h4>
            </div>

            <!-- SMS Form -->
            <form action="#" method="POST" id="smsForm">
                @csrf
                <div class="row g-2 mb-3 align-items-end">
                    <!-- Campus -->
                    <div class="col-md-4">
                        <label for="campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 15px;">apartment</span>
                            </span>
                            <select class="form-select" id="campus" name="campus" style="height: 32px; border-left: none;">
                                <option value="">Select Campus</option>
                                @foreach($campuses as $campus)
                                    <option value="{{ $campus }}" {{ old('campus', 'Main Campus') == $campus ? 'selected' : '' }}>{{ $campus }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Class -->
                    <div class="col-md-4">
                        <label for="class" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 15px;">apartment</span>
                            </span>
                            <select class="form-select" id="class" name="class" style="height: 32px; border-left: none;">
                                <option value="">Select Class</option>
                                @foreach($classes as $className)
                                    <option value="{{ $className }}">{{ $className }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <!-- Section -->
                    <div class="col-md-4">
                        <label for="section" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Section</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 15px;">person</span>
                            </span>
                            <select class="form-select" id="section" name="section" style="height: 32px; border-left: none;" disabled>
                                <option value="">Select Class First</option>
                            </select>
                        </div>
                    </div>
                </div>

                <!-- SMS Textarea -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="sms_message" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Type SMS Here</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471; align-items: start; padding-top: 12px;">
                                <span class="material-symbols-outlined" style="font-size: 18px;">mail</span>
                            </span>
                            <textarea class="form-control" id="sms_message" name="sms_message" rows="6" placeholder="Type your SMS message here..." style="border-left: none; resize: vertical;"></textarea>
                        </div>
                    </div>
                </div>

                <!-- Supported Tags -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label class="form-label mb-1 fs-12 fw-semibold" style="color: #28a745;">Supported Tags:</label>
                        <div class="d-flex flex-wrap gap-2">
                            <span class="badge bg-success" style="cursor: pointer;" onclick="insertTag('$student_name')">$student_name</span>
                            <span class="badge bg-success" style="cursor: pointer;" onclick="insertTag('$parent_name')">$parent_name</span>
                            <span class="badge bg-success" style="cursor: pointer;" onclick="insertTag('$roll_number')">$roll_number</span>
                            <span class="badge bg-success" style="cursor: pointer;" onclick="insertTag('$class_name')">$class_name</span>
                            <span class="badge bg-success" style="cursor: pointer;" onclick="insertTag('$section_name')">$section_name</span>
                            <span class="badge bg-success" style="cursor: pointer;" onclick="insertTag('$campus_name')">$campus_name</span>
                            <span class="badge bg-success" style="cursor: pointer;" onclick="insertTag('$school_name')">$school_name</span>
                        </div>
                    </div>
                </div>

                <!-- Character Counter and Date -->
                <div class="row mb-3 align-items-end">
                    <div class="col-md-6">
                        <div id="characterCounter" class="text-danger fs-12">
                            <span id="remainingChars">160</span> Characters Remaining
                        </div>
                        <div id="messageCount" class="text-danger fs-12">
                            Total <span id="totalMessages">1</span> Message
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label for="date" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Date</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 15px;">calendar_today</span>
                            </span>
                            <input type="date" class="form-control" id="date" name="date" value="{{ date('Y-m-d') }}" style="height: 32px; border-left: none;">
                        </div>
                    </div>
                </div>

                <!-- Send SMS Button -->
                <div class="row">
                    <div class="col-md-12 text-end">
                        <button type="submit" class="btn btn-success px-4 py-2 rounded-8 send-btn">
                            <span>Send SMS</span>
                            <span class="material-symbols-outlined ms-2">thumb_up</span>
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.send-btn {
    background-color: #28a745;
    border: none;
    height: 42px;
    color: white;
    font-weight: 500;
    transition: all 0.3s ease;
}

.send-btn:hover {
    background-color: #218838;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
    color: white;
}

.send-btn .material-symbols-outlined {
    font-size: 18px;
    vertical-align: middle;
}

.input-group-text {
    border-radius: 8px 0 0 8px;
}

.form-select, .form-control {
    border-radius: 0 8px 8px 0;
}

.badge {
    font-size: 11px;
    padding: 4px 8px;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const classSelect = document.getElementById('class');
    const sectionSelect = document.getElementById('section');
    const smsMessage = document.getElementById('sms_message');
    const remainingCharsSpan = document.getElementById('remainingChars');
    const totalMessagesSpan = document.getElementById('totalMessages');

    function loadSections(selectedClass) {
        if (selectedClass) {
            sectionSelect.disabled = false;
            sectionSelect.innerHTML = '<option value="">Loading...</option>';
            
            fetch(`{{ route('exam.timetable.get-sections') }}?class=${encodeURIComponent(selectedClass)}`)
                .then(response => response.json())
                .then(data => {
                    sectionSelect.innerHTML = '<option value="">Select Section</option>';
                    data.forEach(section => {
                        sectionSelect.innerHTML += `<option value="${section}">${section}</option>`;
                    });
                })
                .catch(error => {
                    console.error('Error loading sections:', error);
                    sectionSelect.innerHTML = '<option value="">Error loading sections</option>';
                });
        } else {
            sectionSelect.disabled = true;
            sectionSelect.innerHTML = '<option value="">Select Class First</option>';
        }
    }

    function updateCharacterCounter() {
        const text = smsMessage.value;
        const length = text.length;
        const charsPerMessage = 160;
        const remaining = Math.max(0, charsPerMessage - (length % charsPerMessage));
        const totalMessages = Math.ceil(length / charsPerMessage) || 1;
        
        remainingCharsSpan.textContent = remaining;
        totalMessagesSpan.textContent = totalMessages;
        
        if (remaining < 20) {
            remainingCharsSpan.parentElement.style.color = '#dc3545';
        } else {
            remainingCharsSpan.parentElement.style.color = '#28a745';
        }
    }

    function insertTag(tag) {
        const textarea = smsMessage;
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;
        const before = text.substring(0, start);
        const after = text.substring(end, text.length);
        
        textarea.value = before + tag + after;
        textarea.selectionStart = textarea.selectionEnd = start + tag.length;
        textarea.focus();
        
        updateCharacterCounter();
    }

    classSelect.addEventListener('change', function() {
        loadSections(this.value);
    });

    smsMessage.addEventListener('input', updateCharacterCounter);
    
    // Initialize counter
    updateCharacterCounter();
});
</script>
@endsection
