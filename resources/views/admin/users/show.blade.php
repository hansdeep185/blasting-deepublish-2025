@extends('layouts.app')

@section('title', 'User Details')
@section('page-title', 'User Details')

@section('content')
<div class="row">
    <!-- User Profile -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body text-center">
                <img src="{{ $user->avatar ?? 'https://ui-avatars.com/api/?name='.urlencode($user->name).'&size=150' }}" 
                     alt="Avatar" 
                     class="rounded-circle mb-3" 
                     width="150" 
                     height="150">
                <h4 class="mb-1">{{ $user->name }}</h4>
                <p class="text-muted mb-3">{{ $user->email }}</p>
                
                @if($user->is_active)
                    <span class="badge bg-success mb-3">Active</span>
                @else
                    <span class="badge bg-danger mb-3">Inactive</span>
                @endif

                <div class="d-grid gap-2">
                    <a href="{{ route('admin.users.edit', $user) }}" class="btn btn-primary btn-sm">
                        <i class="bi bi-pencil"></i> Edit User
                    </a>
                </div>
            </div>
        </div>

        <!-- Quick Stats -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h6 class="mb-0">Statistics</h6>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-6">
                        <div class="text-center p-3 bg-light rounded">
                            <h4 class="mb-0">{{ $stats['accounts'] }}</h4>
                            <small class="text-muted">Accounts</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center p-3 bg-light rounded">
                            <h4 class="mb-0">{{ $stats['blasts'] }}</h4>
                            <small class="text-muted">Campaigns</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center p-3 bg-light rounded">
                            <h4 class="mb-0">{{ $stats['contact_lists'] }}</h4>
                            <small class="text-muted">Lists</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center p-3 bg-light rounded">
                            <h4 class="mb-0">{{ $stats['total_contacts'] }}</h4>
                            <small class="text-muted">Contacts</small>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- User Details -->
    <div class="col-lg-8">
        <!-- Info Card -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Account Information</h5>
            </div>
            <div class="card-body">
                <div class="row g-4">
                    <div class="col-md-6">
                        <label class="form-label text-muted small">User ID</label>
                        <div class="fw-bold">{{ $user->id }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Role</label>
                        <div class="fw-bold">{{ ucfirst($user->role) }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Google ID</label>
                        <div class="fw-bold">{{ $user->google_id ?? 'Not connected' }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Email Verified</label>
                        <div class="fw-bold">
                            @if($user->email_verified_at)
                                <span class="badge bg-success">Yes</span>
                            @else
                                <span class="badge bg-warning">No</span>
                            @endif
                        </div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Joined</label>
                        <div class="fw-bold">{{ $user->created_at->format('M d, Y H:i') }}</div>
                    </div>
                    <div class="col-md-6">
                        <label class="form-label text-muted small">Last Updated</label>
                        <div class="fw-bold">{{ $user->updated_at->diffForHumans() }}</div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Message Quota -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">Message Quota</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-2">
                        <span>Used: {{ number_format($user->message_used) }}</span>
                        <span>Total: {{ number_format($user->message_quota) }}</span>
                    </div>
                    <div class="progress" style="height: 25px;">
                        <div class="progress-bar {{ $user->message_used >= $user->message_quota ? 'bg-danger' : 'bg-primary' }}" 
                             role="progressbar" 
                             style="width: {{ $user->message_quota > 0 ? ($user->message_used / $user->message_quota * 100) : 0 }}%">
                            {{ $user->message_quota > 0 ? round(($user->message_used / $user->message_quota * 100), 1) : 0 }}%
                        </div>
                    </div>
                </div>
                <div class="text-muted small">
                    Remaining: {{ number_format($user->message_quota - $user->message_used) }} messages
                </div>
            </div>
        </div>

        <!-- WhatsApp Accounts -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white">
                <h5 class="mb-0">WhatsApp Accounts</h5>
            </div>
            <div class="card-body">
                @if($user->accounts->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Session Name</th>
                                <th>Phone</th>
                                <th>Status</th>
                                <th>AI Agent</th>
                                <th>Created</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($user->accounts as $account)
                            <tr>
                                <td>{{ $account->session_name }}</td>
                                <td>{{ $account->phone_number ?? '-' }}</td>
                                <td>
                                    @if($account->status === 'connected')
                                        <span class="badge bg-success">Connected</span>
                                    @elseif($account->status === 'pending')
                                        <span class="badge bg-warning">Pending</span>
                                    @else
                                        <span class="badge bg-danger">Disconnected</span>
                                    @endif
                                </td>
                                <td>
                                    @if($account->ai_agent_active)
                                        <span class="badge bg-info">Active</span>
                                    @else
                                        <span class="badge bg-secondary">Inactive</span>
                                    @endif
                                </td>
                                <td>{{ $account->created_at->format('M d, Y') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-3 text-muted">
                    No WhatsApp accounts yet
                </div>
                @endif
            </div>
        </div>

        <!-- Recent Campaigns -->
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Recent Campaigns</h5>
            </div>
            <div class="card-body">
                @if($user->blastSchedules->count() > 0)
                <div class="table-responsive">
                    <table class="table table-sm mb-0">
                        <thead>
                            <tr>
                                <th>Campaign</th>
                                <th>Status</th>
                                <th>Recipients</th>
                                <th>Sent</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($user->blastSchedules->take(10) as $blast)
                            <tr>
                                <td>{{ $blast->name }}</td>
                                <td>
                                    @if($blast->status === 'completed')
                                        <span class="badge bg-success">Completed</span>
                                    @elseif($blast->status === 'processing')
                                        <span class="badge bg-info">Processing</span>
                                    @elseif($blast->status === 'scheduled')
                                        <span class="badge bg-warning">Scheduled</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($blast->status) }}</span>
                                    @endif
                                </td>
                                <td>{{ $blast->total_recipients }}</td>
                                <td>{{ $blast->sent_count }}</td>
                                <td>{{ $blast->created_at->format('M d, Y') }}</td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-3 text-muted">
                    No campaigns yet
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection