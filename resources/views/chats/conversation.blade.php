@extends('layouts.app')

@section('title', $chat ? $chat->display_name : 'Chat')
@section('page-title', 'Chat')

@section('content')
<div class="container-fluid px-0" style="height: calc(100vh - 120px);">
    <div class="row g-0 h-100">
        <!-- Left Sidebar - Chat List (Collapsed on mobile) -->
        <div class="col-md-4 col-lg-3 border-end bg-white d-none d-md-block">
            <div class="p-3 border-bottom bg-light">
                <a href="{{ route('chats.index', ['account_id' => $account->id]) }}" class="btn btn-sm btn-outline-secondary">
                    <i class="bi bi-arrow-left me-2"></i>Back to Chats
                </a>
            </div>
            <div class="overflow-auto" style="height: calc(100% - 70px);">
                @foreach($account->chats()->active()->latest('last_message_at')->limit(20)->get() as $c)
                <a href="{{ route('chats.conversation', ['account' => $account->id, 'chat' => $c->id]) }}" 
                   class="d-block text-decoration-none text-dark border-bottom chat-item {{ $chat && $chat->id == $c->id ? 'active' : '' }}">
                    <div class="p-3 d-flex align-items-start">
                        <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" 
                             style="width: 45px; height: 45px;">
                            <strong class="small">{{ $c->initials }}</strong>
                        </div>
                        <div class="flex-grow-1 overflow-hidden">
                            <div class="d-flex justify-content-between mb-1">
                                <h6 class="mb-0 small text-truncate">{{ $c->display_name }}</h6>
                                <small class="text-muted">{{ $c->last_message_at?->format('H:i') }}</small>
                            </div>
                            <small class="text-muted text-truncate d-block">{{ Str::limit($c->last_message, 30) }}</small>
                        </div>
                    </div>
                </a>
                @endforeach
            </div>
        </div>

        <!-- Right Side - Conversation -->
        <div class="col-md-8 col-lg-9 d-flex flex-column" style="height: 100%;">
            @if($chat)
                <!-- Chat Header -->
                <div class="p-3 border-bottom bg-white d-flex align-items-center" style="flex-shrink: 0;">
                    <a href="{{ route('chats.index', ['account_id' => $account->id]) }}" class="btn btn-sm btn-light me-3 d-md-none">
                        <i class="bi bi-arrow-left"></i>
                    </a>
                    <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center me-3" 
                         style="width: 45px; height: 45px;">
                        <strong>{{ $chat->initials }}</strong>
                    </div>
                    <div class="flex-grow-1">
                        <h6 class="mb-0">{{ $chat->display_name }}</h6>
                        <small class="text-muted">{{ $chat->contact_phone }}</small>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li>
                                <button class="dropdown-item" onclick="archiveChat({{ $chat->id }})">
                                    <i class="bi bi-archive me-2"></i>Archive Chat
                                </button>
                            </li>
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <button class="dropdown-item text-danger" onclick="deleteChat({{ $chat->id }})">
                                    <i class="bi bi-trash me-2"></i>Delete Chat
                                </button>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Messages Area -->
                <div id="messagesArea" class="flex-grow-1 p-3" 
                     style="overflow-y: auto; 
                            overflow-x: hidden;
                            background-image: url('https://user-images.githubusercontent.com/15075759/28719144-86dc0f70-73b1-11e7-911d-60d70fcded21.png'); 
                            background-size: cover;">
                    <div id="messagesList">
                        @forelse($chat->messages()->latest()->limit(50)->get()->reverse() as $message)
                            @include('chats.partials.message', ['message' => $message])
                        @empty
                            <div class="text-center text-muted py-5">
                                <i class="bi bi-chat-dots fs-1"></i>
                                <p class="mt-2">No messages yet. Start the conversation!</p>
                            </div>
                        @endforelse
                    </div>
                </div>

                <!-- Message Input -->
                <div class="p-3 border-top bg-white" style="flex-shrink: 0;">
                    <form id="messageForm" action="{{ route('chats.send', $account->id) }}" method="POST">
                        @csrf
                        <input type="hidden" name="chat_id" value="{{ $chat->id }}">
                        <div class="row g-2 align-items-center">
                            <div class="col">
                                <input 
                                    type="text" 
                                    name="message" 
                                    id="messageInput"
                                    placeholder="Type a message..." 
                                    class="form-control"
                                    style="height: 45px;"
                                    required
                                    autocomplete="off"
                                >
                            </div>
                            <div class="col-auto">
                                <button 
                                    id="sendBtn"
                                    type="submit" 
                                    class="btn btn-primary d-flex align-items-center justify-content-center"
                                    style="height: 45px; min-width: 100px;"
                                >
                                    <i class="bi bi-send-fill me-2"></i> Send
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            @else
                <!-- Empty State -->
                <div class="flex-grow-1 d-flex align-items-center justify-content-center bg-light">
                    <div class="text-center">
                        <i class="bi bi-chat-dots text-muted" style="font-size: 5rem;"></i>
                        <h4 class="mt-3">No Chat Selected</h4>
                        <p class="text-muted">Select a chat from the list to start messaging</p>
                    </div>
                </div>
            @endif
        </div>
    </div>
