@extends('layouts.app')

@section('title', 'Notification to Parents')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Notification to Parents</h4>
            </div>

            <!-- Notification Form -->
            <form action="#" method="POST" id="notificationForm">
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

                    <!-- Title -->
                    <div class="col-md-4">
                        <label for="title" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Title</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 15px;">title</span>
                            </span>
                            <input type="text" class="form-control" id="title" name="title" placeholder="Enter notification title..." value="{{ old('title') }}" style="height: 32px; border-left: none;">
                        </div>
                    </div>

                    <!-- Date -->
                    <div class="col-md-4">
                        <label for="date" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Date</label>
                        <div class="input-group input-group-sm">
                            <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 15px;">calendar_today</span>
                            </span>
                            <input type="date" class="form-control" id="date" name="date" value="{{ date('Y-m-d') }}" style="height: 32px; border-left: none;">
                        </div>
                    </div>
                </div>

                <!-- Body Textarea -->
                <div class="row mb-3">
                    <div class="col-md-12">
                        <label for="body" class="form-label mb-1 fs-12 fw-semibold" style="color: #003471;">Body</label>
                        <div class="input-group">
                            <span class="input-group-text" style="background-color: #f0f4ff; border-color: #e0e7ff; color: #003471; align-items: start; padding-top: 12px;">
                                <span class="material-symbols-outlined" style="font-size: 18px;">description</span>
                            </span>
                            <textarea class="form-control" id="body" name="body" rows="6" placeholder="Enter notification body/message here..." style="border-left: none; resize: vertical;">{{ old('body') }}</textarea>
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

                <!-- Send Notification Button -->
                <div class="row">
                    <div class="col-md-12 text-end">
                        <button type="submit" class="btn btn-success px-4 py-2 rounded-8 send-btn">
                            <span>Send Notification</span>
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
    const bodyTextarea = document.getElementById('body');

    function insertTag(tag) {
        const textarea = bodyTextarea;
        const start = textarea.selectionStart;
        const end = textarea.selectionEnd;
        const text = textarea.value;
        const before = text.substring(0, start);
        const after = text.substring(end, text.length);
        
        textarea.value = before + tag + after;
        textarea.selectionStart = textarea.selectionEnd = start + tag.length;
        textarea.focus();
    }

    // Make insertTag available globally
    window.insertTag = insertTag;
});
</script>
@endsection
