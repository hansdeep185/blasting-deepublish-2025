@extends('layouts.app')

@section('title', 'Dashboard')
@section('page-title', 'Dashboard')

@section('content')
<div class="row">
    <!-- Stats Cards -->
    <div class="col-md-3 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small mb-1">WA Accounts</p>
                        <h3 class="mb-0">{{ $totalAccounts }}</h3>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                        <i class="bi bi-phone text-primary" style="font-size: 1.5rem;"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small class="text-success">
                        <i class="bi bi-check-circle-fill"></i> {{ $connectedAccounts }} Connected
                    </small>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small mb-1">Total Contacts</p>
                        <h3 class="mb-0">{{ number_format($totalContacts) }}</h3>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded">
                        <i class="bi bi-people text-success" style="font-size: 1.5rem;"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small class="text-primary">
                        <i class="bi bi-folder"></i> {{ $totalContactLists }} Lists
                    </small>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small mb-1">Blast Campaigns</p>
                        <h3 class="mb-0">{{ $totalBlasts }}</h3>
                    </div>
                    <div class="bg-info bg-opacity-10 p-3 rounded">
                        <i class="bi bi-send text-info" style="font-size: 1.5rem;"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small class="text-warning">
                        <i class="bi bi-clock"></i> {{ $scheduledBlasts }} Scheduled
                    </small>
                </div>
            </div>
        </div>
    </div>

    <div class="col-md-3 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <p class="text-muted small mb-1">Message Quota</p>
                        <h3 class="mb-0">{{ number_format($remainingQuota) }}</h3>
                    </div>
                    <div class="bg-warning bg-opacity-10 p-3 rounded">
                        <i class="bi bi-chat-dots text-warning" style="font-size: 1.5rem;"></i>
                    </div>
                </div>
                <div class="mt-2">
                    <small class="text-muted">
                        of {{ number_format(auth()->user()->message_quota) }} total
                    </small>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Quick Actions -->
