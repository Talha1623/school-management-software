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

        <div class="card bg-white border border-white rounded-10 p-4">
            <div class="row">
                <!-- Sidebar: Teacher Info -->
                <div class="col-md-3 border-end">
                    <div class="d-flex flex-column align-items-center text-center mb-3">
                        <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white mb-2" style="width: 64px; height: 64px;">
                            <span class="material-symbols-outlined" style="font-size: 32px;">person</span>
                        </div>
                        <h5 class="mb-0 fs-16 fw-semibold">{{ $teacher->name }}</h5>
                        <small class="text-muted fs-12">{{ $teacher->designation ?? 'Teacher' }}</small>
                        @if($teacher->campus)
                            <small class="text-muted fs-12 mt-1">{{ $teacher->campus }}</small>
                        @endif
                    </div>

                    <div class="mt-4">
                        <p class="text-muted fs-12 mb-1">Chatting with</p>
                        @if($admin)
                            <p class="mb-0 fw-semibold">{{ $admin->name }}</p>
                            <small class="text-muted fs-12">{{ $admin->email }}</small>
                        @else
                            <p class="text-danger fs-12 mb-0">No admin found. Please contact system administrator.</p>
                        @endif
                    </div>
                </div>

                <!-- Chat Area -->
                <div class="col-md-9">
                    <div class="d-flex flex-column" style="height: 650px;">
                        <!-- Chat Header -->
                        <div class="d-flex align-items-center justify-content-between p-3 border-bottom">
                            <div class="d-flex align-items-center">
                                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white me-3" style="width: 40px; height: 40px;">
                                    <span class="material-symbols-outlined">person</span>
                                </div>
                                <div>
                                    @if($admin)
                                        <h6 class="mb-0 fs-15 fw-semibold">{{ $admin->name }}</h6>
                                        <small class="text-muted fs-12">Admin</small>
                                    @else
                                        <h6 class="mb-0 fs-15 fw-semibold">No Admin</h6>
                                        <small class="text-muted fs-12">Please contact support</small>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Messages Area -->
                        <div class="flex-grow-1 p-3" style="overflow-y: auto; background-color: #f8f9fa;">
                            @if(!$admin)
                                <div class="h-100 d-flex align-items-center justify-content-center">
                                    <p class="text-muted mb-0">No admin user configured for chat.</p>
                                </div>
                            @else
                                @forelse($messages as $msg)
                                    @php
                                        $isTeacher = $msg['from_type'] === 'teacher';
                                    @endphp
                                    <div class="mb-3 {{ $isTeacher ? 'd-flex justify-content-end' : '' }}">
                                        <div class="d-flex align-items-start {{ $isTeacher ? 'justify-content-end' : '' }}">
                                            @if(!$isTeacher)
                                                <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white me-2" style="width: 32px; height: 32px; flex-shrink: 0;">
                                                    <span class="material-symbols-outlined" style="font-size: 16px;">person</span>
                                                </div>
                                            @endif
                                            <div class="flex-grow-1 d-flex {{ $isTeacher ? 'justify-content-end' : '' }}">
                                                <div class="{{ $isTeacher ? 'bg-primary text-white' : 'bg-white' }} rounded-3 p-3 shadow-sm" style="max-width: 70%;">
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
                                                                <a href="{{ $msg['attachment_url'] }}" download class="text-decoration-none d-inline-flex align-items-center gap-1 {{ $isTeacher ? 'text-white' : 'text-primary' }}" style="cursor: pointer;">
                                                                    <span class="material-symbols-outlined" style="font-size: 20px;">picture_as_pdf</span>
                                                                    <span>PDF Attachment - Click to download</span>
                                                                </a>
                                                            @else
                                                                <a href="{{ $msg['attachment_url'] }}" download class="text-decoration-none d-inline-flex align-items-center gap-1 {{ $isTeacher ? 'text-white' : 'text-primary' }}" style="cursor: pointer;">
                                                                    <span class="material-symbols-outlined" style="font-size: 20px;">attach_file</span>
                                                                    <span>Document Attachment - Click to download</span>
                                                                </a>
                                                            @endif
                                                        </div>
                                                    @endif
                                                    <small class="{{ $isTeacher ? 'text-white-50' : 'text-muted' }} fs-11 d-block mt-1">{{ $msg['created_at'] }}</small>
                                                </div>
                                            </div>
                                            @if($isTeacher)
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
                            @endif
                        </div>

                        <!-- Message Input -->
                        <div class="p-3 border-top">
                            @if($admin)
                                <form action="{{ route('staff.chat.send') }}" method="POST" enctype="multipart/form-data" id="chat-form">
                                    @csrf
                                    <div id="file-name-display" class="mb-2 text-muted fs-12" style="display: none;"></div>
                                    <div class="d-flex align-items-center gap-2">
                                        <label class="btn btn-sm btn-outline-secondary mb-0" for="attachment-input" style="cursor: pointer;">
                                            <span class="material-symbols-outlined" style="font-size: 20px;">attach_file</span>
                                            <input type="file" name="attachment" id="attachment-input" class="d-none" accept="image/*,.pdf,.doc,.docx">
                                        </label>
                                        <input type="text" class="form-control" name="text" id="message-text" placeholder="Type a message...">
                                        <button class="btn btn-sm btn-primary" type="submit">
                                            <span class="material-symbols-outlined" style="font-size: 20px;">send</span>
                                        </button>
                                    </div>
                                </form>
                            @else
                                <div class="text-muted fs-13">
                                    Chat is not available because no admin user is configured.
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const fileInput = document.getElementById('attachment-input');
    const fileNameDisplay = document.getElementById('file-name-display');
    const chatForm = document.getElementById('chat-form');
    const messageText = document.getElementById('message-text');

    if (fileInput && fileNameDisplay) {
        fileInput.addEventListener('change', function(e) {
            const file = e.target.files[0];
            if (file) {
                fileNameDisplay.style.display = 'block';
                fileNameDisplay.innerHTML = '<span class="material-symbols-outlined align-middle" style="font-size: 16px;">attach_file</span> Selected: ' + file.name + ' <button type="button" class="btn-close btn-close-sm ms-2" onclick="clearFile()"></button>';
            } else {
                fileNameDisplay.style.display = 'none';
            }
        });
    }

    window.clearFile = function() {
        if (fileInput) {
            fileInput.value = '';
            if (fileNameDisplay) {
                fileNameDisplay.style.display = 'none';
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


