@extends('layouts.app')

@section('title', 'All WhatsApp Accounts')
@section('page-title', 'All WhatsApp Accounts')

@section('content')
<div class="container-fluid">
    <!-- Stats Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-primary mb-2">
                        <i class="bi bi-phone fs-2"></i>
                    </div>
                    <h3 class="mb-0 fw-bold">{{ $stats['total'] }}</h3>
                    <p class="text-muted mb-0 small">Total Accounts</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-success mb-2">
                        <i class="bi bi-check-circle fs-2"></i>
                    </div>
                    <h3 class="mb-0 fw-bold">{{ $stats['connected'] }}</h3>
                    <p class="text-muted mb-0 small">Connected</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-danger mb-2">
                        <i class="bi bi-x-circle fs-2"></i>
                    </div>
                    <h3 class="mb-0 fw-bold">{{ $stats['disconnected'] }}</h3>
                    <p class="text-muted mb-0 small">Disconnected</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm">
                <div class="card-body text-center">
                    <div class="text-info mb-2">
                        <i class="bi bi-people fs-2"></i>
                    </div>
                    <h3 class="mb-0 fw-bold">{{ $stats['users'] }}</h3>
                    <p class="text-muted mb-0 small">Total Users</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filter & Search -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body">
            <form action="{{ route('admin.accounts.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <label class="form-label small text-muted">Search</label>
                    <input type="text" name="search" class="form-control" placeholder="Search by name, phone, or session..." value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">User</label>
                    <select name="user_id" class="form-select">
                        <option value="">All Users</option>
                        @foreach($users as $user)
                            <option value="{{ $user->id }}" {{ request('user_id') == $user->id ? 'selected' : '' }}>
                                {{ $user->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small text-muted">Status</label>
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="connected" {{ request('status') == 'connected' ? 'selected' : '' }}>Connected</option>
                        <option value="disconnected" {{ request('status') == 'disconnected' ? 'selected' : '' }}>Disconnected</option>
                        <option value="connecting" {{ request('status') == 'connecting' ? 'selected' : '' }}>Connecting</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex align-items-end gap-2">
                    <button type="submit" class="btn btn-primary flex-fill">
                        <i class="bi bi-search"></i> Filter
                    </button>
                    <a href="{{ route('admin.accounts.index') }}" class="btn btn-outline-secondary">
                        <i class="bi bi-x-circle"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Accounts Table -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            @if($accounts->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="px-4">Account</th>
                                <th>User</th>
                                <th>Phone Number</th>
                                <th>Status</th>
                                <th>Connected At</th>
                                <th class="text-end px-4">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($accounts as $account)
                            <tr>
                                <td class="px-4">
                                    <div class="d-flex align-items-center">
                                        <div class="bg-{{ $account->status == 'connected' ? 'success' : 'secondary' }} rounded-circle me-2" 
                                             style="width: 8px; height: 8px;"></div>
                                        <div>
                                            <div class="fw-semibold">{{ $account->name }}</div>
                                            <small class="text-muted">{{ $account->session_id }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div>{{ $account->user->name }}</div>
                                    <small class="text-muted">{{ $account->user->email }}</small>
                                </td>
                                <td>
                                    <span class="badge bg-light text-dark border">
                                        {{ $account->phone_number ?? '-' }}
                                    </span>
                                </td>
                                <td>
                                    <span class="badge bg-{{ $account->status == 'connected' ? 'success' : ($account->status == 'connecting' ? 'warning' : 'secondary') }}">
                                        {{ ucfirst($account->status) }}
                                    </span>
                                </td>
                                <td>
                                    @if($account->connected_at)
                                        <small>{{ $account->connected_at->format('d M Y H:i') }}</small>
                                    @else
                                        <small class="text-muted">Never</small>
                                    @endif
                                </td>
                                <td class="text-end px-4">
                                    <div class="dropdown">
                                        <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                                            <i class="bi bi-three-dots-vertical"></i>
                                        </button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li>
                                                <a class="dropdown-item" href="{{ route('admin.accounts.show', $account) }}">
                                                    <i class="bi bi-eye me-2"></i>View Details
                                                </a>
                                            </li>
                                            @if($account->status == 'connected')
                                            <li>
                                                <form action="{{ route('admin.accounts.force-disconnect', $account) }}" method="POST">
                                                    @csrf
                                                    <button type="submit" class="dropdown-item text-warning" onclick="return confirm('Force disconnect this account?')">
                                                        <i class="bi bi-plug me-2"></i>Force Disconnect
                                                    </button>
                                                </form>
                                            </li>
                                            @endif
                                            <li><hr class="dropdown-divider"></li>
                                            <li>
                                                <button type="button" class="dropdown-item text-danger" onclick="confirmDelete({{ $account->id }})">
                                                    <i class="bi bi-trash me-2"></i>Delete
                                                </button>
                                            </li>
                                        </ul>
                                    </div>
                                </td>
                            </tr>

                            <!-- Delete Form (Hidden) -->
                            <form id="delete-form-{{ $account->id }}" action="{{ route('admin.accounts.destroy', $account) }}" method="POST" class="d-none">
                                @csrf
                                @method('DELETE')
                            </form>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div class="card-footer bg-white border-top">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            Showing {{ $accounts->firstItem() }} to {{ $accounts->lastItem() }} of {{ $accounts->total() }} accounts
                        </div>
                        <div>
                            {{ $accounts->withQueryString()->links() }}
                        </div>
                    </div>
                </div>
            @else
                <div class="text-center py-5">
                    <i class="bi bi-phone text-muted" style="font-size: 4rem;"></i>
                    <h4 class="mt-3">No Accounts Found</h4>
                    <p class="text-muted">No WhatsApp accounts registered yet</p>
                </div>
            @endif
        </div>
    </div>
</div>

<script>
function confirmDelete(accountId) {
    if (confirm('Are you sure you want to delete this account? This action cannot be undone.')) {
        document.getElementById('delete-form-' + accountId).submit();
    }
}
</script>
@endsection