<div class="row mb-4">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">Quick Actions</h5>
            </div>
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-3">
                        <a href="{{ route('accounts.create') }}" class="btn btn-outline-primary w-100 py-3">
                            <i class="bi bi-phone-fill d-block mb-2" style="font-size: 1.5rem;"></i>
                            Add WA Account
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ route('contact-lists.create') }}" class="btn btn-outline-success w-100 py-3">
                            <i class="bi bi-folder-plus d-block mb-2" style="font-size: 1.5rem;"></i>
                            Create Contact List
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ url('/blasts/create') }}" class="btn btn-outline-info w-100 py-3">
                            <i class="bi bi-send-fill d-block mb-2" style="font-size: 1.5rem;"></i>
                            New Blast
                            <span class="badge bg-secondary ms-1">Soon</span>
                        </a>
                    </div>
                    <div class="col-md-3">
                        <a href="{{ url('/templates') }}" class="btn btn-outline-warning w-100 py-3">
                            <i class="bi bi-file-text-fill d-block mb-2" style="font-size: 1.5rem;"></i>
                            Create Template
                            <span class="badge bg-secondary ms-1">Soon</span>
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Recent Activity & Stats -->
<div class="row">
    <!-- Recent Blasts -->
    <div class="col-md-8 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Recent Blast Campaigns</h5>
                <a href="{{ url('/blasts') }}" class="btn btn-sm btn-outline-primary">
                    View All
                </a>
            </div>
            <div class="card-body">
                @if($recentBlasts->isEmpty())
                    <div class="text-center py-5">
                        <i class="bi bi-send text-muted" style="font-size: 3rem;"></i>
                        <p class="text-muted mt-3 mb-0">No blast campaigns yet</p>
                        <small class="text-muted">Create your first blast campaign to get started</small>
                    </div>
                @else
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Campaign Name</th>
                                    <th>Status</th>
                                    <th>Recipients</th>
                                    <th>Sent</th>
                                    <th>Failed</th>
                                    <th>Date</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($recentBlasts as $blast)
                                    <tr>
                                        <td>
                                            <strong>{{ $blast->name }}</strong>
                                        </td>
                                        <td>
                                            @if($blast->status === 'scheduled')
                                                <span class="badge bg-warning">Scheduled</span>
                                            @elseif($blast->status === 'processing')
                                                <span class="badge bg-info">Processing</span>
                                            @elseif($blast->status === 'completed')
                                                <span class="badge bg-success">Completed</span>
                                            @elseif($blast->status === 'failed')
                                                <span class="badge bg-danger">Failed</span>
                                            @else
                                                <span class="badge bg-secondary">{{ ucfirst($blast->status) }}</span>
                                            @endif
                                        </td>
                                        <td>{{ number_format($blast->total_recipients ?? 0) }}</td>
                                        <td>
                                            <span class="text-success">{{ number_format($blast->sent_count ?? 0) }}</span>
                                        </td>
                                        <td>
                                            <span class="text-danger">{{ number_format($blast->failed_count ?? 0) }}</span>
                                        </td>
                                        <td>
                                            <small class="text-muted">
                                                {{ $blast->created_at->format('d M Y, H:i') }}
                                            </small>
                                        </td>
                                    </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                @endif
            </div>
        </div>
    </div>

    <!-- WA Accounts Status -->
    <div class="col-md-4 mb-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">WA Accounts</h5>
            </div>
            <div class="card-body">
                @if($accounts->isEmpty())
                    <div class="text-center py-4">
                        <i class="bi bi-phone text-muted" style="font-size: 2rem;"></i>
                        <p class="text-muted mt-3 mb-2">No WA accounts yet</p>
                        <a href="{{ route('accounts.create') }}" class="btn btn-sm btn-primary">
                            Add Account
                        </a>
                    </div>
                @else
                    <div class="list-group list-group-flush">
                        @foreach($accounts as $account)
                            <div class="list-group-item px-0">
                                <div class="d-flex justify-content-between align-items-center">
                                    <div>
                                        <h6 class="mb-1">{{ $account->name }}</h6>
                                        <small class="text-muted">{{ $account->session_name }}</small>
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
                                @if($account->ai_enabled)
                                    <small class="text-primary">
                                        <i class="bi bi-robot"></i> AI Agent Active
                                    </small>
                                @endif
                            </div>
                        @endforeach
                    </div>
                    <div class="mt-3">
                        <a href="{{ route('accounts.index') }}" class="btn btn-sm btn-outline-primary w-100">
                            Manage All Accounts
                        </a>
                    </div>
                @endif
            </div>
        </div>

        <!-- Blast Status Overview -->
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-header bg-white border-0 py-3">
                <h5 class="mb-0">Campaign Status</h5>
            </div>
            <div class="card-body">
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <small>Scheduled</small>
                        <small class="text-warning fw-bold">{{ $scheduledBlasts }}</small>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-warning" style="width: {{ $totalBlasts > 0 ? ($scheduledBlasts / $totalBlasts * 100) : 0 }}%"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <small>Processing</small>
                        <small class="text-info fw-bold">{{ $processingBlasts }}</small>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-info" style="width: {{ $totalBlasts > 0 ? ($processingBlasts / $totalBlasts * 100) : 0 }}%"></div>
                    </div>
                </div>
                <div class="mb-3">
                    <div class="d-flex justify-content-between mb-1">
                        <small>Completed</small>
                        <small class="text-success fw-bold">{{ $completedBlasts }}</small>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-success" style="width: {{ $totalBlasts > 0 ? ($completedBlasts / $totalBlasts * 100) : 0 }}%"></div>
                    </div>
                </div>
                <div>
                    <div class="d-flex justify-content-between mb-1">
                        <small>Failed</small>
                        <small class="text-danger fw-bold">{{ $failedBlasts }}</small>
                    </div>
                    <div class="progress" style="height: 8px;">
                        <div class="progress-bar bg-danger" style="width: {{ $totalBlasts > 0 ? ($failedBlasts / $totalBlasts * 100) : 0 }}%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Usage Tips -->
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm bg-primary bg-opacity-10">
            <div class="card-body">
                <h5 class="text-primary mb-3">
                    <i class="bi bi-lightbulb-fill"></i> Quick Tips
                </h5>
                <div class="row">
                    <div class="col-md-4">
                        <h6>1. Connect WhatsApp</h6>
                        <p class="small text-muted mb-0">
                            Add and connect your WhatsApp account to start sending messages
                        </p>
                    </div>
                    <div class="col-md-4">
                        <h6>2. Import Contacts</h6>
                        <p class="small text-muted mb-0">
                            Create contact lists and import your contacts via CSV
                        </p>
                    </div>
                    <div class="col-md-4">
                        <h6>3. Create Campaign</h6>
                        <p class="small text-muted mb-0">
                            Design your message template and schedule blast campaigns
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection