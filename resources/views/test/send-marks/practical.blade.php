@extends('layouts.app')

@section('title', 'Send Marks to Parents - For Practical Test')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Send Marks to Parents - For Practical Test</h4>
            </div>

            <form id="sendMarksForm" method="POST" action="#" id="filterForm">
                @csrf
                
                <div class="row g-2 mb-3 align-items-end">
                    <!-- Campus -->
                    <div class="col-md-2">
                        <label for="campus" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Campus</label>
                        <select class="form-select form-select-sm" id="campus" name="campus" style="height: 32px;">
                            <option value="">Select Campus</option>
                            @foreach($campuses as $campus)
                                <option value="{{ $campus }}" {{ (old('campus', 'Main Campus') == $campus) ? 'selected' : '' }}>{{ $campus }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Class -->
                    <div class="col-md-2">
                        <label for="class" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Class</label>
                        <select class="form-select form-select-sm" id="class" name="class" style="height: 32px;">
                            <option value="">Select a class</option>
                            @foreach($classes as $className)
                                <option value="{{ $className }}" {{ old('class') == $className ? 'selected' : '' }}>{{ $className }}</option>
                            @endforeach
                        </select>
                    </div>

                    <!-- Section -->
                    <div class="col-md-2">
                        <label for="section" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Section</label>
                        <select class="form-select form-select-sm" id="section" name="section" style="height: 32px;" disabled>
                            <option value="">Select a class first</option>
                        </select>
                    </div>

                    <!-- Subject -->
                    <div class="col-md-2">
                        <label for="subject" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Subject</label>
                        <select class="form-select form-select-sm" id="subject" name="subject" style="height: 32px;" disabled>
                            <option value="">Select a section first</option>
                        </select>
                    </div>

                    <!-- Test -->
                    <div class="col-md-2">
                        <label for="test" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Test</label>
                        <select class="form-select form-select-sm" id="test" name="test" style="height: 32px;" disabled>
                            <option value="">Select a subject first</option>
                        </select>
                    </div>

                    <!-- Notification -->
                    <div class="col-md-2">
                        <label for="notification" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Notification</label>
                        <select class="form-select form-select-sm" id="notification" name="notification" style="height: 32px;">
                            <option value="whatsapp_sms" {{ old('notification', 'whatsapp_sms') == 'whatsapp_sms' ? 'selected' : '' }}>WhatsApp & SMS</option>
                            <option value="whatsapp" {{ old('notification') == 'whatsapp' ? 'selected' : '' }}>WhatsApp</option>
                            <option value="sms" {{ old('notification') == 'sms' ? 'selected' : '' }}>SMS</option>
                        </select>
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
}

.send-btn:hover {
    background-color: #218838;
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(40, 167, 69, 0.3);
}

.send-btn .material-symbols-outlined {
    font-size: 18px;
    vertical-align: middle;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const selects = {
        campus: document.getElementById('campus'),
        class: document.getElementById('class'),
        section: document.getElementById('section'),
        subject: document.getElementById('subject'),
        test: document.getElementById('test')
    };

    function loadOptions(select, route, params, placeholder) {
        select.disabled = false;
        select.innerHTML = '<option value="">Loading...</option>';
        
        const queryString = new URLSearchParams(params).toString();
        fetch(`${route}?${queryString}`)
            .then(response => response.json())
            .then(data => {
                select.innerHTML = `<option value="">${placeholder}</option>`;
                data.forEach(item => {
                    select.innerHTML += `<option value="${item}">${item}</option>`;
                });
            })
            .catch(error => {
                console.error('Error loading options:', error);
                select.innerHTML = `<option value="">Error loading</option>`;
            });
    }

    function resetSelect(select, placeholder) {
        select.disabled = true;
        select.innerHTML = `<option value="">${placeholder}</option>`;
    }

    selects.class.addEventListener('change', function() {
        const selectedClass = this.value;
        if (selectedClass) {
            loadOptions(selects.section, '{{ route("test.send-marks.get-sections") }}', {class: selectedClass}, 'Select a section');
            resetSelect(selects.subject, 'Select a section first');
            resetSelect(selects.test, 'Select a subject first');
        } else {
            resetSelect(selects.section, 'Select a class first');
            resetSelect(selects.subject, 'Select a section first');
            resetSelect(selects.test, 'Select a subject first');
        }
    });

    selects.section.addEventListener('change', function() {
        const selectedSection = this.value;
        const selectedClass = selects.class.value;
        if (selectedSection && selectedClass) {
            loadOptions(selects.subject, '{{ route("test.send-marks.get-subjects") }}', {section: selectedSection, class: selectedClass}, 'Select a subject');
            resetSelect(selects.test, 'Select a subject first');
        } else {
            resetSelect(selects.subject, 'Select a section first');
            resetSelect(selects.test, 'Select a subject first');
        }
    });

    selects.subject.addEventListener('change', function() {
        const selectedSubject = this.value;
        const selectedSection = selects.section.value;
        const selectedClass = selects.class.value;
        if (selectedSubject && selectedSection && selectedClass) {
            loadOptions(selects.test, '{{ route("test.send-marks.get-tests") }}', {subject: selectedSubject, section: selectedSection, class: selectedClass}, 'Select a test');
        } else {
            resetSelect(selects.test, 'Select a subject first');
        }
    });

    document.getElementById('sendMarksForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        
        if (!data.campus || !data.class || !data.section || !data.subject || !data.test || !data.notification) {
            alert('Please fill in all fields');
            return;
        }
        
        console.log('Form data:', data);
        alert('SMS will be sent to parents for the selected test marks.');
    });
});
</script>
@endsection
