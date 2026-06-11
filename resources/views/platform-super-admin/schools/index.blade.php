@extends('layouts.platform-admin')

@section('title', 'Manage Schools')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center mb-3">
            <h4 class="mb-0">Schools Management & Student Limits</h4>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#addSchoolModal">
                <i class="ri-add-line me-1"></i> Add School
            </button>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 mb-4" style="background: linear-gradient(135deg, #003471 0%, #004a9f 100%);">
            <p class="mb-1 text-white-50 fs-12">Total Schools</p>
            <h3 class="mb-0 text-white">{{ $totalSchools }}</h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 mb-4" style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%);">
            <p class="mb-1 text-white-50 fs-12">Active Schools</p>
            <h3 class="mb-0 text-white">{{ $activeSchools }}</h3>
        </div>
    </div>
    <div class="col-md-4">
        <div class="card border-0 shadow-sm p-3 mb-4" style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%);">
            <p class="mb-1 text-white-50 fs-12">Inactive Schools</p>
            <h3 class="mb-0 text-white">{{ $inactiveSchools }}</h3>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm p-3 mb-4" style="background: linear-gradient(135deg, #6f42c1 0%, #563d7c 100%);">
            <p class="mb-1 text-white-50 fs-12">Schools With Student Limit</p>
            <h3 class="mb-0 text-white">{{ $limitedSchools }}</h3>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm p-3 mb-4" style="background: linear-gradient(135deg, #17a2b8 0%, #138496 100%);">
            <p class="mb-1 text-white-50 fs-12">Total Allowed Students (All Schools)</p>
            <h3 class="mb-0 text-white">{{ number_format($totalStudentCapacity) }}</h3>
        </div>
    </div>

    <div class="col-12">
        <div class="card bg-white border border-white rounded-10 p-3">
            <h5 class="mb-3">Schools List</h5>
            <div class="table-responsive">
                <table class="table table-striped table-bordered align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Name</th>
                            <th>Subdomain</th>
                            <th>Domain</th>
                            <th>Owner</th>
                            <th>Email</th>
                            <th>Database</th>
                            <th>Student Limit</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($schools as $school)
                            <tr>
                                <td>{{ $loop->iteration + (($schools->currentPage() - 1) * $schools->perPage()) }}</td>
                                <td>{{ $school->name }}</td>
                                <td>{{ $school->subdomain }}</td>
                                <td>{{ $school->domain }}</td>
                                <td>{{ $school->owner_name }}</td>
                                <td>{{ $school->owner_email }}</td>
                                <td>{{ $school->db_database }}</td>
                                <td style="min-width: 220px;">
                                    <form method="POST" action="{{ route('platform-admin.schools.update-student-limit', $school->id) }}" class="d-flex gap-2 align-items-center">
                                        @csrf
                                        @method('PATCH')
                                        <input
                                            type="number"
                                            name="student_limit"
                                            min="1"
                                            class="form-control form-control-sm"
                                            value="{{ $school->student_limit }}"
                                            placeholder="No limit"
                                        >
                                        <button type="submit" class="btn btn-sm btn-info text-white">Save</button>
                                    </form>
                                </td>
                                <td>
                                    <span class="badge {{ $school->status === 'active' ? 'bg-success' : 'bg-secondary' }}">
                                        {{ ucfirst($school->status) }}
                                    </span>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <form method="POST" action="{{ route('platform-admin.schools.toggle-status', $school->id) }}">
                                            @csrf
                                            @method('PATCH')
                                            <button type="submit" class="btn btn-sm {{ $school->status === 'active' ? 'btn-warning' : 'btn-success' }}">
                                                {{ $school->status === 'active' ? 'Deactivate' : 'Activate' }}
                                            </button>
                                        </form>

                                        <form method="POST" action="{{ route('platform-admin.schools.destroy', $school->id) }}" onsubmit="return confirm('Are you sure? This will delete the school and its database.');">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-danger">Delete</button>
                                        </form>
                                    </div>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="10" class="text-center text-muted">No schools added yet.</td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            <div class="mt-3">
                {{ $schools->links() }}
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="addSchoolModal" tabindex="-1" aria-labelledby="addSchoolModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="addSchoolModalLabel">Add School</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" action="{{ route('platform-admin.schools.store') }}">
                @csrf
                <div class="modal-body school-modal-body">
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">School Name</label>
                            <input type="text" name="name" class="form-control" value="{{ old('name') }}" required>
                            @error('name') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Subdomain</label>
                            <input type="text" name="subdomain" class="form-control" placeholder="talha" value="{{ old('subdomain') }}" required>
                            @error('subdomain') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Owner Name</label>
                            <input type="text" name="owner_name" class="form-control" value="{{ old('owner_name') }}" required>
                            @error('owner_name') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Owner Email</label>
                            <input type="email" name="owner_email" class="form-control" value="{{ old('owner_email') }}" required>
                            @error('owner_email') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Owner Password</label>
                            <input type="password" name="owner_password" class="form-control" required>
                            @error('owner_password') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Status</label>
                            <select name="status" class="form-select" required>
                                <option value="active" {{ old('status') === 'active' ? 'selected' : '' }}>Active</option>
                                <option value="inactive" {{ old('status') === 'inactive' ? 'selected' : '' }}>Inactive</option>
                            </select>
                            @error('status') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">Allowed Students (optional)</label>
                            <input type="number" name="student_limit" min="1" class="form-control" value="{{ old('student_limit') }}" placeholder="e.g. 500">
                            @error('student_limit') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    </div>

                    <h6 class="mb-2 border-top pt-3">School Database</h6>
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label class="form-label">DB Host</label>
                            <input type="text" name="db_host" class="form-control" value="{{ old('db_host', '127.0.0.1') }}" required>
                            @error('db_host') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">DB Port</label>
                            <input type="number" name="db_port" class="form-control" value="{{ old('db_port', '3306') }}" required>
                            @error('db_port') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">DB Name</label>
                            <input type="text" name="db_database" class="form-control" placeholder="school_talha" value="{{ old('db_database') }}" required>
                            @error('db_database') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-6 mb-3">
                            <label class="form-label">DB Username</label>
                            <input type="text" name="db_username" class="form-control" value="{{ old('db_username', 'root') }}" required>
                            @error('db_username') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                        <div class="col-md-12 mb-3">
                            <label class="form-label">DB Password</label>
                            <input type="password" name="db_password" class="form-control">
                            @error('db_password') <small class="text-danger">{{ $message }}</small> @enderror
                        </div>
                    </div>
                </div>
                <div class="modal-footer school-modal-footer">
                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Close</button>
                    <button type="submit" class="btn btn-primary">Save School</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        @if ($errors->any())
            const modalElement = document.getElementById('addSchoolModal');
            if (modalElement && window.bootstrap) {
                const modal = new bootstrap.Modal(modalElement);
                modal.show();
            }
        @endif
    });
</script>
@endpush

@push('styles')
<style>
    .school-modal-body {
        max-height: 68vh;
        overflow-y: auto;
    }

    .school-modal-footer {
        position: sticky;
        bottom: 0;
        background: #fff;
        border-top: 1px solid #dee2e6;
        z-index: 2;
    }
</style>
@endpush
