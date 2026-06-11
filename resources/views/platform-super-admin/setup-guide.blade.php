@extends('layouts.platform-admin')

@section('title', 'Provisioning Setup')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-4 mb-4">
            <h4 class="mb-2">Provisioning Setup Guide</h4>
            <p class="text-muted mb-0">
                Add these values in your live <code>.env</code> file, then run <code>php artisan optimize:clear</code>.
            </p>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card bg-white border border-white rounded-10 p-4 mb-4">
            <h5 class="mb-3">Current Runtime Status</h5>
            <ul class="list-group">
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    Auto Provisioning
                    <span class="badge {{ $setup['auto_provision'] ? 'bg-success' : 'bg-danger' }}">
                        {{ $setup['auto_provision'] ? 'Enabled' : 'Disabled' }}
                    </span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    Platform Base Domain
                    <span class="badge {{ !empty($setup['base_domain']) ? 'bg-success' : 'bg-danger' }}">
                        {{ !empty($setup['base_domain']) ? $setup['base_domain'] : 'Missing' }}
                    </span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    cPanel Base URL
                    <span class="badge {{ !empty($setup['cpanel_base_url']) ? 'bg-success' : 'bg-danger' }}">
                        {{ !empty($setup['cpanel_base_url']) ? 'Configured' : 'Missing' }}
                    </span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    cPanel Username
                    <span class="badge {{ !empty($setup['cpanel_username']) ? 'bg-success' : 'bg-danger' }}">
                        {{ !empty($setup['cpanel_username']) ? $setup['cpanel_username'] : 'Missing' }}
                    </span>
                </li>
                <li class="list-group-item d-flex justify-content-between align-items-center">
                    cPanel Token
                    <span class="badge {{ $setup['has_cpanel_token'] ? 'bg-success' : 'bg-danger' }}">
                        {{ $setup['has_cpanel_token'] ? 'Configured' : 'Missing' }}
                    </span>
                </li>
            </ul>
        </div>
    </div>

    <div class="col-lg-6">
        <div class="card bg-white border border-white rounded-10 p-4 mb-4">
            <h5 class="mb-3">Copy .env Block</h5>
            <textarea class="form-control" rows="12" readonly>{{ $envTemplate }}</textarea>
            <p class="small text-muted mt-3 mb-0">
                After updating <code>.env</code>, run: <code>php artisan optimize:clear</code>
            </p>
        </div>
    </div>
</div>
@endsection
