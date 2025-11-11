@extends('layouts.app')

@section('title', 'Send Marks to Parents - Combine Result')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-4 mb-4" style="background-color: #f5f5f5;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0 fs-18 fw-semibold" style="color: #003471;">Send Marks to Parents - Combine Result</h4>
            </div>

            <form id="sendMarksCombinedForm" method="POST" action="#" style="max-width: 800px;">
                @csrf
                
                @php
                    $fields = [
                        ['id' => 'campus', 'name' => 'campus', 'label' => 'Campus', 'type' => 'select', 'options' => $campuses, 'placeholder' => 'Select Campus', 'default' => 'Main Campus', 'disabled' => false],
                        ['id' => 'class', 'name' => 'class', 'label' => 'Class', 'type' => 'select', 'options' => $classes, 'placeholder' => 'Select a class', 'default' => '', 'disabled' => false],
                        ['id' => 'section', 'name' => 'section', 'label' => 'Section', 'type' => 'select', 'options' => [], 'placeholder' => 'Select a class first', 'default' => '', 'disabled' => true],
                        ['id' => 'test_type', 'name' => 'test_type', 'label' => 'Test Type', 'type' => 'select', 'options' => $testTypes, 'placeholder' => 'Select Test Type', 'default' => '', 'disabled' => false],
                        ['id' => 'from_date', 'name' => 'from_date', 'label' => 'From Date', 'type' => 'date', 'placeholder' => '', 'default' => '', 'disabled' => false],
                        ['id' => 'to_date', 'name' => 'to_date', 'label' => 'To Date', 'type' => 'date', 'placeholder' => '', 'default' => '', 'disabled' => false],
                        ['id' => 'notification', 'name' => 'notification', 'label' => 'Notification', 'type' => 'select', 'options' => ['whatsapp_sms' => 'WhatsApp & SMS', 'whatsapp' => 'WhatsApp', 'sms' => 'SMS'], 'placeholder' => '', 'default' => 'whatsapp_sms', 'disabled' => false],
                        ['id' => 'session', 'name' => 'session', 'label' => 'Session', 'type' => 'select', 'options' => $sessions, 'placeholder' => 'Select Session', 'default' => '', 'disabled' => false],
                    ];
                @endphp

                @foreach($fields as $field)
                <div class="row mb-3 align-items-center">
                    <div class="col-md-3">
                        <label for="{{ $field['id'] }}" class="form-label mb-0 fs-14 fw-semibold" style="color: #333;">{{ $field['label'] }}</label>
                    </div>
                    <div class="col-md-9">
                        @if($field['type'] === 'select')
                        <div class="input-group">
                            <span class="input-group-text input-icon">
                                <span class="material-symbols-outlined">business</span>
                            </span>
                            <select class="form-select select-field" id="{{ $field['id'] }}" name="{{ $field['name'] }}" {{ $field['disabled'] ? 'disabled' : '' }}>
                                @if($field['id'] === 'notification')
                                    @foreach($field['options'] as $value => $text)
                                        <option value="{{ $value }}" {{ old($field['name'], $field['default']) == $value ? 'selected' : '' }}>{{ $text }}</option>
                                    @endforeach
                                @else
                                    <option value="">{{ $field['placeholder'] }}</option>
                                    @foreach($field['options'] as $option)
                                        <option value="{{ $option }}" {{ (old($field['name'], $field['default']) == $option) ? 'selected' : '' }}>{{ $option }}</option>
                                    @endforeach
                                @endif
                            </select>
                        </div>
                        @elseif($field['type'] === 'date')
                        <div class="input-group">
                            <span class="input-group-text input-icon">
                                <span class="material-symbols-outlined">calendar_today</span>
                            </span>
                            <input type="date" class="form-control select-field" id="{{ $field['id'] }}" name="{{ $field['name'] }}" value="{{ old($field['name'], $field['default']) }}" {{ $field['disabled'] ? 'disabled' : '' }}>
                        </div>
                        @endif
                    </div>
                </div>
                @endforeach

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
.input-icon {
    background-color: #f0f0f0;
    border: 1px solid #ddd;
    border-right: none;
    border-radius: 8px 0 0 8px;
    padding: 0.5rem 0.75rem;
}

.input-icon .material-symbols-outlined {
    font-size: 18px;
    color: #666;
}

.select-field {
    border: 1px solid #ddd;
    border-left: none;
    border-radius: 0 8px 8px 0;
    height: 42px;
    background-color: #f9f9f9;
}

.select-field:focus,
.select-field:active {
    border-color: #003471;
    box-shadow: 0 0 0 0.2rem rgba(0, 52, 113, 0.25);
    background-color: #fff;
}

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
    const classSelect = document.getElementById('class');
    const sectionSelect = document.getElementById('section');

    function loadSections(selectedClass) {
        if (selectedClass) {
            sectionSelect.disabled = false;
            sectionSelect.innerHTML = '<option value="">Loading...</option>';
            
            fetch(`{{ route('test.send-marks.get-sections-combined') }}?class=${encodeURIComponent(selectedClass)}`)
                .then(response => response.json())
                .then(data => {
                    sectionSelect.innerHTML = '<option value="">Select a section</option>';
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
            sectionSelect.innerHTML = '<option value="">Select a class first</option>';
        }
    }

    classSelect.addEventListener('change', function() {
        loadSections(this.value);
    });

    document.getElementById('sendMarksCombinedForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        const data = Object.fromEntries(formData);
        
        if (!data.campus || !data.class || !data.section || !data.test_type || !data.from_date || !data.to_date || !data.notification || !data.session) {
            alert('Please fill in all fields');
            return;
        }
        
        if (new Date(data.from_date) > new Date(data.to_date)) {
            alert('From Date cannot be greater than To Date');
            return;
        }
        
        console.log('Form data:', data);
        alert('SMS will be sent to parents for the combine result.');
    });
});
</script>
@endsection
