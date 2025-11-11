@extends('layouts.app')

@section('title', 'Print Marksheets - For Practical Test')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-4 mb-4" style="background-color: #f5f5f5;">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h4 class="mb-0 fs-18 fw-semibold" style="color: #003471;">Print Marksheets - For Practical Test</h4>
            </div>

            <form id="printMarksheetsForm" method="GET" action="#" style="max-width: 800px;">
                @php
                    $fields = [
                        ['id' => 'campus', 'name' => 'campus', 'label' => 'Campus', 'options' => $campuses, 'placeholder' => 'Select Campus', 'default' => 'Main Campus', 'disabled' => false],
                        ['id' => 'class', 'name' => 'class', 'label' => 'Class', 'options' => $classes, 'placeholder' => 'Select a class', 'default' => '', 'disabled' => false],
                        ['id' => 'section', 'name' => 'section', 'label' => 'Section', 'options' => [], 'placeholder' => 'Select a class first', 'default' => '', 'disabled' => true],
                        ['id' => 'subject', 'name' => 'subject', 'label' => 'Subject', 'options' => [], 'placeholder' => 'Select a section first', 'default' => '', 'disabled' => true],
                        ['id' => 'test', 'name' => 'test', 'label' => 'Test', 'options' => [], 'placeholder' => 'Select a subject first', 'default' => '', 'disabled' => true],
                    ];
                @endphp

                @foreach($fields as $field)
                <div class="row mb-3 align-items-center">
                    <div class="col-md-3">
                        <label for="{{ $field['id'] }}" class="form-label mb-0 fs-14 fw-semibold" style="color: #333;">{{ $field['label'] }}</label>
                    </div>
                    <div class="col-md-9">
                        <div class="input-group">
                            <span class="input-group-text input-icon">
                                <span class="material-symbols-outlined">business</span>
                            </span>
                            <select class="form-select select-field" id="{{ $field['id'] }}" name="{{ $field['name'] }}" {{ $field['disabled'] ? 'disabled' : '' }}>
                                <option value="">{{ $field['placeholder'] }}</option>
                                @foreach($field['options'] as $option)
                                    <option value="{{ $option }}" {{ (old($field['name'], $field['default']) == $option) ? 'selected' : '' }}>{{ $option }}</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
                @endforeach

                <!-- Filter Button -->
                <div class="row">
                    <div class="col-md-12 text-end">
                        <button type="submit" class="btn px-4 py-2 rounded-8 filter-btn">
                            <span class="material-symbols-outlined me-2">filter_alt</span>
                            <span>Filter</span>
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

.filter-btn {
    background: linear-gradient(135deg, #003471 0%, #004a9f 100%);
    color: white;
    border: none;
    height: 42px;
}

.filter-btn:hover {
    background: linear-gradient(135deg, #004a9f 0%, #003471 100%);
    transform: translateY(-1px);
    box-shadow: 0 4px 8px rgba(0, 52, 113, 0.3);
}

.filter-btn .material-symbols-outlined {
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
            loadOptions(selects.section, '{{ route("test.print-marksheets.get-sections") }}', {class: selectedClass}, 'Select a section');
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
            loadOptions(selects.subject, '{{ route("test.print-marksheets.get-subjects") }}', {section: selectedSection, class: selectedClass}, 'Select a subject');
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
            loadOptions(selects.test, '{{ route("test.print-marksheets.get-tests") }}', {subject: selectedSubject, section: selectedSection, class: selectedClass}, 'Select a test');
        } else {
            resetSelect(selects.test, 'Select a subject first');
        }
    });
});
</script>
@endsection
