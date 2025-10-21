@extends('layouts.app')

@section('title', 'Campaign Details: ' . $blast->name)
@section('page-title')
    Campaign Details
    <span class="text-muted fw-light ms-2">#{{ $blast->id }}</span>
@endsection

@section('content')
<div class="container-fluid">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="{{ route('blasts.index') }}" class="btn btn-outline-secondary">
            <i class="bi bi-arrow-left me-2"></i>Back to Campaigns
        </a>
        <div class="d-flex gap-2">
            <a href="{{ route('blasts.report', $blast) }}" class="btn btn-primary">
                <i class="bi bi-bar-chart me-2"></i>View Full Report
            </a>
            @if($blast->canBeCancelled())
            <form action="{{ route('blasts.cancel', $blast) }}" method="POST" onsubmit="return confirm('Are you sure you want to cancel this campaign?')">
                @csrf
                <button type="submit" class="btn btn-danger">
                    <i class="bi bi-x-circle me-2"></i>Cancel Campaign
                </button>
            </form>
            @endif
        </div>
    </div>

    <div class="row g-4">
        <div class="col-lg-7">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">{{ $blast->name }}</h5>
                </div>
                <div class="card-body">
                    @if($blast->description)
                    <p class="text-muted">{{ $blast->description }}</p>
                    <hr>
                    @endif

                    <div class="row g-3">
                        <div class="col-md-6">
                            <small class="text-muted d-block">Status</small>
                            <span class="badge bg-{{ $blast->status_color }} fs-6">
                                {{ $blast->status_label }}
                            </span>
                        </div>
                        <div class="col-md-6">
                            <small class="text-muted d-block">Schedule Type</small>
                            <strong>{{ $blast->schedule_type == 'scheduled' ? 'Scheduled' : 'Immediate' }}</strong>
                        </div>
                        @if($blast->scheduled_at)
                        <div class="col-12">
                            <small class="text-muted d-block">Scheduled At</small>
                            <strong>{{ $blast->scheduled_at->format('d M Y, H:i') }}</strong>
                            <span class="text-muted">({{ $blast->scheduled_at->diffForHumans() }})</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Source</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <small class="text-muted d-block">WhatsApp Account</small>
                        <strong>{{ $blast->account->name }}</strong>
                        <span class="text-muted">({{ $blast->account->phone_number }})</span>
                    </div>
                    <div>
                        <small class="text-muted d-block">Template</small>
                        <strong>{{ $blast->template->name }}</strong>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-5">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-header bg-white py-3">
                    <h5 class="mb-0">Statistics</h5>
                </div>
                <div class="card-body">
                    <div class="mb-3">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <small class="text-muted">Progress</small>
                            <small class="fw-semibold">{{ $blast->progress }}%</small>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar bg-success" role="progressbar" style="width: {{ $blast->progress }}%"></div>
                        </div>
                    </div>

                    <ul class="list-group list-group-flush">
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">Total Recipients</span>
                            <strong class="text-primary">{{ $blast->total_recipients }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">Sent</span>
                            <strong class="text-success">{{ $blast->sent_count }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">Failed</span>
                            <strong class="text-danger">{{ $blast->failed_count }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">Pending / Queued</span>
                            <strong class="text-warning">{{ $blast->pending_count }}</strong>
                        </li>
                        <li class="list-group-item d-flex justify-content-between align-items-center px-0">
                            <span class="text-muted">Success Rate</span>
                            <strong class="text-info">{{ $blast->success_rate }}%</strong>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="mb-0">Recent Messages (Up to 50)</h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover mb-0">
                    <thead>
                        <tr>
                            <th>Recipient</th>
                            <th>Phone</th>
                            <th>Status</th>
                            <th>Last Update</th>
                            <th>Error</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($blast->messages as $message)
                            <tr>
                                <td>{{ $message->recipient_name }}</td>
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
                                            {{ \Illuminate\Support\Str::limit($message->error_message, 40) }}
                                        </small>
                                    @else
                                        -
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="5" class="text-center py-4">
                                    <i class="bi bi-chat-dots fs-3 text-muted"></i>
                                    <p class="text-muted mb-0">No messages have been processed yet.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>
@endsection