@extends('layouts.app')

@section('title', 'Blank Admission Form')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3 mb-4">
            <div class="d-flex justify-content-between align-items-center mb-3">
                <h4 class="mb-0 fs-16 fw-semibold">Blank Admission Form</h4>
                <a href="{{ route('admission.report.blank.print') }}?auto_print=1" target="_blank" class="btn btn-sm btn-primary" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%); border: none;">
                    <span class="material-symbols-outlined" style="font-size: 16px; vertical-align: middle;">print</span>
                    Print
                </a>
            </div>

            <div class="alert alert-info mb-0">
                <p class="mb-0">Use Print to open a clean A4 layout with your school name (no sidebar).</p>
            </div>
        </div>
    </div>
</div>
@endsection
