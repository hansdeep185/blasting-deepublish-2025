@extends('layouts.app')

@section('title', 'Account Details')
@section('page-title', 'Account Details')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <a href="{{ route('admin.accounts.index') }}" class="btn btn-outline-secondary me-3">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h3 class="mb-1">{{ $account->name }}</h3>
                <span class="badge bg-{{ $account->status == 'connected' ? 'success' : ($account->status == 'connecting' ? 'warning' : 'secondary') }}">
                    {{ ucfirst($account->status) }}
                </span>
            </div>
        </div>
        <div class="d-flex gap-2">
            @if($account->status == 'connected')
            <form action="{{ route('admin.accounts.force-disconnect', $account) }}" method="POST">
                @csrf
                <button type="submit" class="btn btn-warning" onclick="return confirm('Force disconnect this account?')">
                    <i class="bi bi-plug me-2"></i>Force Disconnect
                </button>
            </form>
            @endif
            <button type="button" class="btn btn-danger" onclick="confirmDelete()">
                <i class="bi bi-trash me-2"></i>Delete Account
            </button>
        </div>
    </div>

    <div class="row g-4">
        <!-- Left Column: Account Info -->
        <div class="col-lg-8">
            <!-- Basic Information -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-4">Account Information</h5>
                    
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="small text-muted mb-1">Account Name</label>
                            <p class="mb-0 fw-semibold">{{ $account->name }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1">Session ID</label>
                            <p class="mb-0">
                                <code class="bg-light px-2 py-1 rounded">{{ $account->session_id }}</code>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1">Phone Number</label>
                            <p class="mb-0">
                                @if($account->phone_number)
                                    <span class="badge bg-success-subtle text-success border border-success">
                                        {{ $account->phone_number }}
                                    </span>
                                @else
                                    <span class="text-muted">Not connected</span>
                                @endif
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1">Status</label>
                            <p class="mb-0">
                                <span class="badge bg-{{ $account->status == 'connected' ? 'success' : ($account->status == 'connecting' ? 'warning' : 'secondary') }}">
                                    <i class="bi bi-circle-fill me-1" style="font-size: 0.5rem;"></i>
                                    {{ ucfirst($account->status) }}
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1">Created At</label>
                            <p class="mb-0">{{ $account->created_at->format('d M Y H:i') }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1">Last Connected</label>
                            <p class="mb-0">
                                @if($account->connected_at)
                                    {{ $account->connected_at->format('d M Y H:i') }}
                                    <small class="text-muted">({{ $account->connected_at->diffForHumans() }})</small>
                                @else
                                    <span class="text-muted">Never connected</span>
                                @endif
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- User Information -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-4">Account Owner</h5>
                    
                    <div class="d-flex align-items-center">
                        <img src="{{ $account->user->avatar ?? 'https://ui-avatars.com/api/?name='.urlencode($account->user->name).'&background=25D366&color=fff' }}" 
                             alt="Avatar" 
                             class="rounded-circle me-3 border border-2" 
                             width="60" 
                             height="60">
                        <div class="flex-grow-1">
                            <h6 class="mb-1">{{ $account->user->name }}</h6>
                            <p class="text-muted mb-1">{{ $account->user->email }}</p>
                            <div class="d-flex gap-2">
                                <span class="badge bg-primary">{{ ucfirst($account->user->role) }}</span>
                                <span class="badge bg-{{ $account->user->is_active ? 'success' : 'secondary' }}">
                                    {{ $account->user->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </div>
                        </div>
                        <div>
                            <a href="{{ route('admin.users.show', $account->user) }}" class="btn btn-sm btn-outline-primary">
                                View User
                            </a>
                        </div>
                    </div>

                    <hr>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="small text-muted mb-1">Message Quota</label>
                            <div class="d-flex justify-content-between">
                                <span class="fw-semibold">{{ number_format($account->user->message_quota - $account->user->message_used) }}</span>
                                <span class="text-muted">/ {{ number_format($account->user->message_quota) }}</span>
                            </div>
                            <div class="progress mt-2" style="height: 6px;">
                                @php
                                    $percentage = $account->user->message_quota > 0 
                                        ? (($account->user->message_quota - $account->user->message_used) / $account->user->message_quota) * 100 
                                        : 0;
                                @endphp
                                <div class="progress-bar bg-success" 
                                     role="progressbar" 
                                     style="width: {{ $percentage }}%">
                                </div>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1">Total Accounts</label>
                            <p class="mb-0 fw-semibold">
                                {{ $account->user->accounts->count() }} accounts
                            </p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Connection History -->
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="mb-4">Connection History</h5>
                    
                    <div class="timeline">
                        <div class="timeline-item">
                            <div class="timeline-marker bg-primary"></div>
                            <div class="timeline-content">
                                <div class="d-flex justify-content-between">
                                    <strong>Account Created</strong>
                                    <small class="text-muted">{{ $account->created_at->format('d M Y H:i') }}</small>
                                </div>
                                <p class="text-muted small mb-0">Account registered in system</p>
                            </div>
                        </div>
                        
                        @if($account->connected_at)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <div class="d-flex justify-content-between">
                                    <strong>First Connection</strong>
                                    <small class="text-muted">{{ $account->connected_at->format('d M Y H:i') }}</small>
                                </div>
                                <p class="text-muted small mb-0">Successfully connected to WhatsApp</p>
                            </div>
                        </div>
                        @endif

                        @if($account->status == 'connected')
                        <div class="timeline-item">
                            <div class="timeline-marker bg-success"></div>
                            <div class="timeline-content">
                                <div class="d-flex justify-content-between">
                                    <strong>Currently Connected</strong>
                                    <small class="text-muted">{{ now()->format('d M Y H:i') }}</small>
                                </div>
                                <p class="text-muted small mb-0">Account is active and operational</p>
                            </div>
                        </div>
                        @endif

                        @if($account->status == 'disconnected' && $account->connected_at)
                        <div class="timeline-item">
                            <div class="timeline-marker bg-danger"></div>
                            <div class="timeline-content">
                                <div class="d-flex justify-content-between">
                                    <strong>Disconnected</strong>
                                    <small class="text-muted">{{ $account->updated_at->format('d M Y H:i') }}</small>
                                </div>
                                <p class="text-muted small mb-0">Account disconnected from WhatsApp</p>
                            </div>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
        </div>

        <!-- Right Column: Stats & Actions -->
        <div class="col-lg-4">
            <!-- Quick Stats -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-4">Quick Stats</h5>
                    
                    <div class="text-center p-3 bg-light rounded mb-3">
                        <div class="text-primary mb-2">
                            <i class="bi bi-clock-history fs-1"></i>
                        </div>
                        <h4 class="mb-0">
                            @if($account->connected_at)
                                {{ $account->connected_at->diffForHumans(null, true) }}
                            @else
                                Never
                            @endif
                        </h4>
                        <small class="text-muted">Since Connected</small>
                    </div>

                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Messages Sent</span>
                        <strong>0</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total Campaigns</span>
                        <strong>0</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Success Rate</span>
                        <strong class="text-success">0%</strong>
                    </div>
                </div>
            </div>

            <!-- QR Code -->
            @if($account->status == 'connecting' && $account->qr_code)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4 text-center">
                    <h5 class="mb-3">QR Code</h5>
                    <img src="{{ $account->qr_code }}" alt="QR Code" class="img-fluid rounded" style="max-width: 250px;">
                    <p class="text-muted small mt-3 mb-0">Scan with WhatsApp to connect</p>
                </div>
            </div>
            @endif

            <!-- Danger Zone -->
            <div class="card border-danger border-2 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="text-danger mb-3">
                        <i class="bi bi-exclamation-triangle me-2"></i>Danger Zone
                    </h5>
                    
                    @if($account->status == 'connected')
                    <form action="{{ route('admin.accounts.force-disconnect', $account) }}" method="POST" class="mb-3">
                        @csrf
                        <button type="submit" class="btn btn-warning w-100" onclick="return confirm('Force disconnect this account? User will need to scan QR code again.')">
                            <i class="bi bi-plug me-2"></i>Force Disconnect
                        </button>
                    </form>
                    @endif

                    <button type="button" class="btn btn-danger w-100" onclick="confirmDelete()">
                        <i class="bi bi-trash me-2"></i>Delete Account
                    </button>
                    
                    <p class="text-muted small mb-0 mt-3">
                        <i class="bi bi-info-circle me-1"></i>
                        These actions are permanent and cannot be undone.
                    </p>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Form (Hidden) -->
<form id="delete-form" action="{{ route('admin.accounts.destroy', $account) }}" method="POST" class="d-none">
    @csrf
    @method('DELETE')
</form>

<script>
function confirmDelete() {
    if (confirm('Are you sure you want to delete this account? This action cannot be undone and will remove all associated data.')) {
        document.getElementById('delete-form').submit();
    }
}
</script>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline-item {
    position: relative;
    padding-bottom: 20px;
}

.timeline-item:last-child {
    padding-bottom: 0;
}

.timeline-item::before {
    content: '';
    position: absolute;
    left: -22px;
    top: 8px;
    bottom: -12px;
    width: 2px;
    background: #e9ecef;
}

.timeline-item:last-child::before {
    display: none;
}

.timeline-marker {
    position: absolute;
    left: -26px;
    top: 4px;
    width: 10px;
    height: 10px;
    border-radius: 50%;
    border: 2px solid white;
    box-shadow: 0 0 0 2px currentColor;
}

.timeline-content {
    background: #f8f9fa;
    padding: 12px;
    border-radius: 8px;
}
</style>
@endsection