</div>

@if($chat)
<script>
const accountId = {{ $account->id }};
const chatId = {{ $chat->id }};
const messagesArea = document.getElementById('messagesArea');
const messagesList = document.getElementById('messagesList');
const messageForm = document.getElementById('messageForm');
const messageInput = document.getElementById('messageInput');
const sendBtn = document.getElementById('sendBtn');

// Auto scroll to bottom
function scrollToBottom() {
    messagesArea.scrollTop = messagesArea.scrollHeight;
}

// Initial scroll
setTimeout(scrollToBottom, 100);

// Send message
messageForm.addEventListener('submit', async function(e) {
    e.preventDefault();
    
    const message = messageInput.value.trim();
    if (!message) return;
    
    // Disable input
    sendBtn.disabled = true;
    messageInput.disabled = true;
    
    try {
        const formData = new FormData();
        formData.append('_token', document.querySelector('meta[name="csrf-token"]').content);
        formData.append('chat_id', chatId);
        formData.append('message', message);

        const response = await fetch(`/chats/${accountId}/send`, {
            method: 'POST',
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: formData
        });
        
        const data = await response.json();
        
        if (data.success) {
            // Clear input
            messageInput.value = '';
            
            // Add message to UI
            appendMessage(data.message);
            
            // Update lastMessageId for polling
            if (typeof lastMessageId !== 'undefined') {
                lastMessageId = data.message.id;
            }
            
            // Scroll to bottom
            setTimeout(scrollToBottom, 100);
        } else {
            console.error('Error:', data.error);
            alert('Failed to send message: ' + (data.error || 'Unknown error'));
        }
    } catch (error) {
        console.error('Error:', error);
        alert('Failed to send message. Please try again.');
    } finally {
        // Re-enable input
        sendBtn.disabled = false;
        messageInput.disabled = false;
        messageInput.focus();
    }
});

// Append message to UI
function appendMessage(message) {
    const messageHtml = createMessageElement(message);
    messagesList.insertAdjacentHTML('beforeend', messageHtml);
}

// Create message element
function createMessageElement(message) {
    const isOutgoing = message.direction === 'outgoing';
    const alignClass = isOutgoing ? 'justify-content-end' : 'justify-content-start';
    const bgClass = isOutgoing ? 'bg-success text-white' : 'bg-white';
    const textClass = isOutgoing ? 'text-white-50' : 'text-muted';
    const time = new Date(message.created_at).toLocaleTimeString('en-US', { hour: '2-digit', minute: '2-digit' });
    
    return `
        <div class="d-flex ${alignClass} mb-2">
            <div class="message-bubble ${bgClass} rounded px-3 py-2 shadow-sm" style="max-width: 70%;">
                <div style="white-space: pre-wrap; word-break: break-word;">${escapeHtml(message.message_content)}</div>
                <div class="text-end mt-1">
                    <small class="${textClass}" style="font-size: 0.75rem;">${time}</small>
                    ${isOutgoing ? getStatusIcon(message.status) : ''}
                </div>
            </div>
        </div>
    `;
}

// Get status icon
function getStatusIcon(status) {
    const icons = {
        'pending': '<i class="bi bi-clock ms-1"></i>',
        'sent': '<i class="bi bi-check ms-1"></i>',
        'delivered': '<i class="bi bi-check-all ms-1"></i>',
        'read': '<i class="bi bi-check-all text-info ms-1"></i>',
        'failed': '<i class="bi bi-exclamation-circle text-danger ms-1"></i>'
    };
    return icons[status] || '';
}

// Escape HTML
function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}

// Archive chat
async function archiveChat(chatId) {
    if (!confirm('Archive this chat?')) return;
    
    try {
        const response = await fetch(`/chats/${chatId}/archive`, {
            method: 'POST',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });
        
        if (response.ok) {
            window.location.href = '/chats?account_id=' + accountId;
        }
    } catch (error) {
        alert('Failed to archive chat');
    }
}

// Delete chat
async function deleteChat(chatId) {
    if (!confirm('Delete this chat? This cannot be undone.')) return;
    
    try {
        const response = await fetch(`/chats/${chatId}`, {
            method: 'DELETE',
            headers: {
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
                'Accept': 'application/json'
            }
        });
        
        if (response.ok) {
            window.location.href = '/chats?account_id=' + accountId;
        }
    } catch (error) {
        alert('Failed to delete chat');
    }
}

// Polling untuk sync messages dari WAHA setiap 5 detik
let lastMessageId = {{ $chat->messages()->latest()->first()->id ?? 0 }};
let isSyncing = false; // Flag to prevent multiple syncs
let syncInterval;

console.log('Starting WAHA sync with lastMessageId:', lastMessageId);

// Function to sync messages
async function syncWahaMessages() {
    if (isSyncing) {
        console.log('Sync already in progress. Skipping.');
        return;
    }
    
    isSyncing = true;
    console.log('Running sync...');

    try {
        const response = await fetch(`/chats/${chatId}/messages/sync`, {
            headers: {
                'Accept': 'application/json',
                'X-Requested-With': 'XMLHttpRequest'
            }
        });
        
        if (response.ok) {
            const data = await response.json();
            
            console.log('Sync response:', data);
            
            if (data.success && data.messages && data.messages.length > 0) {
                console.log('New messages from WAHA:', data.count);
                
                data.messages.forEach(message => {
                    appendMessage(message);
                    lastMessageId = message.id;
                });
                
                setTimeout(scrollToBottom, 100);
            }
        } else {
            console.error('Sync failed with status:', response.status);
        }
    } catch (error) {
        console.error('Error syncing messages:', error);
        // Stop polling if there's a persistent network error
        if (syncInterval) {
            clearInterval(syncInterval);
            console.error('Polling stopped due to network error.');
        }
    } finally {
        isSyncing = false;
    }
}

// Start polling
syncInterval = setInterval(syncWahaMessages, 8000); // Sync every 8 seconds
</script>
@endif

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
.message-bubble {
    box-shadow: 0 1px 2px rgba(0,0,0,0.1);
    word-wrap: break-word;
    overflow-wrap: break-word;
}

/* Fix scroll issue */
#messagesArea {
    height: 100%;
    overflow-y: auto !important;
    overflow-x: hidden;
}

#messagesList {
    min-height: min-content;
    display: flex;
    flex-direction: column;
    gap: 0;
}
</style>
@endsection