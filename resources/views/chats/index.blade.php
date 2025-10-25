@extends('layouts.app')

@section('title', 'Chats')
@section('page-title', 'WhatsApp Chats')

@section('content')
<div class="container-fluid px-0" style="height: calc(100vh - 120px);">
    <div class="row g-0 h-100">
        <!-- Left Sidebar - Chat List -->
        <div class="col-md-4 col-lg-3 border-end bg-white">
            <!-- Header -->
            <div class="p-3 border-bottom bg-light">
                <div class="d-flex justify-content-between align-items-center mb-3">
                    <h5 class="mb-0">Chats</h5>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="?filter=all">All Chats</a></li>
                            <li><a class="dropdown-item" href="?filter=unread">Unread</a></li>
                            <li><a class="dropdown-item" href="?filter=archived">Archived</a></li>
                        </ul>
                    </div>
                </div>

                <!-- Account Selector -->
                @if($accounts->count() > 1)
                <select class="form-select form-select-sm mb-2" onchange="window.location.href='?account_id='+this.value">
                    @foreach($accounts as $acc)
                        <option value="{{ $acc->id }}" {{ $selectedAccount->id == $acc->id ? 'selected' : '' }}>
                            {{ $acc->name }} ({{ $acc->phone_number }})
                        </option>
                    @endforeach
                </select>
                @endif

                <!-- Search -->
                <form action="{{ route('chats.index') }}" method="GET">
                    <input type="hidden" name="account_id" value="{{ $selectedAccount->id }}">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0">
                            <i class="bi bi-search"></i>
                        </span>
                        <input type="text" name="search" class="form-control border-start-0" 
                               placeholder="Search chats..." value="{{ request('search') }}">
                    </div>
                </form>
            </div>

            <!-- Chat List -->
            <div class="overflow-auto" style="height: calc(100% - 140px);">
                @forelse($chats as $chat)
                <a href="{{ route('chats.conversation', ['account' => $selectedAccount->id, 'chat' => $chat->id]) }}" 
                   class="d-block text-decoration-none text-dark border-bottom chat-item {{ request()->route('chat') == $chat->id ? 'active' : '' }}">
                    <div class="p-3 d-flex align-items-start">
                        <!-- Avatar -->
                        <div class="flex-shrink-0 me-3">
                            <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center" 
                                 style="width: 50px; height: 50px;">
                                <strong>{{ $chat->initials }}</strong>
                            </div>
                        </div>
                        
                        <!-- Chat Info -->
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="d-flex justify-content-between align-items-start mb-1">
                                <h6 class="mb-0 text-truncate">{{ $chat->display_name }}</h6>
                                <small class="text-muted">
                                    {{ $chat->last_message_at ? $chat->last_message_at->format('H:i') : '' }}
                                </small>
                            </div>
                            <div class="d-flex justify-content-between align-items-center">
                                <small class="text-muted text-truncate" style="max-width: 200px;">
                                    {{ $chat->last_message ?? 'No messages yet' }}
                                </small>
                                @if($chat->unread_count > 0)
                                <span class="badge bg-success rounded-pill">{{ $chat->unread_count }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </a>
                @empty
                <div class="text-center py-5">
                    <i class="bi bi-chat-dots text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3">No chats yet</p>
                    <button class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#newChatModal">
                        <i class="bi bi-plus-circle me-2"></i>Start New Chat
                    </button>
                </div>
                @endforelse
            </div>

            <!-- New Chat Button -->
            @if($chats->count() > 0)
            <div class="p-3 border-top">
                <button class="btn btn-primary w-100" data-bs-toggle="modal" data-bs-target="#newChatModal">
                    <i class="bi bi-plus-circle me-2"></i>New Chat
                </button>
            </div>
            @endif
        </div>

        <!-- Right Side - Welcome/Empty State -->
        <div class="col-md-8 col-lg-9 bg-light d-flex align-items-center justify-content-center">
            <div class="text-center">
                <i class="bi bi-whatsapp text-success" style="font-size: 5rem;"></i>
                <h3 class="mt-3">WhatsApp Chat</h3>
                <p class="text-muted">Select a chat to start messaging</p>
            </div>
        </div>
    </div>
</div>

<!-- New Chat Modal -->
<div class="modal fade" id="newChatModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Start New Chat</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="{{ route('chats.show', $selectedAccount) }}" method="GET">
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Phone Number <span class="text-danger">*</span></label>
                        <input type="text" name="phone" class="form-control" 
                               placeholder="628123456789" required>
                        <small class="text-muted">Format: 628xxxxxxxxx (without +)</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Name (Optional)</label>
                        <input type="text" name="name" class="form-control" 
                               placeholder="Contact name">
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Start Chat</button>
                </div>
            </form>
        </div>
    </div>
</div>

<style>
.chat-item {
    transition: background-color 0.2s;
}
.chat-item:hover {
    background-color: #f5f5f5;
}
.chat-item.active {
    background-color: #e8f5e9;
}
</style>
@endsection