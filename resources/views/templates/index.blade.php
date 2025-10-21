@extends('layouts.app')

@section('title', 'Message Templates')
@section('page-title', 'Message Templates')

@section('content')
<div class="container py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Message Templates</h2>
            <p class="text-muted mb-0">Create and manage message templates with variables</p>
        </div>
        <a href="{{ route('templates.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Create Template
        </a>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if(session('error'))
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            {{ session('error') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Stats -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body">
                    <h3 class="mb-0">{{ $stats['total'] }}</h3>
                    <p class="text-muted mb-0">Total Templates</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body">
                    <h3 class="mb-0">{{ $stats['active'] }}</h3>
                    <p class="text-muted mb-0">Active</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body">
                    <h3 class="mb-0">{{ $stats['marketing'] }}</h3>
                    <p class="text-muted mb-0">Marketing</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center border-0 shadow-sm">
                <div class="card-body">
                    <h3 class="mb-0">{{ $stats['notification'] }}</h3>
                    <p class="text-muted mb-0">Notification</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4 border-0 shadow-sm">
        <div class="card-body">
            <form action="{{ route('templates.index') }}" method="GET" class="row g-3">
                <div class="col-md-4">
                    <input type="text" 
                           name="search" 
                           class="form-control" 
                           placeholder="Search templates..." 
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="category" class="form-select">
                        <option value="">All Categories</option>
                        <option value="marketing" {{ request('category') == 'marketing' ? 'selected' : '' }}>Marketing</option>
                        <option value="notification" {{ request('category') == 'notification' ? 'selected' : '' }}>Notification</option>
                        <option value="reminder" {{ request('category') == 'reminder' ? 'selected' : '' }}>Reminder</option>
                        <option value="greeting" {{ request('category') == 'greeting' ? 'selected' : '' }}>Greeting</option>
                        <option value="other" {{ request('category') == 'other' ? 'selected' : '' }}>Other</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="status" class="form-select">
                        <option value="">All Status</option>
                        <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Active</option>
                        <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactive</option>
                    </select>
                </div>
                <div class="col-md-3">
                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-primary">
                            <i class="bi bi-search"></i> Filter
                        </button>
                        <a href="{{ route('templates.index') }}" class="btn btn-secondary">
                            <i class="bi bi-x-circle"></i> Clear
                        </a>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Templates Grid -->
    @if($templates->isEmpty())
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No templates found. 
            <a href="{{ route('templates.create') }}">Create your first template</a> to get started!
        </div>
    @else
        <div class="row">
            @foreach($templates as $template)
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 border-0 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <div class="flex-grow-1">
                                    <h5 class="card-title mb-1">{{ $template->name }}</h5>
                                    <span class="badge bg-{{ $template->category_color }}">
                                        {{ $template->category_label }}
                                    </span>
                                    @if($template->hasMedia())
                                        <span class="badge bg-info ms-1">
                                            <i class="bi bi-paperclip"></i> Media
                                        </span>
                                    @endif
                                </div>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-light" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('templates.show', $template) }}">
                                                <i class="bi bi-eye"></i> View
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('templates.edit', $template) }}">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                        </li>
                                        <li>
                                            <form action="{{ route('templates.duplicate', $template) }}" method="POST">
                                                @csrf
                                                <button type="submit" class="dropdown-item">
                                                    <i class="bi bi-files"></i> Duplicate
                                                </button>
                                            </form>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('templates.destroy', $template) }}" 
                                                  method="POST" 
                                                  onsubmit="return confirm('Delete this template?')">
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

                            <p class="card-text text-muted small" style="max-height: 60px; overflow: hidden;">
                                {{ Str::limit($template->content, 100) }}
                            </p>

                            @if($template->variables && count($template->variables) > 0)
                                <div class="mb-3">
                                    <small class="text-muted">Variables:</small><br>
                                    @foreach($template->variables as $var)
                                        <span class="badge bg-light text-dark border me-1">{!! '{'.$var.'}' !!}</span>
                                    @endforeach
                                </div>
                            @endif

                            <div class="d-flex justify-content-between align-items-center pt-3 border-top">
                                <div>
                                    <small class="text-muted">
                                        <i class="bi bi-send"></i> Used {{ $template->usage_count }}x
                                    </small>
                                </div>
                                <div class="form-check form-switch">
                                    <input class="form-check-input" 
                                           type="checkbox" 
                                           {{ $template->is_active ? 'checked' : '' }}
                                           onchange="toggleStatus({{ $template->id }}, this)">
                                    <label class="form-check-label small">
                                        {{ $template->is_active ? 'Active' : 'Inactive' }}
                                    </label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-4">
            {{ $templates->links() }}
        </div>
    @endif
</div>

<script>
function toggleStatus(templateId, checkbox) {
    fetch(`/templates/${templateId}/toggle-status`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        if (data.success) {
            const label = checkbox.nextElementSibling;
            label.textContent = data.is_active ? 'Active' : 'Inactive';
        } else {
            checkbox.checked = !checkbox.checked;
            alert('Failed to update status');
        }
    })
    .catch(error => {
        checkbox.checked = !checkbox.checked;
        alert('Error: ' + error.message);
    });
}
</script>
@endsection