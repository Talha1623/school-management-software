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
                        <h5 class="mb-0 fs-16 fw-semibold">Conversations</h5>
                        <button class="btn btn-sm btn-primary">
                            <span class="material-symbols-outlined" style="font-size: 18px;">add</span>
                        </button>
                    </div>
                    
                    <!-- Search Box -->
                    <div class="mb-3">
                        <div class="input-group input-group-sm">
                            <span class="input-group-text bg-light border-end-0">
                                <span class="material-symbols-outlined" style="font-size: 16px;">search</span>
                            </span>
                            <input type="text" class="form-control border-start-0" placeholder="Search conversations...">
                        </div>
                    </div>

                    <!-- Chat List -->
                    <div class="chat-list" style="max-height: 600px; overflow-y: auto;">
                        <div class="chat-item p-3 border-bottom cursor-pointer" style="transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f8f9fa'" onmouseout="this.style.backgroundColor='white'">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 me-3">
                                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white" style="width: 45px; height: 45px;">
                                        <span class="material-symbols-outlined">person</span>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <h6 class="mb-0 fs-14 fw-semibold">Support Team</h6>
                                        <small class="text-muted">10:30 AM</small>
                                    </div>
                                    <p class="mb-0 text-muted fs-12" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">Hello! How can I help you today?</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="chat-item p-3 border-bottom cursor-pointer" style="transition: background-color 0.2s;" onmouseover="this.style.backgroundColor='#f8f9fa'" onmouseout="this.style.backgroundColor='white'">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0 me-3">
                                    <div class="rounded-circle bg-success d-flex align-items-center justify-content-center text-white" style="width: 45px; height: 45px;">
                                        <span class="material-symbols-outlined">person</span>
                                    </div>
                                </div>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-start mb-1">
                                        <h6 class="mb-0 fs-14 fw-semibold">Admin</h6>
                                        <small class="text-muted">Yesterday</small>
                                    </div>
                                    <p class="mb-0 text-muted fs-12" style="overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">Thank you for contacting us.</p>
                                </div>
                            </div>
                        </div>
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
                                    <h6 class="mb-0 fs-15 fw-semibold">Support Team</h6>
                                    <small class="text-muted fs-12">Online</small>
                                </div>
                            </div>
                            <div class="d-flex gap-2">
                                <button class="btn btn-sm btn-outline-secondary">
                                    <span class="material-symbols-outlined" style="font-size: 18px;">more_vert</span>
                                </button>
                            </div>
                        </div>

                        <!-- Messages Area -->
                        <div class="flex-grow-1 p-3" style="overflow-y: auto; background-color: #f8f9fa;">
                            <!-- Incoming Message -->
                            <div class="mb-3">
                                <div class="d-flex align-items-start">
                                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white me-2" style="width: 32px; height: 32px; flex-shrink: 0;">
                                        <span class="material-symbols-outlined" style="font-size: 16px;">person</span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="bg-white rounded-3 p-3 shadow-sm" style="max-width: 70%;">
                                            <p class="mb-0 fs-14">Hello! How can I assist you today?</p>
                                            <small class="text-muted fs-11 d-block mt-1">10:30 AM</small>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Outgoing Message -->
                            <div class="mb-3 d-flex justify-content-end">
                                <div class="flex-grow-1 d-flex justify-content-end">
                                    <div class="bg-primary text-white rounded-3 p-3 shadow-sm" style="max-width: 70%;">
                                        <p class="mb-0 fs-14">I need help with student admission process.</p>
                                        <small class="text-white-50 fs-11 d-block mt-1">10:32 AM</small>
                                    </div>
                                </div>
                                <div class="rounded-circle bg-success d-flex align-items-center justify-content-center text-white ms-2" style="width: 32px; height: 32px; flex-shrink: 0;">
                                    <span class="material-symbols-outlined" style="font-size: 16px;">person</span>
                                </div>
                            </div>

                            <!-- Incoming Message -->
                            <div class="mb-3">
                                <div class="d-flex align-items-start">
                                    <div class="rounded-circle bg-primary d-flex align-items-center justify-content-center text-white me-2" style="width: 32px; height: 32px; flex-shrink: 0;">
                                        <span class="material-symbols-outlined" style="font-size: 16px;">person</span>
                                    </div>
                                    <div class="flex-grow-1">
                                        <div class="bg-white rounded-3 p-3 shadow-sm" style="max-width: 70%;">
                                            <p class="mb-0 fs-14">Sure! I can help you with that. Please go to Admission Management > Admit Student.</p>
                                            <small class="text-muted fs-11 d-block mt-1">10:33 AM</small>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- Message Input -->
                        <div class="p-3 border-top">
                            <div class="d-flex align-items-center gap-2">
                                <button class="btn btn-sm btn-outline-secondary">
                                    <span class="material-symbols-outlined" style="font-size: 20px;">attach_file</span>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary">
                                    <span class="material-symbols-outlined" style="font-size: 20px;">emoji_emotions</span>
                                </button>
                                <input type="text" class="form-control" placeholder="Type a message..." id="message-input">
                                <button class="btn btn-sm btn-primary">
                                    <span class="material-symbols-outlined" style="font-size: 20px;">send</span>
                                </button>
                            </div>
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
@endsection

