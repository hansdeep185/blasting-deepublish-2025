@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="row g-4 mb-4">
    <!-- Quota Card -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="text-muted mb-1">Message Quota</h6>
                        <h3 class="mb-0">{{ number_format($stats['quota_remaining']) }}</h3>
                        <small class="text-muted">of {{ number_format(auth()->user()->message_quota) }} remaining</small>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                        <i class="bi bi-chat-text text-primary fs-4"></i>
                    </div>
                </div>
                <div class="progress" style="height: 10px;">
                    <div class="progress-bar bg-primary" role="progressbar" 
                         style="width: {{ $stats['quota_percentage'] }}%"
                         aria-valuenow="{{ $stats['quota_percentage'] }}" 
                         aria-valuemin="0" 
                         aria-valuemax="100">
                    </div>
                </div>
                <small class="text-muted">{{ $stats['quota_percentage'] }}% used</small>
            </div>
        </div>
    </div>

    <!-- Accounts Card -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="text-muted mb-1">Connected Accounts</h6>
                        <h3 class="mb-0">{{ $stats['connected_accounts'] }}</h3>
                        <small class="text-muted">of {{ $stats['total_accounts'] }} total</small>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded">
                        <i class="bi bi-whatsapp text-success fs-4"></i>
                    </div>
                </div>
                <a href="{{ route('accounts.index') }}" class="btn btn-sm btn-outline-success">
                    Manage Accounts
                </a>
            </div>
        </div>
    </div>

    <!-- Contacts Card -->
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="text-muted mb-1">Total Contacts</h6>
                        <h3 class="mb-0">{{ number_format($stats['total_contacts']) }}</h3>
                        <small class="text-muted">in {{ $stats['total_lists'] }} lists</small>
                    </div>
                    <div class="bg-info bg-opacity-10 p-3 rounded">
                        <i class="bi bi-people text-info fs-4"></i>
                    </div>
                </div>
                <a href="{{ route('contact-lists.index') }}" class="btn btn-sm btn-outline-info">
                    Manage Contacts
                </a>
            </div>
        </div>
    </div>
</div>

<!-- Blast Statistics -->
<div class="row g-4 mb-4">
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="bi bi-send text-primary fs-1 mb-2"></i>
                <h4 class="mb-0">{{ $blastStats['total'] }}</h4>
                <small class="text-muted">Total Blasts</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="bi bi-clock text-warning fs-1 mb-2"></i>
                <h4 class="mb-0">{{ $blastStats['scheduled'] }}</h4>
                <small class="text-muted">Scheduled</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="bi bi-hourglass-split text-info fs-1 mb-2"></i>
                <h4 class="mb-0">{{ $blastStats['processing'] }}</h4>
                <small class="text-muted">Processing</small>
            </div>
        </div>
    </div>
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <i class="bi bi-check-circle text-success fs-1 mb-2"></i>
                <h4 class="mb-0">{{ $blastStats['completed'] }}</h4>
                <small class="text-muted">Completed</small>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Blasts -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Blast Campaigns</h5>
                <a href="{{ route('blasts.index') }}" class="btn btn-sm btn-outline-primary">View All</a>
            </div>
            <div class="card-body p-0">
                @if($recentBlasts->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>Name</th>
                                <th>Template</th>
                                <th>Recipients</th>
                                <th>Status</th>
                                <th>Scheduled</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentBlasts as $blast)
                            <tr>
                                <td>
                                    <a href="{{ route('blasts.show', $blast) }}" class="text-decoration-none">
                                        {{ $blast->name }}
                                    </a>
                                </td>
                                <td>{{ $blast->template->name }}</td>
                                <td>
                                    <span class="badge bg-light text-dark">
                                        {{ $blast->sent_count }}/{{ $blast->total_recipients }}
                                    </span>
                                </td>
                                <td>
                                    @if($blast->status === 'completed')
                                        <span class="badge bg-success">Completed</span>
                                    @elseif($blast->status === 'processing')
                                        <span class="badge bg-info">Processing</span>
                                    @elseif($blast->status === 'scheduled')
                                        <span class="badge bg-warning">Scheduled</span>
                                    @elseif($blast->status === 'failed')
                                        <span class="badge bg-danger">Failed</span>
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($blast->status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    <small>{{ $blast->scheduled_at ? $blast->scheduled_at->format('M d, Y H:i') : '-' }}</small>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-5">
                    <i class="bi bi-send text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3">No blast campaigns yet</p>
                    <a href="{{ route('blasts.create') }}" class="btn btn-primary btn-sm">
                        Create Your First Blast
                    </a>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Account Status -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white d-flex justify-content-between align-items-center">
                <h5 class="mb-0">WhatsApp Accounts</h5>
                <a href="{{ route('accounts.create') }}" class="btn btn-sm btn-whatsapp">
                    <i class="bi bi-plus"></i>
                </a>
            </div>
            <div class="card-body">
                @if($accounts->count() > 0)
                    @foreach($accounts as $account)
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <div>
                            <h6 class="mb-1">{{ $account->session_name }}</h6>
                            <small class="text-muted">{{ $account->phone_number ?? 'Not connected' }}</small>
                        </div>
                        <div>
                            @if($account->status === 'connected')
                                <span class="badge bg-success">
                                    <i class="bi bi-check-circle"></i> Connected
                                </span>
                            @elseif($account->status === 'pending')
                                <span class="badge bg-warning">
                                    <i class="bi bi-clock"></i> Pending
                                </span>
                            @else
                                <span class="badge bg-danger">
                                    <i class="bi bi-x-circle"></i> Disconnected
                                </span>
                            @endif
                        </div>
                    </div>
                    @endforeach
                @else
                <div class="text-center py-4">
                    <i class="bi bi-phone text-muted" style="font-size: 2rem;"></i>
                    <p class="text-muted mt-2 mb-0">No accounts yet</p>
                    <small class="text-muted">Add your first WhatsApp account</small>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection