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
                        <h5 class="mb-0 fs-16 fw-semibold">Recipients</h5>
                    </div>

                    <form method="GET" action="{{ route('live-chat') }}" class="mb-3">
                        <div class="row g-2">
                            <div class="col-12">
                                <select class="form-select form-select-sm" name="campus">
                                    <option value="">All Campuses</option>
                                    @foreach($campuses as $campusItem)
                                        @php $campusName = $campusItem->campus_name ?? ''; @endphp
                                        <option value="{{ $campusName }}" {{ ($selectedCampus ?? '') === $campusName ? 'selected' : '' }}>
                                            {{ $campusName }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <select class="form-select form-select-sm" name="class">
                                    <option value="">All Classes</option>
                                    @foreach($classes as $className)
                                        <option value="{{ $className }}" {{ ($selectedClass ?? '') === $className ? 'selected' : '' }}>
                                            {{ $className }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div class="col-6">
                                <select class="form-select form-select-sm" name="section">
                                    <option value="">All Sections</option>
                                    @foreach($sections as $sectionName)
                                        <option value="{{ $sectionName }}" {{ ($selectedSection ?? '') === $sectionName ? 'selected' : '' }}>
                                            {{ $sectionName }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            @if(isset($selectedType) && !empty($selectedType) && isset($selectedRecipient) && $selectedRecipient)
                                <input type="hidden" name="recipient_type" value="{{ $selectedType }}">
                                <input type="hidden" name="recipient_id" value="{{ $selectedRecipient->id }}">
                            @endif
                            <div class="col-12 d-flex gap-2">
                                <button type="submit" class="btn btn-sm btn-primary w-100">Apply</button>
                                <a href="{{ route('live-chat') }}" class="btn btn-sm btn-outline-secondary w-100">Reset</a>
                            </div>
                        </div>
                    </form>
                    
                    <!-- Search Box -->
                    <div class="mb-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0">
                                <span class="material-symbols-outlined" style="font-size: 16px;">search</span>
                            </span>
                            <input type="text" class="form-control border-start-0" placeholder="Search recipients..." id="teacher-search-input" oninput="filterTeachers()">
                        </div>
                    </div>

                    <!-- Chat List -->
                    <div class="chat-list" style="max-height: 600px; overflow-y: auto;" id="teacher-list">
                        @foreach($teachers as $t)
                            @php
                                $isActive = isset($selectedRecipient) && $selectedRecipient && ($selectedType ?? '') === 'teacher' && $selectedRecipient->id === $t->id;
                            @endphp
                            <a href="{{ route('live-chat', ['recipient_type' => 'teacher', 'recipient_id' => $t->id, 'campus' => $selectedCampus ?? null, 'class' => $selectedClass ?? null, 'section' => $selectedSection ?? null]) }}"
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
                        @endforeach
                        @foreach($accountants as $a)
                            @php
                                $isActive = isset($selectedRecipient) && $selectedRecipient && ($selectedType ?? '') === 'accountant' && $selectedRecipient->id === $a->id;
                            @endphp
                            <a href="{{ route('live-chat', ['recipient_type' => 'accountant', 'recipient_id' => $a->id, 'campus' => $selectedCampus ?? null, 'class' => $selectedClass ?? null, 'section' => $selectedSection ?? null]) }}"
                               class="text-decoration-none text-dark">
                                <div class="chat-item p-3 border-bottom cursor-pointer {{ $isActive ? 'bg-light' : '' }}"
                                     data-teacher-name="{{ strtolower($a->name ?? '') }}"
                                     style="transition: background-color 0.2s;">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="rounded-circle bg-dark d-flex align-items-center justify-content-center text-white" style="width: 45px; height: 45px;">
                                                <span class="material-symbols-outlined">badge</span>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <h6 class="mb-0 fs-14 fw-semibold">{{ $a->name ?? 'Accountant' }}</h6>
                                            </div>
                                            <p class="mb-0 text-muted fs-12" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                Accountant @if($a->campus) - {{ $a->campus }} @endif
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                        @foreach($students as $s)
                            @php
                                $isActive = isset($selectedRecipient) && $selectedRecipient && ($selectedType ?? '') === 'student' && $selectedRecipient->id === $s->id;
                            @endphp
                            <a href="{{ route('live-chat', ['recipient_type' => 'student', 'recipient_id' => $s->id, 'campus' => $selectedCampus ?? null, 'class' => $selectedClass ?? null, 'section' => $selectedSection ?? null]) }}"
                               class="text-decoration-none text-dark">
                                <div class="chat-item p-3 border-bottom cursor-pointer {{ $isActive ? 'bg-light' : '' }}"
                                     data-teacher-name="{{ strtolower($s->student_name ?? '') }}"
                                     style="transition: background-color 0.2s;">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="rounded-circle bg-success d-flex align-items-center justify-content-center text-white" style="width: 45px; height: 45px;">
                                                <span class="material-symbols-outlined">school</span>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <h6 class="mb-0 fs-14 fw-semibold">{{ $s->student_name ?? 'Student' }}</h6>
                                            </div>
                                            <p class="mb-0 text-muted fs-12" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                Student @if($s->student_code) - {{ $s->student_code }} @endif
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                        @foreach($parents as $p)
                            @php
                                $isActive = isset($selectedRecipient) && $selectedRecipient && ($selectedType ?? '') === 'parent' && $selectedRecipient->id === $p->id;
                            @endphp
                            <a href="{{ route('live-chat', ['recipient_type' => 'parent', 'recipient_id' => $p->id, 'campus' => $selectedCampus ?? null, 'class' => $selectedClass ?? null, 'section' => $selectedSection ?? null]) }}"
                               class="text-decoration-none text-dark">
                                <div class="chat-item p-3 border-bottom cursor-pointer {{ $isActive ? 'bg-light' : '' }}"
                                     data-teacher-name="{{ strtolower($p->name ?? '') }}"
                                     style="transition: background-color 0.2s;">
                                    <div class="d-flex align-items-center">
                                        <div class="flex-shrink-0 me-3">
                                            <div class="rounded-circle bg-warning d-flex align-items-center justify-content-center text-white" style="width: 45px; height: 45px;">
                                                <span class="material-symbols-outlined">family_restroom</span>
                                            </div>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="d-flex justify-content-between align-items-start mb-1">
                                                <h6 class="mb-0 fs-14 fw-semibold">{{ $p->name ?? 'Parent' }}</h6>
                                            </div>
                                            <p class="mb-0 text-muted fs-12" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                                                Parent @if($p->id_card_number) - {{ $p->id_card_number }} @endif
                                            </p>
                                        </div>
                                    </div>
                                </div>
                            </a>
                        @endforeach
                        @if($teachers->isEmpty() && $accountants->isEmpty() && $students->isEmpty() && $parents->isEmpty())
                            <div class="p-3 text-muted">No recipients found.</div>
                        @endif
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
                                    @if(isset($selectedRecipient) && $selectedRecipient)
                                        <h6 class="mb-0 fs-15 fw-semibold">
                                            {{ ($selectedType ?? '') === 'student' ? ($selectedRecipient->student_name ?? 'Student') : ($selectedRecipient->name ?? 'Recipient') }}
                                        </h6>
                                        <small class="text-muted fs-12">
                                            @if(($selectedType ?? '') === 'teacher')
                                                {{ $selectedRecipient->designation ?? 'Teacher' }}
                                            @elseif(($selectedType ?? '') === 'accountant')
                                                Accountant
                                            @elseif(($selectedType ?? '') === 'student')
                                                Student
                                            @else
                                                Parent
                                            @endif
                                        </small>
                                    @else
                                        <h6 class="mb-0 fs-15 fw-semibold">Select a Recipient</h6>
                                        <small class="text-muted fs-12">From the left list to start chat</small>
                                    @endif
                                </div>
                            </div>
                        </div>

                        <!-- Messages Area -->
                        <div class="flex-grow-1 p-3" style="overflow-y: auto; background-color: #f8f9fa;" id="chat-messages-area">
                            @if(isset($selectedRecipient) && $selectedRecipient)
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
                            @if(isset($selectedRecipient) && $selectedRecipient)
                                <form action="{{ route('live-chat.send') }}" method="POST" enctype="multipart/form-data" id="admin-chat-form">
                                    @csrf
                                    <input type="hidden" name="recipient_type" value="{{ $selectedType }}">
                                    <input type="hidden" name="recipient_id" value="{{ $selectedRecipient->id }}">
                                    <input type="hidden" name="campus" value="{{ $selectedCampus ?? '' }}">
                                    <input type="hidden" name="class" value="{{ $selectedClass ?? '' }}">
                                    <input type="hidden" name="section" value="{{ $selectedSection ?? '' }}">
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
                                    <div class="mt-2">
                                        <div class="alert alert-danger py-2 px-3 mb-0 d-none" id="admin-chat-error" role="alert"></div>
                                    </div>
                                </form>
                            @else
                                <div class="text-muted fs-13">
                                    Please select a recipient to enable message sending.
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
    const adminChatForm = document.getElementById('admin-chat-form');
    const adminMessageText = document.getElementById('admin-message-text');
    const messagesArea = document.getElementById('chat-messages-area');
    const errorBox = document.getElementById('admin-chat-error');

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
    if (messagesArea) {
        messagesArea.scrollTop = messagesArea.scrollHeight;
    }

    function showError(msg) {
        if (!errorBox) return;
        errorBox.textContent = msg || 'Something went wrong.';
        errorBox.classList.remove('d-none');
    }

    function clearError() {
        if (!errorBox) return;
        errorBox.textContent = '';
        errorBox.classList.add('d-none');
    }

    function escapeHtml(unsafe) {
        const div = document.createElement('div');
        div.textContent = unsafe ?? '';
        return div.innerHTML;
    }

    function renderAttachmentHtml(attachmentUrl, attachmentType, isAdmin) {
        if (!attachmentUrl) return '';
        if (attachmentType === 'image') {
            return `
                <div class="mt-2">
                    <a href="${attachmentUrl}" download class="d-inline-block text-decoration-none">
                        <img src="${attachmentUrl}" alt="Attachment" class="img-thumbnail" style="max-width: 150px; max-height: 150px; width: auto; height: auto; border-radius: 8px; cursor: pointer; object-fit: contain; display: block;">
                        <small class="text-muted d-block mt-1" style="font-size: 10px;">Click to download</small>
                    </a>
                </div>
            `;
        }
        const cls = isAdmin ? 'text-white' : 'text-primary';
        const icon = attachmentType === 'pdf' ? 'picture_as_pdf' : 'attach_file';
        const label = attachmentType === 'pdf' ? 'PDF Attachment - Click to download' : 'Document Attachment - Click to download';
        return `
            <div class="mt-2">
                <a href="${attachmentUrl}" download class="text-decoration-none d-inline-flex align-items-center gap-1 ${cls}" style="cursor: pointer;">
                    <span class="material-symbols-outlined" style="font-size: 20px;">${icon}</span>
                    <span>${label}</span>
                </a>
            </div>
        `;
    }

    function appendAdminMessage(payload) {
        if (!messagesArea) return;

        const textHtml = payload.text ? `<p class="mb-1 fs-14">${escapeHtml(payload.text)}</p>` : '';
        const attachmentHtml = renderAttachmentHtml(payload.attachment_url, payload.attachment_type, true);
        const createdAt = escapeHtml(payload.created_at || '');

        const wrapper = document.createElement('div');
        wrapper.className = 'mb-3 d-flex justify-content-end';
        wrapper.innerHTML = `
            <div class="d-flex align-items-start justify-content-end">
                <div class="flex-grow-1 d-flex justify-content-end">
                    <div class="bg-primary text-white rounded-3 p-3 shadow-sm" style="max-width: 70%;">
                        ${textHtml}
                        ${attachmentHtml}
                        <small class="text-white-50 fs-11 d-block mt-1">${createdAt}</small>
                    </div>
                </div>
                <div class="rounded-circle bg-success d-flex align-items-center justify-content-center text-white ms-2" style="width: 32px; height: 32px; flex-shrink: 0;">
                    <span class="material-symbols-outlined" style="font-size: 16px;">person</span>
                </div>
            </div>
        `;

        // If there was "no messages" placeholder, remove it
        const emptyPlaceholder = messagesArea.querySelector('.h-100.d-flex.align-items-center.justify-content-center');
        if (emptyPlaceholder) emptyPlaceholder.remove();

        messagesArea.appendChild(wrapper);
        messagesArea.scrollTop = messagesArea.scrollHeight;
    }

    if (adminChatForm) {
        adminChatForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            clearError();

            const formData = new FormData(adminChatForm);

            const submitBtn = adminChatForm.querySelector('button[type="submit"]');
            if (submitBtn) submitBtn.disabled = true;

            try {
                const res = await fetch(adminChatForm.action, {
                    method: 'POST',
                    headers: {
                        'Accept': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                    },
                    body: formData,
                });

                const json = await res.json().catch(() => null);
                if (!res.ok || !json || json.success !== true) {
                    const msg = json?.message || 'Failed to send message.';
                    showError(msg);
                    return;
                }

                appendAdminMessage(json.data || {});

                if (adminMessageText) adminMessageText.value = '';
                if (adminFileInput) adminFileInput.value = '';
                if (adminFileNameDisplay) adminFileNameDisplay.style.display = 'none';
            } catch (err) {
                showError('Network error. Please try again.');
            } finally {
                if (submitBtn) submitBtn.disabled = false;
            }
        });
    }
});
</script>
@endsection

