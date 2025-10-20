@extends('layouts.app')

@section('title', 'WhatsApp Accounts')
@section('page-title', 'WhatsApp Accounts')

@section('content')
<div class="row mb-4">
    <div class="col-12">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h5 class="mb-1">Your WhatsApp Accounts</h5>
                <p class="text-muted mb-0">Manage your WhatsApp sessions for sending messages</p>
            </div>
            <a href="{{ route('accounts.create') }}" class="btn btn-whatsapp">
                <i class="bi bi-plus-circle"></i> Add New Account
            </a>
        </div>
    </div>
</div>

@if($accounts->count() > 0)
<div class="row g-4">
    @foreach($accounts as $account)
    <div class="col-md-6 col-lg-4">
        <div class="card border-0 shadow-sm h-100">
            <div class="card-body">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <div>
                        <h6 class="mb-1">{{ $account->session_name }}</h6>
                        <small class="text-muted">
                            {{ $account->phone_number ?? 'Not connected' }}
                        </small>
                    </div>
                    <div class="dropdown">
                        <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                            <i class="bi bi-three-dots-vertical"></i>
                        </button>
                        <ul class="dropdown-menu">
                            <li>
                                <a class="dropdown-item" href="{{ route('accounts.show', $account) }}">
                                    <i class="bi bi-eye"></i> View Details
                                </a>
                            </li>
                            @if($account->isConnected())
                            <li>
                                <form action="{{ route('accounts.reconnect', $account) }}" method="POST" class="d-inline">
                                    @csrf
                                    <button type="submit" class="dropdown-item">
                                        <i class="bi bi-arrow-clockwise"></i> Reconnect
                                    </button>
                                </form>
                            </li>
                            @endif
                            <li><hr class="dropdown-divider"></li>
                            <li>
                                <form action="{{ route('accounts.destroy', $account) }}" method="POST" 
                                      onsubmit="return confirm('Are you sure you want to delete this account?')">
                                    @csrf
                                    @method('DELETE')
                                    <button type="submit" class="dropdown-item text-danger">
                                        <i class="bi bi-trash"></i> Delete
                                    </button>
                                </form>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Status Badge -->
                <div class="mb-3">
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

                    @if($account->ai_agent_active)
                        <span class="badge bg-info ms-1">
                            <i class="bi bi-robot"></i> AI Active
                        </span>
                    @endif
                </div>

                <!-- Stats -->
                <div class="row g-2 mb-3">
                    <div class="col-6">
                        <div class="text-center p-2 bg-light rounded">
                            <div class="fw-bold">{{ $account->blastSchedules()->count() }}</div>
                            <small class="text-muted">Campaigns</small>
                        </div>
                    </div>
                    <div class="col-6">
                        <div class="text-center p-2 bg-light rounded">
                            <div class="fw-bold">{{ $account->sessionMessages()->count() }}</div>
                            <small class="text-muted">Messages</small>
                        </div>
                    </div>
                </div>

                <!-- Actions -->
                <div class="d-grid gap-2">
                    <a href="{{ route('accounts.show', $account) }}" class="btn btn-sm btn-outline-primary">
                        <i class="bi bi-eye"></i> View Details
                    </a>
                </div>

                <div class="text-muted small mt-2">
                    <i class="bi bi-clock"></i> Created {{ $account->created_at->diffForHumans() }}
                </div>
            </div>
        </div>
    </div>
    @endforeach
</div>
@else
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center py-5">
                <i class="bi bi-whatsapp text-muted" style="font-size: 4rem;"></i>
                <h4 class="mt-3 mb-2">No WhatsApp Accounts Yet</h4>
                <p class="text-muted mb-4">
                    Create your first WhatsApp account to start sending messages
                </p>
                <a href="{{ route('accounts.create') }}" class="btn btn-whatsapp">
                    <i class="bi bi-plus-circle"></i> Add New Account
                </a>
            </div>
        </div>
    </div>
</div>
@endif
@endsection