@extends('layouts.app')

@section('title', 'Live Chat')

@section('content')
<div class="row">
    <div class="col-12">
        <!-- Page Header -->
        <div class="d-flex align-items-center mb-4">
            <h2 class="mb-0 fs-20 fw-semibold text-dark me-2">Live Chat</h2>
            <span class="material-symbols-outlined text-secondary" style="font-size: 20px;">chat</span>
        </div>

        <!-- Live Chat Container -->
        <div class="card bg-white border border-white rounded-10 p-4">
            <div class="row">
                <!-- Chat List Sidebar -->
                <div class="col-md-4 border-end">
                    <div class="d-flex align-items-center justify-content-between mb-3">
                        <h5 class="mb-0 fs-16 fw-semibold">Teachers</h5>
                    </div>
                    
                    <!-- Search Box -->
                    <div class="mb-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0">
                                <span class="material-symbols-outlined" style="font-size: 16px;">search</span>
                            </span>
                            <input type="text" class="form-control border-start-0" placeholder="Search teachers..." id="teacher-search-input" oninput="filterTeachers()">
                        </div>
                    </div>

                    <!-- Chat List -->
                    <div class="chat-list" style="max-height: 600px; overflow-y: auto;" id="teacher-list">
                        @forelse($teachers as $t)
                            @php
                                $isActive = isset($selectedTeacher) && $selectedTeacher && $selectedTeacher->id === $t->id;
                            @endphp
                            <a href="{{ route('live-chat', ['teacher_id' => $t->id]) }}"
                               class="text-decoration-none text-dark">
                                <div class="chat-item p-3 border-bottom cursor-pointer {{ $isActive ? 'bg-light' : '' }}"
                                     data-teacher-name="{{ strtolower($t->name) }}"
                                     style="transition: background-color 0.2s;">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white" style="width: 45px; height: 45px;">
                                                <span class="material-symbols-outlined">person</span>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <h6 class="mb-0 fs-14 fw-semibold">{{ $t->name }}</h6>
                                            </div>
                                            <p class="mb-0 text-muted fs-12" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                {{ $t->designation ?? 'Teacher' }} @if($t->campus) - {{ $t->campus }} @endif
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @empty
                            <div class="p-3 text-muted">
                                No teachers found.
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Chat Area -->
                <div class="col-md-8">
                    <div class="d-flex flex-column" style="height: 700px;">
                        <!-- Chat Header -->
                        <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white me-3" style="width: 40px; height: 40px;">
                                    <span class="material-symbols-outlined">person</span>
                                </div>
                                <div>
                                    @if(isset($selectedTeacher) && $selectedTeacher)
                                        <h6 class="mb-0 fs-15 fw-semibold">{{ $selectedTeacher->name }}</h6>
                                        <small class="text-muted fs-12">{{ $selectedTeacher->designation ?? 'Teacher' }}</small>
                                    @else
                                        <h6 class="mb-0 fs-15 fw-semibold">Select a Teacher</h6>
                                        <small class="text-muted fs-12">From the left list to start chat</small>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Messages Area -->
                        <div class="flex-grow-1 p-3" style="overflow-y: auto; background-color: #f8f9fa;">
                            @if(isset($selectedTeacher) && $selectedTeacher)
                                @forelse($messages as $msg)
                                    @php
                                        $isAdmin = $msg['from_type'] === 'admin';
                                    @endphp
                                    <div class="mb-3 {{ $isAdmin ? 'd-flex justify-content-end' : '' }}">
                                        <div class="d-flex align-items-start {{ $isAdmin ? 'justify-content-end' : '' }}">
                                            @if(!$isAdmin)
                                                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white me-2" style="width: 32px; height: 32px; flex-shrink: 0;">
                                                    <span class="material-symbols-outlined" style="font-size: 16px;">person</span>
                                                </div>
                                            @endif
                                            <div class="flex-grow-1 d-flex {{ $isAdmin ? 'justify-content-end' : '' }}">
                                                <div class="{{ $isAdmin ? 'bg-primary text-white' : 'bg-white' }} rounded-3 p-3 shadow-sm" style="max-width: 70%;">
                                                    @if($msg['text'])
                                                        <p class="mb-1 fs-14">{{ $msg['text'] }}</p>
                                                    @endif
                                                    @if($msg['attachment_url'])
                                                        <div class="mt-2">
                                                            @if($msg['attachment_type'] === 'image')
                                                                <a href="{{ $msg['attachment_url'] }}" download class="d-inline-block text-decoration-none">
                                                                    <img src="{{ $msg['attachment_url'] }}" alt="Attachment" class="img-thumbnail" style="max-width: 150px; max-height: 150px; width: auto; height: auto; border-radius: 8px; cursor: pointer; object-fit: contain; display: block;" onerror="this.onerror=null; this.src='data:image/svg+xml,%3Csvg xmlns=\'http://www.w3.org/2000/svg\' width=\'150\' height=\'150\'%3E%3Crect fill=\'%23ddd\' width=\'150\' height=\'150\'/%3E%3Ctext fill=\'%23999\' font-family=\'sans-serif\' font-size=\'12\' x=\'50%25\' y=\'50%25\' text-anchor=\'middle\' dy=\'.3em\'%3EImage not found%3C/text%3E%3C/svg%3E';">
                                                                    <small class="text-muted d-block mt-1" style="font-size: 10px;">Click to download</small>
                                                                </a>
                                                            @elseif($msg['attachment_type'] === 'pdf')
                                                                <a href="{{ $msg['attachment_url'] }}" download class="text-decoration-none d-inline-flex align-items-center gap-1 {{ $isAdmin ? 'text-white' : 'text-primary' }}" style="cursor: pointer;">
                                                                    <span class="material-symbols-outlined" style="font-size: 20px;">picture_as_pdf</span>
                                                                    <span>PDF Attachment - Click to download</span>
                                                                </a>
                                                            @else
                                                                <a href="{{ $msg['attachment_url'] }}" download class="text-decoration-none d-inline-flex align-items-center gap-1 {{ $isAdmin ? 'text-white' : 'text-primary' }}" style="cursor: pointer;">
                                                                    <span class="material-symbols-outlined" style="font-size: 20px;">attach_file</span>
                                                                    <span>Document Attachment - Click to download</span>
                                                                </a>
                                                            @endif
                                                        </div>
                                                    @endif
                                                    <small class="{{ $isAdmin ? 'text-white-50' : 'text-muted' }} fs-11 d-block mt-1">{{ $msg['created_at'] }}</small>
                                                </div>
                                            </div>
                                            @if($isAdmin)
                                                <div class="rounded-circle bg-success d-flex align-items-center justify-content-center text-white ms-2" style="width: 32px; height: 32px; flex-shrink: 0;">
                                                    <span class="material-symbols-outlined" style="font-size: 16px;">person</span>
                                                </div>
                                            @endif
                                        </div>
                                    </div>
                                @empty
                                    <div class="h-100 d-flex align-items-center justify-content-center">
                                        <p class="text-muted mb-0">No messages yet. Start a conversation below.</p>
                                    </div>
                                @endforelse
                            @else
                                <div class="h-100 d-flex align-items-center justify-content-center">
                                    <p class="text-muted mb-0">Select a teacher from the left to view and send messages.</p>
                                </div>
                            @endif
                        </div>

                        <!-- Message Input -->
                        <div class="p-3 border-top">
                            @if(isset($selectedTeacher) && $selectedTeacher)
                                <form action="{{ route('live-chat.send-teacher', $selectedTeacher->id) }}" method="POST" enctype="multipart/form-data" id="admin-chat-form">
                                    @csrf
                                    <div id="admin-file-name-display" class="mb-2 text-muted fs-12" style="display: none;"></div>
                                    <div class="d-flex align-items-center gap-2">
                                        <label class="btn btn-sm btn-outline-secondary mb-0" for="admin-attachment-input" style="cursor: pointer;">
                                            <span class="material-symbols-outlined" style="font-size: 20px;">attach_file</span>
                                            <input type="file" name="attachment" id="admin-attachment-input" class="d-none" accept="image/*,.pdf,.doc,.docx">
                                        </label>
                                        <input type="text" class="form-control" name="text" id="admin-message-text" placeholder="Type a message...">
                                        <button class="btn btn-sm btn-primary" type="submit">
                                            <span class="material-symbols-outlined" style="font-size: 20px;">send</span>
                                        </button>
                                    </div>
                                </form>
                            @else
                                <div class="text-muted fs-13">
                                    Please select a teacher to enable message sending.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.chat-item:hover {
    background-color: #f8f9fa !important;
}

