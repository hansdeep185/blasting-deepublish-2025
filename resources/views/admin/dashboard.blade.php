@extends('layouts.app')

@section('title', 'Admin Dashboard')
@section('page-title', 'Admin Dashboard')

@section('content')
<div class="row g-4 mb-4">
    <!-- User Statistics -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="text-muted mb-1">Total Users</h6>
                        <h3 class="mb-0">{{ $userStats['total'] }}</h3>
                        <small class="text-success">{{ $userStats['active'] }} active</small>
                    </div>
                    <div class="bg-primary bg-opacity-10 p-3 rounded">
                        <i class="bi bi-people text-primary fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Account Statistics -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="text-muted mb-1">WA Accounts</h6>
                        <h3 class="mb-0">{{ $accountStats['total'] }}</h3>
                        <small class="text-success">{{ $accountStats['connected'] }} connected</small>
                    </div>
                    <div class="bg-success bg-opacity-10 p-3 rounded">
                        <i class="bi bi-whatsapp text-success fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Message Statistics -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="text-muted mb-1">Messages (30d)</h6>
                        <h3 class="mb-0">{{ number_format($messageStats['total_sent']) }}</h3>
                        <small class="text-info">{{ number_format($messageStats['today']) }} today</small>
                    </div>
                    <div class="bg-info bg-opacity-10 p-3 rounded">
                        <i class="bi bi-chat-dots text-info fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Blast Statistics -->
    <div class="col-md-3">
        <div class="card border-0 shadow-sm">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="text-muted mb-1">Total Blasts</h6>
                        <h3 class="mb-0">{{ $blastStats['total'] }}</h3>
                        <small class="text-warning">{{ $blastStats['processing'] }} processing</small>
                    </div>
                    <div class="bg-warning bg-opacity-10 p-3 rounded">
                        <i class="bi bi-send text-warning fs-4"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <!-- Recent Blasts -->
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Recent Blast Activities</h5>
            </div>
            <div class="card-body p-0">
                @if($recentBlasts->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th>User</th>
                                <th>Campaign</th>
                                <th>Recipients</th>
                                <th>Status</th>
                                <th>Date</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($recentBlasts as $blast)
                            <tr>
                                <td>
                                    <div class="d-flex align-items-center">
                                        <img src="{{ $blast->user->avatar ?? 'https://ui-avatars.com/api/?name='.urlencode($blast->user->name) }}" 
                                             alt="Avatar" 
                                             class="rounded-circle me-2" 
                                             width="30" 
                                             height="30">
                                        <small>{{ $blast->user->name }}</small>
                                    </div>
                                </td>
                                <td>{{ $blast->name }}</td>
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
                                    @else
                                        <span class="badge bg-secondary">{{ ucfirst($blast->status) }}</span>
                                    @endif
                                </td>
                                <td>
                                    <small>{{ $blast->created_at->diffForHumans() }}</small>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <div class="text-center py-5">
                    <i class="bi bi-inbox text-muted" style="font-size: 3rem;"></i>
                    <p class="text-muted mt-3">No recent activities</p>
                </div>
                @endif
            </div>
        </div>
    </div>

    <!-- Top Users -->
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Top Users by Usage</h5>
            </div>
            <div class="card-body">
                @if($topUsers->count() > 0)
                    @foreach($topUsers->take(5) as $user)
                    <div class="d-flex justify-content-between align-items-center mb-3 pb-3 border-bottom">
                        <div class="d-flex align-items-center">
                            <img src="{{ $user->avatar ?? 'https://ui-avatars.com/api/?name='.urlencode($user->name) }}" 
                                 alt="Avatar" 
                                 class="rounded-circle me-2" 
                                 width="40" 
                                 height="40">
                            <div>
                                <h6 class="mb-0">{{ $user->name }}</h6>
                                <small class="text-muted">{{ $user->email }}</small>
                            </div>
                        </div>
                        <div class="text-end">
                            <div class="fw-bold">{{ number_format($user->message_used) }}</div>
                            <small class="text-muted">messages</small>
                        </div>
                    </div>
                    @endforeach
                @else
                <div class="text-center py-4">
                    <i class="bi bi-people text-muted" style="font-size: 2rem;"></i>
                    <p class="text-muted mt-2 mb-0">No users yet</p>
                </div>
                @endif
            </div>
        </div>
    </div>
</div>
@endsection