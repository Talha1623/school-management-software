@extends($layout ?? 'layouts.app')

@section('title', 'Live Chat')

@section('content')
@php
    $contactStyles = [
        'super_admin' => ['color' => '#dc3545', 'bg' => '#fde8ea', 'icon' => 'admin_panel_settings'],
        'admin' => ['color' => '#6c757d', 'bg' => '#f0f1f3', 'icon' => 'shield_person'],
        'accountant' => ['color' => '#003471', 'bg' => '#e8eef5', 'icon' => 'badge'],
        'teacher' => ['color' => '#0d6efd', 'bg' => '#e7f1ff', 'icon' => 'person'],
        'student' => ['color' => '#198754', 'bg' => '#e8f5ee', 'icon' => 'school'],
        'parent' => ['color' => '#b8860b', 'bg' => '#fff8e6', 'icon' => 'family_restroom'],
    ];
    $peerStyle = $selectedPeer ? ($contactStyles[$selectedType] ?? $contactStyles['teacher']) : $contactStyles['teacher'];
    $totalContacts = collect($contactGroups)->flatten(1)->count();
@endphp

<div class="live-chat-page">
    <div class="lc-page-header mb-4">
        <div class="d-flex align-items-center gap-3">
            <div class="lc-page-icon">
                <span class="material-symbols-outlined">forum</span>
            </div>
            <div>
                <h2 class="mb-0 fs-20 fw-semibold text-dark">Live Chat</h2>
                <p class="mb-0 text-muted fs-13">{{ $pageSubtitle ?? 'Message staff, parents, and students securely' }}</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success border-0 rounded-8 py-2 px-3 fs-13 mb-3">{{ session('success') }}</div>
    @endif
    @if(session('error'))
        <div class="alert alert-danger border-0 rounded-8 py-2 px-3 fs-13 mb-3">{{ session('error') }}</div>
    @endif

    <div class="lc-shell">
        <div class="row g-0">
            {{-- Sidebar --}}
            <div class="col-lg-4 col-xl-3 lc-sidebar">
                <div class="lc-sidebar-inner">
                    <div class="lc-sidebar-head">
                        <h5 class="mb-0 fw-semibold">Recipients</h5>
                        <span class="lc-count-badge">{{ $totalContacts }}</span>
                    </div>

                    @if($showFilters ?? false)
                    <details class="lc-filters-panel" {{ ($selectedCampus || $selectedClass || $selectedSection) ? 'open' : '' }}>
                        <summary class="lc-filters-toggle">
                            <span class="material-symbols-outlined">tune</span>
                            <span>Filters</span>
                            @if($selectedCampus || $selectedClass || $selectedSection)
                                <span class="lc-filter-dot"></span>
                            @endif
                            <span class="material-symbols-outlined lc-chevron">expand_more</span>
                        </summary>
                        <form method="GET" action="{{ route($routes['index']) }}" class="lc-filters-form">
                            @if($selectedType && $selectedId)
                                <input type="hidden" name="recipient_type" value="{{ $selectedType }}">
                                <input type="hidden" name="recipient_id" value="{{ $selectedId }}">
                            @endif
                            <label class="lc-label">Campus</label>
                            <select class="form-select form-select-sm lc-input" name="campus">
                                <option value="">All Campuses</option>
                                @foreach($campuses as $campusItem)
                                    @php $campusName = $campusItem->campus_name ?? $campusItem; @endphp
                                    <option value="{{ $campusName }}" {{ ($selectedCampus ?? '') == $campusName ? 'selected' : '' }}>{{ $campusName }}</option>
                                @endforeach
                            </select>
                            <div class="row g-2 mt-0">
                                <div class="col-6">
                                    <label class="lc-label">Class</label>
                                    <select class="form-select form-select-sm lc-input" name="class">
                                        <option value="">All</option>
                                        @foreach($classes as $className)
                                            <option value="{{ $className }}" {{ ($selectedClass ?? '') == $className ? 'selected' : '' }}>{{ $className }}</option>
                                        @endforeach
                                    </select>
                                </div>
                                <div class="col-6">
                                    <label class="lc-label">Section</label>
                                    <select class="form-select form-select-sm lc-input" name="section">
                                        <option value="">All</option>
                                        @foreach($sections as $sectionName)
                                            <option value="{{ $sectionName }}" {{ ($selectedSection ?? '') == $sectionName ? 'selected' : '' }}>{{ $sectionName }}</option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>
                            <div class="lc-filter-actions">
                                <button type="submit" class="lc-btn lc-btn-primary lc-btn-sm">Apply</button>
                                <a href="{{ route($routes['index']) }}" class="lc-btn lc-btn-ghost lc-btn-sm">Reset</a>
                            </div>
                        </form>
                    </details>
                    @endif

                    @if(count($contactGroups) > 1)
                    <div class="lc-chips" id="chat-category-tabs">
                        <button type="button" class="lc-chip active chat-filter-btn" data-filter="all">All</button>
                        @foreach(array_keys($contactGroups) as $groupLabel)
                            <button type="button" class="lc-chip chat-filter-btn" data-filter="{{ \Illuminate\Support\Str::slug($groupLabel) }}">{{ $groupLabel }}</button>
                        @endforeach
                    </div>
                    @endif

                    <div class="lc-search-wrap">
                        <span class="material-symbols-outlined lc-search-icon">search</span>
                        <input type="text" class="lc-search-input" placeholder="Search by name..." id="chat-search-input" oninput="filterChatContacts()">
                    </div>

                    <div class="lc-contact-list" id="chat-contact-list">
                        @forelse($contactGroups as $groupLabel => $contacts)
                            <div class="contact-group-section" data-group="{{ \Illuminate\Support\Str::slug($groupLabel) }}">
                                <p class="lc-group-label">{{ $groupLabel }}</p>
                                @foreach($contacts as $contact)
                                    @php
                                        $isActive = ($selectedType ?? '') === $contact['type'] && (int) ($selectedId ?? 0) === (int) $contact['id'];
                                        $style = $contactStyles[$contact['type']] ?? $contactStyles['teacher'];
                                        $linkParams = array_filter([
                                            'recipient_type' => $contact['type'],
                                            'recipient_id' => $contact['id'],
                                            'campus' => $selectedCampus ?? null,
                                            'class' => $selectedClass ?? null,
                                            'section' => $selectedSection ?? null,
                                        ]);
                                    @endphp
                                    <a href="{{ route($routes['index'], $linkParams) }}" class="lc-contact-link contact-link">
                                        <div class="lc-contact {{ $isActive ? 'is-active' : '' }}"
                                             data-contact-name="{{ strtolower($contact['name']) }}"
                                             data-contact-type="{{ $contact['type'] }}">
                                            <div class="lc-avatar" style="background: {{ $style['bg'] }}; color: {{ $style['color'] }};">
                                                <span class="material-symbols-outlined">{{ $style['icon'] }}</span>
                                            </div>
                                            <div class="lc-contact-info">
                                                <span class="lc-contact-name">{{ $contact['name'] }}</span>
                                                <span class="lc-contact-meta">{{ $contact['subtitle'] }}</span>
                                            </div>
                                            @if($isActive)
                                                <span class="lc-active-dot"></span>
                                            @endif
                                        </div>
                                    </a>
                                @endforeach
                            </div>
                        @empty
                            <div class="lc-empty-side">
                                <span class="material-symbols-outlined">person_off</span>
                                <p>No recipients found</p>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>

            {{-- Chat panel --}}
            <div class="col-lg-8 col-xl-9 lc-main">
                <div class="lc-chat-panel">
                    <div class="lc-chat-header">
                        <div class="d-flex align-items-center gap-3">
                            <div class="lc-avatar lc-avatar-lg" style="background: {{ $peerStyle['bg'] }}; color: {{ $peerStyle['color'] }};">
                                <span class="material-symbols-outlined">{{ $peerStyle['icon'] }}</span>
                            </div>
                            <div>
                                @if($selectedPeer)
                                    <h6 class="mb-0 fw-semibold fs-15">{{ $selectedPeer['name'] }}</h6>
                                    <span class="lc-chat-status">{{ $selectedPeer['subtitle'] }}</span>
                                @else
                                    <h6 class="mb-0 fw-semibold fs-15">No conversation selected</h6>
                                    <span class="lc-chat-status">Pick someone from the list to start chatting</span>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="lc-messages" id="chat-messages-area">
                        @if($selectedPeer)
                            @forelse($messages as $msg)
                                @php $incomingStyle = $contactStyles[$selectedType] ?? $contactStyles['teacher']; @endphp
                                <div class="lc-msg-row {{ $msg['is_mine'] ? 'lc-msg-mine' : 'lc-msg-theirs' }}">
                                    @if(!$msg['is_mine'])
                                        <div class="lc-avatar lc-avatar-sm" style="background: {{ $incomingStyle['bg'] }}; color: {{ $incomingStyle['color'] }};">
                                            <span class="material-symbols-outlined">{{ $incomingStyle['icon'] }}</span>
                                        </div>
                                    @endif
                                    <div class="lc-bubble {{ $msg['is_mine'] ? 'lc-bubble-mine' : 'lc-bubble-theirs' }}">
                                        @if($msg['text'])
                                            <p class="lc-bubble-text">{{ $msg['text'] }}</p>
                                        @endif
                                        @if($msg['attachment_url'])
                                            <div class="lc-attachment">
                                                @if($msg['attachment_type'] === 'image')
                                                    <a href="{{ $msg['attachment_url'] }}" target="_blank" rel="noopener" class="lc-attach-img">
                                                        <img src="{{ $msg['attachment_url'] }}" alt="Attachment">
                                                    </a>
                                                @elseif($msg['attachment_type'] === 'pdf')
                                                    <a href="{{ $msg['attachment_url'] }}" target="_blank" rel="noopener" class="lc-attach-file">
                                                        <span class="material-symbols-outlined">picture_as_pdf</span>
                                                        <span>PDF file</span>
                                                    </a>
                                                @else
                                                    <a href="{{ $msg['attachment_url'] }}" target="_blank" rel="noopener" class="lc-attach-file">
                                                        <span class="material-symbols-outlined">attach_file</span>
                                                        <span>Download file</span>
                                                    </a>
                                                @endif
                                            </div>
                                        @endif
                                        <span class="lc-bubble-time">{{ $msg['created_at'] }}</span>
                                    </div>
                                    @if($msg['is_mine'])
                                        <div class="lc-avatar lc-avatar-sm" style="background: #e8f5ee; color: #198754;">
                                            <span class="material-symbols-outlined">person</span>
                                        </div>
                                    @endif
                                </div>
                            @empty
                                <div class="lc-empty-chat">
                                    <div class="lc-empty-icon">
                                        <span class="material-symbols-outlined">chat_bubble</span>
                                    </div>
                                    <h6>No messages yet</h6>
                                    <p>Say hello — your conversation will appear here.</p>
                                </div>
                            @endforelse
                        @else
                            <div class="lc-empty-chat">
                                <div class="lc-empty-icon">
                                    <span class="material-symbols-outlined">forum</span>
                                </div>
                                <h6>Welcome to Live Chat</h6>
                                <p>Select a recipient on the left to open a conversation.</p>
                            </div>
                        @endif
                    </div>

                    <div class="lc-composer">
                        @if($selectedPeer)
                            <form action="{{ route($routes['send']) }}" method="POST" enctype="multipart/form-data" id="chat-form">
                                @csrf
                                <input type="hidden" name="recipient_type" value="{{ $selectedType }}">
                                <input type="hidden" name="recipient_id" value="{{ $selectedId }}">
                                <input type="hidden" name="campus" value="{{ $selectedCampus ?? '' }}">
                                <input type="hidden" name="class" value="{{ $selectedClass ?? '' }}">
                                <input type="hidden" name="section" value="{{ $selectedSection ?? '' }}">
                                <div id="chat-file-name-display" class="lc-file-preview" style="display: none;"></div>
                                <div class="lc-composer-bar">
                                    <label class="lc-attach-btn" for="chat-attachment-input" title="Attach file">
                                        <span class="material-symbols-outlined">attach_file</span>
                                        <input type="file" name="attachment" id="chat-attachment-input" class="d-none" accept="image/*,.pdf,.doc,.docx">
                                    </label>
                                    <input type="text" class="lc-message-input" name="text" id="chat-message-text" placeholder="Write a message..." autocomplete="off">
                                    <button class="lc-send-btn" type="submit" title="Send">
                                        <span class="material-symbols-outlined">send</span>
                                    </button>
                                </div>
                                <div class="alert alert-danger border-0 rounded-8 py-2 px-3 mb-0 mt-2 fs-12 d-none" id="chat-error" role="alert"></div>
                            </form>
                        @else
                            <div class="lc-composer-disabled">
                                <span class="material-symbols-outlined">lock</span>
                                Select a recipient to send messages
                            </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.live-chat-page {
    --lc-brand: #003471;
    --lc-brand-light: #e8eef5;
    --lc-border: #e9ecef;
    --lc-bg: #f4f6f9;
    --lc-radius: 12px;
    --lc-shadow: 0 2px 12px rgba(0, 52, 113, 0.06);
}