.cursor-pointer {
    cursor: pointer;
}
</style>

<script>
function filterTeachers() {
    const input = document.getElementById('teacher-search-input');
    const term = (input.value || '').toLowerCase();
    const items = document.querySelectorAll('#teacher-list .chat-item');

    items.forEach(item => {
        const name = item.getAttribute('data-teacher-name') || '';
        if (!term || name.includes(term)) {
            item.parentElement.style.display = '';
        } else {
            item.parentElement.style.display = 'none';
        }
    });
}

document.addEventListener('DOMContentLoaded', function() {
    const adminFileInput = document.getElementById('admin-attachment-input');
    const adminFileNameDisplay = document.getElementById('admin-file-name-display');

    if (adminFileInput && adminFileNameDisplay) {
        adminFileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                adminFileNameDisplay.style.display = 'block';
                adminFileNameDisplay.innerHTML = '<span class="material-symbols-outlined align-middle" style="font-size: 16px;">attach_file</span> Selected: ' + file.name + ' <button type="button" class="btn-close btn-close-sm ms-2" onclick="clearAdminFile()"></button>';
            } else {
                adminFileNameDisplay.style.display = 'none';
            }
        });
    }

    window.clearAdminFile = function() {
        if (adminFileInput) {
            adminFileInput.value = '';
            if (adminFileNameDisplay) {
                adminFileNameDisplay.style.display = 'none';
            }
        }
    };

    // Auto scroll to bottom on page load
    const messagesArea = document.querySelector('.flex-grow-1.p-3');
    if (messagesArea) {
        messagesArea.scrollTop = messagesArea.scrollHeight;
    }

    // Success/Error messages
    @if(session('success'))
        alert('{{ session('success') }}');
    @endif
    @if(session('error'))
        alert('{{ session('error') }}');
    @endif
});
</script>
@endsection

