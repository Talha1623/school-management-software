@extends('layouts.app')

@section('title', 'Exam Settings')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-4 mb-4">
            <div class="d-flex align-items-center mb-4">
                <span class="material-symbols-outlined me-2" style="font-size: 28px; color: #003471;">quiz</span>
                <h3 class="mb-0 fw-bold" style="color: #003471;">Exam Settings</h3>
            </div>

            @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            @endif

            <form method="POST" action="{{ route('settings.exam.store') }}">
                @csrf
                <div class="row">
                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm rounded-10 p-4 h-100">
                            <h5 class="mb-4 fw-semibold" style="color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">description</span>
                                Admit Card INSTRUCTIONS
                            </h5>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Instructions Content</label>
                                <textarea class="form-control" name="admit_card_instructions" id="admit_card_instructions" rows="8" placeholder="Enter admit card instructions here...">{{ old('admit_card_instructions', $settings->admit_card_instructions ?? '') }}</textarea>
                            </div>
                        </div>
                    </div>

                    <div class="col-md-6 mb-4">
                        <div class="card border-0 shadow-sm rounded-10 p-4 h-100">
                            <h5 class="mb-4 fw-semibold" style="color: #003471;">
                                <span class="material-symbols-outlined" style="font-size: 20px; vertical-align: middle;">cancel</span>
                                Fail student if
                            </h5>
                            
                            <div class="mb-3">
                                <label class="form-label fw-medium">Select Option</label>
                                <select class="form-select" name="fail_student_if" id="fail_student_if">
                                    <option value="">Select option</option>
                                    <option value="less_than_passing" {{ old('fail_student_if', $settings->fail_student_if ?? '') == 'less_than_passing' ? 'selected' : '' }}>Less than passing marks</option>
                                    <option value="fail_any_subject" {{ old('fail_student_if', $settings->fail_student_if ?? '') == 'fail_any_subject' ? 'selected' : '' }}>Fail any subject</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end gap-2 mt-4">
                    <button type="button" class="btn btn-outline-secondary">Cancel</button>
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
// Ensure sidebar stays open on Exam Settings page
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

