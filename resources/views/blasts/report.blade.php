@extends('layouts.app')

@section('title', 'Campaign Report: ' . $blast->name)
@section('page-title', 'Campaign Report')

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="mb-1">{{ $blast->name }}</h2>
            <p class="text-muted mb-0">Detailed report of all messages</p>
        </div>
        <a href="{{ route('blasts.show', $blast) }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Campaign
        </a>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-primary mb-2">
                        <i class="bi bi-people fs-2"></i>
                    </div>
                    <h3 class="mb-0 fw-bold">{{ $messageStats['total'] }}</h3>
                    <p class="text-muted mb-0 small">Total Recipients</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-success mb-2">
                        <i class="bi bi-check-circle fs-2"></i>
                    </div>
                    <h3 class="mb-0 fw-bold">{{ $messageStats['sent'] }}</h3>
                    <p class="text-muted mb-0 small">Sent</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-danger mb-2">
                        <i class="bi bi-x-circle fs-2"></i>
                    </div>
                    <h3 class="mb-0 fw-bold">{{ $messageStats['failed'] }}</h3>
                    <p class="text-muted mb-0 small">Failed</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-warning mb-2">
                        <i class="bi bi-clock-history fs-2"></i>
                    </div>
                    <h3 class="mb-0 fw-bold">{{ $messageStats['pending'] }}</h3>
                    <p class="text-muted mb-0 small">Pending/Queued</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('blasts.report', $blast) }}" method="GET" class="row g-3">
                <div class="col-md-6">
                    <label class="form-label small text-muted">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Search by name or phone..." value="{{ request('search') }}">
                </div>
                <div class="col-md-4">
                    <label class="form-label small text-muted">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="queued" {{ request('status') == 'queued' ? 'selected' : '' }}>Queued</option>
                        <option value="sent" {{ request('status') == 'sent' ? 'selected' : '' }}>Sent</option>
                        <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
                        <option value="read" {{ request('status') == 'read' ? 'selected' : '' }}>Read</option>
                        <option value="failed" {{ request('status') == 'failed' ? 'selected' : '' }}>Failed</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="{{ route('blasts.report', $blast) }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Recipient</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Last Update</th>
                            <th>Details / Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($messages as $message)
                            <tr>
                                <td>
                                    {{ $message->recipient_name }}
                                    @if($message->contact)
                                    <a href="#" class="d-block small text-muted text-decoration-none">
                                        View Contact
                                    </a>
                                    @endif
                                </td>
                                <td>{{ $message->phone_number }}</td>
                                <td>
                                    <span class="badge bg-{{ $message->status_color }}">
                                        <i class="bi bi-{{ $message->status_icon }} me-1"></i>
                                        {{ ucfirst($message->status) }}
                                    </span>
                                </td>
                                <td>{{ $message->updated_at->format('d M Y H:i') }}</td>
                                <td>
                                    @if($message->status == 'failed')
                                        <small class="text-danger" title="{{ $message->error_message }}">
                                            {{ \Illuminate\Support\Str::limit($message->error_message, 50) }}
                                        </small>
                                    @elseif($message->waha_message_id)
                                        <small class="text-muted">ID: {{ $message->waha_message_id }}</small>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-5">
                                    <i class="bi bi-search fs-3 text-muted"></i>
                                    <h4 class="mt-3">No Messages Found</h4>
                                    <p class="text-muted mb-0">No messages match your current filters.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
            
            @if($messages->hasPages())
            <div class="card-footer bg-white border-0">
                {{ $messages->links() }}
            </div>
            @endif
        </div>
    </div>
</div>
@endsection