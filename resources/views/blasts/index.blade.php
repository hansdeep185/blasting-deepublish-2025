@extends('layouts.app')

@section('title', 'Blast Campaigns')
@section('page-title', 'Blast Campaigns')

@section('content')
<div class="container-fluid">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">Blast Campaigns</h2>
            <p class="text-muted mb-0">Send bulk messages to your contacts</p>
        </div>
        <a href="{{ route('blasts.create') }}" class="btn btn-primary">
            <i class="bi bi-send me-2"></i>Create Campaign
        </a>
    </div>

    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-primary mb-2">
                        <i class="bi bi-broadcast fs-2"></i>
                    </div>
                    <h3 class="mb-0 fw-bold">{{ $stats['total'] }}</h3>
                    <p class="text-muted mb-0 small">Total Campaigns</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-warning mb-2">
                        <i class="bi bi-clock-history fs-2"></i>
                    </div>
                    <h3 class="mb-0 fw-bold">{{ $stats['pending'] }}</h3>
                    <p class="text-muted mb-0 small">Pending</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-primary mb-2">
                        <i class="bi bi-arrow-repeat fs-2"></i>
                    </div>
                    <h3 class="mb-0 fw-bold">{{ $stats['processing'] }}</h3>
                    <p class="text-muted mb-0 small">Processing</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-success mb-2">
                        <i class="bi bi-check-circle fs-2"></i>
                    </div>
                    <h3 class="mb-0 fw-bold">{{ $stats['completed'] }}</h3>
                    <p class="text-muted mb-0 small">Completed</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('blasts.index') }}" method="GET" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small text-muted">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Search campaigns..." value="{{ request('search') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="draft" {{ request('status') == 'draft' ? 'selected' : '' }}>Draft</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="scheduled" {{ request('status') == 'scheduled' ? 'selected' : '' }}>Scheduled</option>
                        <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
                        <option value="completed" {{ request('status') == 'completed' ? 'selected' : '' }}>Completed</option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                        <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="{{ route('blasts.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Campaigns List -->
    @if($blasts->count() > 0)
        <div class="row g-3 mb-4">
            @foreach($blasts as $blast)
            <div class="col-lg-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body">
                        <!-- Header -->
                        <div class="d-flex justify-content-between align-items-start mb-3">
                            <div class="flex-grow-1">
                                <h5 class="mb-1">{{ $blast->name }}</h5>
                                <div class="d-flex align-items-center gap-2 flex-wrap">
                                    <span class="badge bg-{{ $blast->status_color }}">
                                        {{ $blast->status_label }}
                                    </span>
                                    @if($blast->schedule_type === 'scheduled')
                                        <span class="badge bg-info-subtle text-info border border-info">
                                            <i class="bi bi-calendar"></i> Scheduled
                                        </span>
                                    @endif
                                </div>
                            </div>
                            <div class="dropdown">
                                <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                                    <i class="bi bi-three-dots-vertical"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end">
                                    <li>
                                        <a class="dropdown-item" href="{{ route('blasts.show', $blast) }}">
                                            <i class="bi bi-eye me-2"></i>View Details
                                        </a>
                                    </li>
                                    <li>
                                        <a class="dropdown-item" href="{{ route('blasts.report', $blast) }}">
                                            <i class="bi bi-bar-chart me-2"></i>View Report
                                        </a>
                                    </li>
                                    @if($blast->canBeCancelled())
                                    <li><hr class="dropdown-divider"></li>
                                    <li>
                                        <form action="{{ route('blasts.cancel', $blast) }}" method="POST">
                                            @csrf
                                            <button type="submit" class="dropdown-item text-danger" onclick="return confirm('Cancel this campaign?')">
                                                <i class="bi bi-x-circle me-2"></i>Cancel Campaign
                                            </button>
                                        </form>
                                    </li>
                                    @endif
                                </ul>
                            </div>
                        </div>

                        <!-- Description -->
                        @if($blast->description)
                        <p class="text-muted small mb-3">{{ Str::limit($blast->description, 100) }}</p>
                        @endif

                        <!-- Campaign Info -->
                        <div class="row g-2 mb-3">
                            <div class="col-6">
                                <div class="bg-light rounded p-2">
                                    <small class="text-muted d-block">Account</small>
                                    <strong class="small">{{ $blast->account->name }}</strong>
                                </div>
                            </div>
                            <div class="col-6">
                                <div class="bg-light rounded p-2">
                                    <small class="text-muted d-block">Template</small>
                                    <strong class="small">{{ $blast->template->name }}</strong>
                                </div>
                            </div>
                            @if($blast->scheduled_at)
                            <div class="col-12">
                                <div class="bg-light rounded p-2">
                                    <small class="text-muted d-block">Scheduled At</small>
                                    <strong class="small">
                                        <i class="bi bi-calendar"></i>
                                        {{ $blast->scheduled_at->format('d M Y H:i') }}
                                        ({{ $blast->scheduled_at->diffForHumans() }})
                                    </strong>
                                </div>
                            </div>
                            @endif
                        </div>

                        <!-- Progress -->
                        @if($blast->total_recipients > 0)
                        <div class="mb-3">
                            <div class="d-flex justify-content-between align-items-center mb-1">
                                <small class="text-muted">Progress</small>
                                <small class="fw-semibold">{{ $blast->progress }}%</small>
                            </div>
                            <div class="progress" style="height: 8px;">
                                <div class="progress-bar bg-success" role="progressbar" style="width: {{ $blast->progress }}%"></div>
                            </div>
                        </div>
                        @endif

                        <!-- Statistics -->
                        <div class="row g-2 text-center pt-3 border-top">
                            <div class="col-3">
                                <div class="text-primary fw-bold">{{ $blast->total_recipients }}</div>
                                <small class="text-muted">Total</small>
                            </div>
                            <div class="col-3">
                                <div class="text-success fw-bold">{{ $blast->sent_count }}</div>
                                <small class="text-muted">Sent</small>
                            </div>
                            <div class="col-3">
                                <div class="text-danger fw-bold">{{ $blast->failed_count }}</div>
                                <small class="text-muted">Failed</small>
                            </div>
                            <div class="col-3">
                                <div class="text-warning fw-bold">{{ $blast->pending_count }}</div>
                                <small class="text-muted">Pending</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center">
            {{ $blasts->withQueryString()->links() }}
        </div>
    @else
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-send text-muted" style="font-size: 4rem;"></i>
                <h4 class="mt-3">No Campaigns Yet</h4>
                <p class="text-muted mb-4">Create your first blast campaign to reach your contacts</p>
                <a href="{{ route('blasts.create') }}" class="btn btn-primary">
                    <i class="bi bi-send me-2"></i>Create Campaign
                </a>
            </div>
        </div>
    @endif
</div>
@endsection