.lc-page-icon {
    width: 48px;
    height: 48px;
    border-radius: 12px;
    background: linear-gradient(135deg, var(--lc-brand) 0%, #1a5a9e 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
}
.lc-page-icon .material-symbols-outlined { font-size: 26px; }

.lc-shell {
    background: #fff;
    border-radius: 16px;
    box-shadow: var(--lc-shadow);
    border: 1px solid var(--lc-border);
    overflow: hidden;
    min-height: 720px;
}

.lc-sidebar {
    border-right: 1px solid var(--lc-border);
    background: #fafbfc;
}
.lc-sidebar-inner {
    display: flex;
    flex-direction: column;
    height: 720px;
    padding: 20px 16px 16px;
}

.lc-sidebar-head {
    display: flex;
    align-items: center;
    justify-content: space-between;
    margin-bottom: 16px;
    padding-bottom: 12px;
    border-bottom: 1px solid var(--lc-border);
}
.lc-count-badge {
    font-size: 11px;
    font-weight: 600;
    background: var(--lc-brand-light);
    color: var(--lc-brand);
    padding: 4px 10px;
    border-radius: 20px;
}

.lc-filters-panel {
    margin-bottom: 14px;
    border: 1px solid var(--lc-border);
    border-radius: var(--lc-radius);
    background: #fff;
    overflow: hidden;
}
.lc-filters-panel[open] .lc-chevron { transform: rotate(180deg); }
.lc-filters-toggle {
    display: flex;
    align-items: center;
    gap: 8px;
    padding: 10px 14px;
    cursor: pointer;
    font-size: 13px;
    font-weight: 600;
    color: #495057;
    list-style: none;
    user-select: none;
}
.lc-filters-toggle::-webkit-details-marker { display: none; }
.lc-filters-toggle .material-symbols-outlined { font-size: 18px; color: var(--lc-brand); }
.lc-chevron { margin-left: auto; font-size: 20px !important; color: #adb5bd !important; transition: transform 0.2s; }
.lc-filter-dot {
    width: 6px;
    height: 6px;
    border-radius: 50%;
    background: var(--lc-brand);
    margin-left: 2px;
}
.lc-filters-form { padding: 0 14px 14px; }
.lc-label {
    display: block;
    font-size: 11px;
    font-weight: 600;
    color: var(--lc-brand);
    text-transform: uppercase;
    letter-spacing: 0.03em;
    margin: 10px 0 4px;
}
.lc-input {
    border-radius: 8px !important;
    border-color: var(--lc-border) !important;
    font-size: 13px !important;
}
.lc-filter-actions {
    display: flex;
    gap: 8px;
    margin-top: 12px;
}
.lc-btn {
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 8px;
    font-size: 13px;
    font-weight: 600;
    padding: 7px 16px;
    border: none;
    text-decoration: none;
    cursor: pointer;
    transition: all 0.15s;
}
.lc-btn-sm { flex: 1; }
.lc-btn-primary { background: var(--lc-brand); color: #fff; }
.lc-btn-primary:hover { background: #002a5c; color: #fff; }
.lc-btn-ghost { background: #f1f3f5; color: #495057; }
.lc-btn-ghost:hover { background: #e9ecef; color: #212529; }

.lc-chips {
    display: flex;
    flex-wrap: wrap;
    gap: 6px;
    margin-bottom: 12px;
}
.lc-chip {
    border: 1px solid var(--lc-border);
    background: #fff;
    color: #6c757d;
    font-size: 11px;
    font-weight: 600;
    padding: 5px 12px;
    border-radius: 20px;
    cursor: pointer;
    transition: all 0.15s;
    white-space: nowrap;
}
.lc-chip:hover { border-color: var(--lc-brand); color: var(--lc-brand); }
.lc-chip.active {
    background: var(--lc-brand);
    border-color: var(--lc-brand);
    color: #fff;
}

.lc-search-wrap {
    position: relative;
    margin-bottom: 12px;
}
.lc-search-icon {
    position: absolute;
    left: 12px;
    top: 50%;
    transform: translateY(-50%);
    font-size: 18px;
    color: #adb5bd;
    pointer-events: none;
}
.lc-search-input {
    width: 100%;
    border: 1px solid var(--lc-border);
    border-radius: 10px;
    padding: 10px 12px 10px 40px;
    font-size: 13px;
    background: #fff;
    outline: none;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.lc-search-input:focus {
    border-color: var(--lc-brand);
    box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.1);
}

.lc-contact-list {
    flex: 1;
    overflow-y: auto;
    margin: 0 -4px;
    padding: 0 4px;
}
.lc-contact-list::-webkit-scrollbar { width: 5px; }
.lc-contact-list::-webkit-scrollbar-thumb { background: #dee2e6; border-radius: 4px; }

.lc-group-label {
    font-size: 10px;
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.06em;
    color: #adb5bd;
    margin: 12px 8px 6px;
    padding: 0;
}
.lc-contact-link { text-decoration: none; display: block; }
.lc-contact {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 10px 12px;
    border-radius: 10px;
    margin-bottom: 2px;
    transition: background 0.15s;
    position: relative;
}
.lc-contact:hover { background: #fff; box-shadow: 0 1px 4px rgba(0,0,0,0.04); }
.lc-contact.is-active {
    background: #fff;
    box-shadow: 0 2px 8px rgba(0, 52, 113, 0.08);
    border-left: 3px solid var(--lc-brand);
    padding-left: 9px;
}
.lc-avatar {
    width: 42px;
    height: 42px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    flex-shrink: 0;
}
.lc-avatar .material-symbols-outlined { font-size: 22px; }
.lc-avatar-lg { width: 44px; height: 44px; }
.lc-avatar-sm { width: 30px; height: 30px; }
.lc-avatar-sm .material-symbols-outlined { font-size: 16px; }

.lc-contact-info { flex: 1; min-width: 0; }
.lc-contact-name {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #212529;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
}
.lc-contact-meta {
    display: block;
    font-size: 11px;
    color: #868e96;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    margin-top: 1px;
}
.lc-active-dot {
    width: 8px;
    height: 8px;
    border-radius: 50%;
    background: #22c55e;
    flex-shrink: 0;
}

.lc-empty-side {
    text-align: center;
    padding: 32px 16px;
    color: #adb5bd;
}
.lc-empty-side .material-symbols-outlined { font-size: 40px; }
.lc-empty-side p { font-size: 13px; margin: 8px 0 0; }

.lc-main { background: #fff; }
.lc-chat-panel {
    display: flex;
    flex-direction: column;
    height: 720px;
}

.lc-chat-header {
    padding: 18px 24px;
    border-bottom: 1px solid var(--lc-border);
    background: #fff;
}
.lc-chat-status { font-size: 12px; color: #868e96; }

.lc-messages {
    flex: 1;
    overflow-y: auto;
    padding: 20px 24px;
    background: var(--lc-bg);
    background-image: radial-gradient(circle at 1px 1px, rgba(0,52,113,0.03) 1px, transparent 0);
    background-size: 24px 24px;
}
.lc-messages::-webkit-scrollbar { width: 6px; }
.lc-messages::-webkit-scrollbar-thumb { background: #ced4da; border-radius: 4px; }

.lc-msg-row {
    display: flex;
    align-items: flex-end;
    gap: 8px;
    margin-bottom: 14px;
    max-width: 78%;
}
.lc-msg-mine {
    margin-left: auto;
    flex-direction: row-reverse;
}
.lc-msg-theirs { margin-right: auto; }

.lc-bubble {
    padding: 10px 14px;
    border-radius: 16px;
    position: relative;
    word-break: break-word;
}
.lc-bubble-theirs {
    background: #fff;
    border: 1px solid var(--lc-border);
    border-bottom-left-radius: 4px;
    box-shadow: 0 1px 2px rgba(0,0,0,0.04);
}
.lc-bubble-mine {
    background: linear-gradient(135deg, var(--lc-brand) 0%, #1a5a9e 100%);
    color: #fff;
    border-bottom-right-radius: 4px;
    box-shadow: 0 2px 8px rgba(0, 52, 113, 0.2);
}
.lc-bubble-text {
    margin: 0;
    font-size: 14px;
    line-height: 1.45;
}
.lc-bubble-mine .lc-bubble-text { color: #fff; }
.lc-bubble-time {
    display: block;
    font-size: 10px;
    margin-top: 6px;
    opacity: 0.65;
}
.lc-bubble-mine .lc-attach-file { color: #fff !important; }
.lc-attachment { margin-top: 6px; }
.lc-attach-img img {
    max-width: 180px;
    max-height: 160px;
    border-radius: 10px;
    display: block;
}
.lc-attach-file {
    display: inline-flex;
    align-items: center;
    gap: 6px;
    font-size: 13px;
    color: var(--lc-brand);
    text-decoration: none;
    padding: 6px 10px;
    background: var(--lc-brand-light);
    border-radius: 8px;
}
.lc-attach-file:hover { opacity: 0.85; }

.lc-empty-chat {
    height: 100%;
    min-height: 320px;
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    text-align: center;
    padding: 24px;
}
.lc-empty-icon {
    width: 72px;
    height: 72px;
    border-radius: 50%;
    background: var(--lc-brand-light);
    color: var(--lc-brand);
    display: flex;
    align-items: center;
    justify-content: center;
    margin-bottom: 16px;
}
.lc-empty-icon .material-symbols-outlined { font-size: 36px; }
.lc-empty-chat h6 {
    font-size: 16px;
    font-weight: 600;
    color: #343a40;
    margin-bottom: 6px;
}
.lc-empty-chat p {
    font-size: 13px;
    color: #868e96;
    margin: 0;
    max-width: 280px;
}

.lc-composer {
    padding: 16px 24px 20px;
    border-top: 1px solid var(--lc-border);
    background: #fff;
}
.lc-composer-disabled {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px;
    background: #f8f9fa;
    border-radius: 12px;
    color: #adb5bd;
    font-size: 13px;
}
.lc-composer-disabled .material-symbols-outlined { font-size: 18px; }

.lc-composer-bar {
    display: flex;
    align-items: center;
    gap: 10px;
    background: var(--lc-bg);
    border: 1px solid var(--lc-border);
    border-radius: 14px;
    padding: 6px 8px 6px 6px;
    transition: border-color 0.15s, box-shadow 0.15s;
}
.lc-composer-bar:focus-within {
    border-color: var(--lc-brand);
    box-shadow: 0 0 0 3px rgba(0, 52, 113, 0.08);
    background: #fff;
}
.lc-attach-btn {
    width: 38px;
    height: 38px;
    border-radius: 10px;
    display: flex;
    align-items: center;
    justify-content: center;
    color: #6c757d;
    cursor: pointer;
    margin: 0;
    transition: background 0.15s, color 0.15s;
}
.lc-attach-btn:hover { background: var(--lc-brand-light); color: var(--lc-brand); }
.lc-attach-btn .material-symbols-outlined { font-size: 22px; }

.lc-message-input {
    flex: 1;
    border: none;
    background: transparent;
    font-size: 14px;
    padding: 8px 4px;
    outline: none;
}
.lc-send-btn {
    width: 42px;
    height: 42px;
    border-radius: 12px;
    border: none;
    background: linear-gradient(135deg, var(--lc-brand) 0%, #1a5a9e 100%);
    color: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    transition: transform 0.15s, box-shadow 0.15s;
    flex-shrink: 0;
}
.lc-send-btn:hover {
    transform: scale(1.04);
    box-shadow: 0 4px 12px rgba(0, 52, 113, 0.25);
}
.lc-send-btn:disabled { opacity: 0.6; transform: none; }
.lc-send-btn .material-symbols-outlined { font-size: 22px; }

.lc-file-preview {
    font-size: 12px;
    color: #495057;
    padding: 6px 10px;
    background: var(--lc-brand-light);
    border-radius: 8px;
    margin-bottom: 8px;
    display: flex;
    align-items: center;
    gap: 6px;
}

.contact-group-section.hidden-group { display: none; }

@media (max-width: 991.98px) {
    .lc-shell { min-height: auto; }
    .lc-sidebar-inner, .lc-chat-panel { height: auto; min-height: 480px; }
    .lc-sidebar { border-right: none; border-bottom: 1px solid var(--lc-border); }
    .lc-contact-list { max-height: 280px; }
}
</style>

<script>
function filterChatContacts() {
    const term = (document.getElementById('chat-search-input')?.value || '').toLowerCase();
    document.querySelectorAll('#chat-contact-list .lc-contact').forEach(item => {
        const name = item.getAttribute('data-contact-name') || '';
        const link = item.closest('.contact-link');
        if (link) link.style.display = !term || name.includes(term) ? '' : 'none';
    });
}

document.querySelectorAll('.chat-filter-btn').forEach(btn => {
    btn.addEventListener('click', function () {
        const filter = this.getAttribute('data-filter');
        document.querySelectorAll('.chat-filter-btn').forEach(b => b.classList.remove('active'));
        this.classList.add('active');
        document.querySelectorAll('.contact-group-section').forEach(section => {
            section.classList.toggle('hidden-group', filter !== 'all' && section.getAttribute('data-group') !== filter);
        });
    });
});

document.addEventListener('DOMContentLoaded', function () {
    const fileInput = document.getElementById('chat-attachment-input');
    const fileNameDisplay = document.getElementById('chat-file-name-display');
    const chatForm = document.getElementById('chat-form');
    const messageText = document.getElementById('chat-message-text');
    const messagesArea = document.getElementById('chat-messages-area');
    const errorBox = document.getElementById('chat-error');
    const useAjax = @json($ajaxSend ?? false);

    if (fileInput && fileNameDisplay) {
        fileInput.addEventListener('change', function (e) {
            const file = e.target.files[0];
            if (file) {
                fileNameDisplay.style.display = 'flex';
                fileNameDisplay.innerHTML = '<span class="material-symbols-outlined" style="font-size:16px">description</span><span>' + file.name + '</span><button type="button" class="btn-close btn-close-sm ms-auto" onclick="clearChatFile()"></button>';
            } else {
                fileNameDisplay.style.display = 'none';
            }
        });
    }

    window.clearChatFile = function () {
        if (fileInput) fileInput.value = '';
        if (fileNameDisplay) fileNameDisplay.style.display = 'none';
    };

    if (messagesArea) messagesArea.scrollTop = messagesArea.scrollHeight;

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
    function renderAttachmentHtml(url, type) {
        if (!url) return '';
        if (type === 'image') {
            return '<div class="lc-attachment"><a href="' + url + '" target="_blank" class="lc-attach-img"><img src="' + url + '" alt=""></a></div>';
        }
        const icon = type === 'pdf' ? 'picture_as_pdf' : 'attach_file';
        const label = type === 'pdf' ? 'PDF file' : 'Download file';
        return '<div class="lc-attachment"><a href="' + url + '" target="_blank" class="lc-attach-file" style="color:#fff!important"><span class="material-symbols-outlined">' + icon + '</span><span>' + label + '</span></a></div>';
    }
    function appendOutgoingMessage(payload) {
        if (!messagesArea) return;
        const empty = messagesArea.querySelector('.lc-empty-chat');
        if (empty) empty.remove();

        const row = document.createElement('div');
        row.className = 'lc-msg-row lc-msg-mine';
        row.innerHTML = `
            <div class="lc-bubble lc-bubble-mine">
                ${payload.text ? '<p class="lc-bubble-text">' + escapeHtml(payload.text) + '</p>' : ''}
                ${renderAttachmentHtml(payload.attachment_url, payload.attachment_type)}
                <span class="lc-bubble-time">${escapeHtml(payload.created_at || '')}</span>
            </div>
            <div class="lc-avatar lc-avatar-sm" style="background:#e8f5ee;color:#198754">
                <span class="material-symbols-outlined">person</span>
            </div>
        `;
        messagesArea.appendChild(row);
        messagesArea.scrollTop = messagesArea.scrollHeight;
    }

    if (chatForm && useAjax) {
        chatForm.addEventListener('submit', async function (e) {
            e.preventDefault();
            clearError();
            const formData = new FormData(chatForm);
            const submitBtn = chatForm.querySelector('.lc-send-btn');
            if (submitBtn) submitBtn.disabled = true;
            try {
                const res = await fetch(chatForm.action, {
                    method: 'POST',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                    body: formData,
                });
                const json = await res.json().catch(() => null);
                if (!res.ok || !json || json.success !== true) {
                    showError(json?.message || 'Failed to send message.');
                    return;
                }
                appendOutgoingMessage(json.data || {});
                if (messageText) messageText.value = '';
                if (fileInput) fileInput.value = '';
                if (fileNameDisplay) fileNameDisplay.style.display = 'none';
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
