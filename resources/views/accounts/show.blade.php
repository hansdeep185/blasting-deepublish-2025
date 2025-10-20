@extends('layouts.app')

@section('title', 'Account Details')
@section('page-title', 'WhatsApp Account Details')

@section('content')
<div class="row">
    <!-- Account Info -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Account Information</h5>
                <div class="dropdown">
                    <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                        <i class="bi bi-three-dots-vertical"></i>
                    </button>
                    <ul class="dropdown-menu">
                        <li>
                            <form action="{{ route('accounts.reconnect', $account) }}" method="POST" class="d-inline">
                                @csrf
                                <button type="submit" class="dropdown-item">
                                    <i class="bi bi-arrow-clockwise"></i> Reconnect
                                </button>
                            </form>
                        </li>
                        <li><hr class="dropdown-divider"></li>
                        <li>
                            <form action="{{ route('accounts.destroy', $account) }}" method="POST" 
                                  onsubmit="return confirm('Are you sure you want to delete this account?')">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="dropdown-item text-danger">
                                    <i class="bi bi-trash"></i> Delete Account
                                </button>
                            </form>
                        </li>
                    </ul>
                </div>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Session Name</label>
                        <div class="fw-bold">{{ $account->session_name }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Phone Number</label>
                        <div class="fw-bold">{{ $account->phone_number ?? 'Not connected' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Status</label>
                        <div>
                            @if($account->status === 'connected')
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle"></i> Connected
                                </span>
                            @elseif($account->status === 'pending')
                                <span class="badge bg-warning">
                                    <i class="bi bi-clock"></i> Pending
                                </span>
                            @elseif($account->status === 'disconnected')
                                <span class="badge bg-danger">
                                    <i class="bi bi-x-circle"></i> Disconnected
                                </span>
                            @else
                                <span class="badge bg-secondary">
                                    {{ ucfirst($account->status) }}
                                </span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">WAHA Status</label>
                        <div>
                            <span class="badge bg-info">{{ $wahaStatus }}</span>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">AI Agent</label>
                        <div class="form-check form-switch">
                            <input class="form-check-input" 
                                   type="checkbox" 
                                   id="aiAgentToggle"
                                   {{ $account->ai_agent_active ? 'checked' : '' }}
                                   onchange="toggleAiAgent()">
                            <label class="form-check-label" for="aiAgentToggle">
                                {{ $account->ai_agent_active ? 'Active' : 'Inactive' }}
                            </label>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Created At</label>
                        <div class="fw-bold">{{ $account->created_at->format('M d, Y H:i') }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- QR Code Section (if pending) -->
        @if($account->isPending())
            @if($qrCode)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-qr-code"></i> Scan QR Code to Connect
                    </h5>
                </div>
                <div class="card-body text-center py-5">
                    <div class="alert alert-warning">
                        <i class="bi bi-exclamation-triangle"></i>
                        <strong>Action Required:</strong> Please scan this QR code with your WhatsApp app to connect.
                    </div>

                    <div class="qr-code-container mb-4">
                        <img src="{{ $qrCode }}" alt="QR Code" class="img-fluid" style="max-width: 300px;">
                    </div>

                    <div class="steps">
                        <h6 class="mb-3">How to scan:</h6>
                        <ol class="text-start d-inline-block">
                            <li class="mb-2">Open WhatsApp on your phone</li>
                            <li class="mb-2">Tap Menu (⋮) or Settings</li>
                            <li class="mb-2">Tap "Linked Devices"</li>
                            <li class="mb-2">Tap "Link a Device"</li>
                            <li>Point your phone at this screen to capture the code</li>
                        </ol>
                    </div>

                    <button class="btn btn-primary mt-3" onclick="window.location.reload()">
                        <i class="bi bi-arrow-clockwise"></i> Refresh QR Code
                    </button>
                </div>
            </div>
            @else
            <!-- Loading QR Code -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white">
                    <h5 class="mb-0">
                        <i class="bi bi-qr-code"></i> Generating QR Code
                    </h5>
                </div>
                <div class="card-body text-center py-5">
                    <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;">
                        <span class="visually-hidden">Loading...</span>
                    </div>
                    <h5>Please wait...</h5>
                    <p class="text-muted">We're generating your QR code. This usually takes 5-10 seconds.</p>
                    <p class="text-muted small">WAHA Status: <span class="badge bg-info">{{ $wahaStatus }}</span></p>
                    
                    <div class="mt-3">
                        <button class="btn btn-primary" onclick="window.location.reload()">
                            <i class="bi bi-arrow-clockwise"></i> Refresh Now
                        </button>
                    </div>
                    
                    <div class="alert alert-info mt-4">
                        <i class="bi bi-info-circle"></i>
                        <small>Page will auto-refresh in <span id="countdown">10</span> seconds...</small>
                    </div>
                </div>
            </div>
            @endif
        @endif

        <!-- Connected Info -->
        @if($account->isConnected())
        <div class="alert alert-success">
            <i class="bi bi-check-circle"></i>
            <strong>Connected!</strong> Your WhatsApp account is ready to send messages.
        </div>
        @endif

        <!-- Disconnected Warning -->
        @if($account->isDisconnected())
        <div class="alert alert-danger">
            <i class="bi bi-x-circle"></i>
            <strong>Disconnected!</strong> Your WhatsApp account needs to be reconnected.
            <form action="{{ route('accounts.reconnect', $account) }}" method="POST" class="d-inline ms-2">
                @csrf
                <button type="submit" class="btn btn-sm btn-danger">
                    <i class="bi bi-arrow-clockwise"></i> Reconnect Now
                </button>
            </form>
        </div>
        @endif
    </div>

    <!-- Quick Actions -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <a href="{{ route('chats.show', $account) }}" class="btn btn-outline-primary">
                        <i class="bi bi-chat-dots"></i> Open Chat
                    </a>
                    <a href="{{ route('blasts.create') }}?account={{ $account->id }}" class="btn btn-outline-success">
                        <i class="bi bi-send"></i> Create Blast
                    </a>
                    <button class="btn btn-outline-info" onclick="checkStatus()">
                        <i class="bi bi-arrow-clockwise"></i> Check Status
                    </button>
                </div>
            </div>
        </div>

        <!-- Statistics -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Statistics</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="text-center p-3 bg-light rounded">
                            <h4 class="mb-0">{{ $account->blastSchedules()->count() }}</h4>
                            <small class="text-muted">Campaigns</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center p-3 bg-light rounded">
                            <h4 class="mb-0">{{ $account->sessionMessages()->count() }}</h4>
                            <small class="text-muted">Messages</small>
                        </div>
                    </div>
                    <div class="col-12">
                        <div class="text-center p-3 bg-light rounded">
                            <h4 class="mb-0">{{ $account->rate_limit_delay }}s</h4>
                            <small class="text-muted">Rate Limit Delay</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function toggleAiAgent() {
    const checkbox = document.getElementById('aiAgentToggle');
    const accountId = {{ $account->id }};
    
    fetch(`/accounts/${accountId}/toggle-ai`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const label = checkbox.nextElementSibling;
            label.textContent = data.ai_agent_active ? 'Active' : 'Inactive';
            
            // Show notification
            alert(data.message);
        } else {
            checkbox.checked = !checkbox.checked;
            alert('Failed to toggle AI Agent');
        }
    })
    .catch(error => {
        checkbox.checked = !checkbox.checked;
        alert('Error: ' + error.message);
    });
}

function checkStatus() {
    window.location.reload();
}

// Auto refresh if QR code is not available and account is pending
@if($account->isPending() && !$qrCode)
let countdown = 10;
const countdownElement = document.getElementById('countdown');

const countdownInterval = setInterval(() => {
    countdown--;
    if (countdownElement) {
        countdownElement.textContent = countdown;
    }
    
    if (countdown <= 0) {
        clearInterval(countdownInterval);
        window.location.reload();
    }
}, 1000);
@endif

// Auto refresh QR code every 30 seconds if pending
@if($account->isPending() && $qrCode)
setInterval(() => {
    window.location.reload();
}, 30000);
@endif
</script>
@endpush