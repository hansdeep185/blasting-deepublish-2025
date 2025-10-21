@extends('layouts.app')

@section('title', 'Template Details')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div class="d-flex align-items-center">
            <a href="{{ route('templates.index') }}" class="btn btn-outline-secondary me-3">
                <i class="bi bi-arrow-left"></i>
            </a>
            <div>
                <h2 class="mb-1">{{ $template->name }}</h2>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <span class="badge bg-{{ $template->category_color }}-subtle text-{{ $template->category_color }} border border-{{ $template->category_color }}">
                        {{ $template->category_label }}
                    </span>
                    <span class="badge {{ $template->is_active ? 'bg-success' : 'bg-secondary' }}">
                        {{ $template->is_active ? 'Active' : 'Inactive' }}
                    </span>
                    @if($template->hasMedia())
                        <span class="badge bg-info-subtle text-info border border-info">
                            <i class="bi bi-paperclip"></i> Has Media
                        </span>
                    @endif
                </div>
            </div>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('templates.edit', $template) }}" class="btn btn-primary">
                <i class="bi bi-pencil me-2"></i>Edit
            </a>
            <div class="dropdown">
                <button class="btn btn-outline-secondary dropdown-toggle" type="button" data-bs-toggle="dropdown">
                    <i class="bi bi-three-dots"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end">
                    <li>
                        <form action="{{ route('templates.duplicate', $template) }}" method="POST">
                            @csrf
                            <button type="submit" class="dropdown-item">
                                <i class="bi bi-files me-2"></i>Duplicate
                            </button>
                        </form>
                    </li>
                    <li><hr class="dropdown-divider"></li>
                    <li>
                        <button type="button" class="dropdown-item text-danger" onclick="confirmDelete()">
                            <i class="bi bi-trash me-2"></i>Delete
                        </button>
                    </li>
                </ul>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="bi bi-check-circle me-2"></i>{{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <div class="row g-4">
        <!-- Left Column: Template Info -->
        <div class="col-lg-7">
            <!-- Basic Information -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-4">Template Information</h5>

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="small text-muted mb-1">Template Name</label>
                            <p class="mb-0 fw-semibold">{{ $template->name }}</p>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1">Category</label>
                            <p class="mb-0">{{ $template->category_label }}</p>
                        </div>
                        @if($template->description)
                        <div class="col-12">
                            <label class="small text-muted mb-1">Description</label>
                            <p class="mb-0">{{ $template->description }}</p>
                        </div>
                        @endif
                        <div class="col-md-6">
                            <label class="small text-muted mb-1">Status</label>
                            <p class="mb-0">
                                <span class="badge {{ $template->is_active ? 'bg-success' : 'bg-secondary' }}">
                                    {{ $template->is_active ? 'Active' : 'Inactive' }}
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1">Created</label>
                            <p class="mb-0">{{ $template->created_at->format('d M Y H:i') }}</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Message Content -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-3">Message Content</h5>
                    <div class="bg-light rounded p-3" style="white-space: pre-wrap;">{{ $template->content }}</div>
                    <small class="text-muted mt-2 d-block">
                        <i class="bi bi-text-paragraph me-1"></i>{{ strlen($template->content) }} characters
                    </small>
                </div>
            </div>

            <!-- Variables Used -->
            @if(count($template->variables) > 0)
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-3">Variables Used</h5>
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Variable</th>
                                    <th>Description</th>
                                    <th>Sample Value</th>
                                </tr>
                            </thead>
                            <tbody>
                                @foreach($template->variables as $variable)
                                <tr>
                                    <td><code>{{'{'.$variable.'}'}}</code></td>
                                    <td>{{ $availableVariables[$variable] ?? 'Custom Variable' }}</td>
                                    <td class="text-muted">{{ $sampleData[$variable] ?? '-' }}</td>
                                </tr>
                                @endforeach
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            @endif

            <!-- Media Attachment -->
            @if($template->hasMedia())
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-3">Media Attachment</h5>
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="small text-muted mb-1">Media Type</label>
                            <p class="mb-0">
                                <span class="badge bg-info">
                                    <i class="bi bi-{{ $template->media_type == 'image' ? 'image' : ($template->media_type == 'video' ? 'camera-video' : 'file-earmark') }}"></i>
                                    {{ ucfirst($template->media_type) }}
                                </span>
                            </p>
                        </div>
                        <div class="col-md-6">
                            <label class="small text-muted mb-1">Media URL</label>
                            <p class="mb-0">
                                <a href="{{ $template->media_url }}" target="_blank" class="text-decoration-none">
                                    <i class="bi bi-link-45deg"></i> View Media
                                </a>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            @endif

            <!-- Usage Statistics -->
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="mb-3">Usage Statistics</h5>
                    <div class="row g-3">
                        <div class="col-md-4">
                            <div class="text-center p-3 bg-light rounded">
                                <h3 class="mb-0 text-primary">{{ $template->usage_count }}</h3>
                                <small class="text-muted">Times Used</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3 bg-light rounded">
                                <h3 class="mb-0 text-success">{{ $template->blastSchedules->count() }}</h3>
                                <small class="text-muted">Campaigns</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="text-center p-3 bg-light rounded">
                                @if($template->last_used_at)
                                    <div class="small text-muted mb-1">Last Used</div>
                                    <small class="fw-semibold">{{ $template->last_used_at->diffForHumans() }}</small>
                                @else
                                    <div class="small text-muted">Never Used</div>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Blast Campaigns Using This Template -->
            @if($template->blastSchedules->count() > 0)
            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h5 class="mb-3">Campaigns Using This Template</h5>
                    <div class="list-group list-group-flush">
                        @foreach($template->blastSchedules->take(5) as $blast)
                        <div class="list-group-item px-0">
                            <div class="d-flex justify-content-between align-items-center">
                                <div>
                                    <h6 class="mb-1">{{ $blast->name }}</h6>
                                    <small class="text-muted">
                                        <i class="bi bi-calendar me-1"></i>{{ $blast->scheduled_at->format('d M Y H:i') }}
                                    </small>
                                </div>
                                <span class="badge bg-{{ $blast->status == 'completed' ? 'success' : ($blast->status == 'processing' ? 'primary' : 'secondary') }}">
                                    {{ ucfirst($blast->status) }}
                                </span>
                            </div>
                        </div>
                        @endforeach
                    </div>
                    @if($template->blastSchedules->count() > 5)
                        <div class="text-center mt-3">
                            <a href="{{ route('blasts.index', ['template' => $template->id]) }}" class="btn btn-sm btn-outline-primary">
                                View All Campaigns
                            </a>
                        </div>
                    @endif
                </div>
            </div>
            @endif
        </div>

        <!-- Right Column: Preview -->
        <div class="col-lg-5">
            <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                <div class="card-body p-4">
                    <h5 class="mb-3">
                        <i class="bi bi-eye me-2"></i>Message Preview
                    </h5>

                    <!-- WhatsApp-like Preview -->
                    <div class="border rounded p-3 bg-white mb-3">
                        <div class="d-flex align-items-center mb-3 pb-3 border-bottom">
                            <div class="bg-success rounded-circle d-flex align-items-center justify-content-center me-2" 
                                 style="width: 40px; height: 40px;">
                                <i class="bi bi-whatsapp text-white fs-5"></i>
                            </div>
                            <div>
                                <strong>Your Business</strong>
                                <small class="d-block text-muted">WhatsApp Message</small>
                            </div>
                        </div>

                        <!-- Media Preview -->
                        @if($template->hasMedia())
                        <div class="mb-3">
                            <div class="bg-light rounded p-3 text-center">
                                <i class="bi bi-{{ $template->media_type == 'image' ? 'image' : ($template->media_type == 'video' ? 'camera-video' : 'file-earmark') }} text-muted fs-1"></i>
                                <p class="mb-0 small text-muted mt-2">{{ ucfirst($template->media_type) }} Attachment</p>
                            </div>
                        </div>
                        @endif

                        <!-- Message Preview -->
                        <div class="bg-light rounded p-3">
                            <div class="text-break" style="white-space: pre-wrap;">
                                {!! $preview !!}
                            </div>
                            <small class="text-muted d-block mt-2">
                                <i class="bi bi-clock me-1"></i>{{ now()->format('H:i') }}
                            </small>
                        </div>

                        <!-- Sample Data Info -->
                        <div class="mt-3 p-2 bg-info-subtle rounded">
                            <small class="text-muted">
                                <i class="bi bi-info-circle me-1"></i>
                                Preview uses sample data shown below
                            </small>
                        </div>
                    </div>

                    <!-- Custom Preview Form -->
                    <div class="card bg-light border-0">
                        <div class="card-body">
                            <h6 class="mb-3">Test with Custom Data</h6>
                            <form id="previewForm">
                                @foreach($template->variables as $variable)
                                <div class="mb-3">
                                    <label class="form-label small">{{ $availableVariables[$variable] ?? ucwords(str_replace('_', ' ', $variable)) }}</label>
                                    <input type="text" class="form-control form-control-sm preview-input" 
                                           data-variable="{{ $variable }}" 
                                           value="{{ $sampleData[$variable] ?? 'Sample Value' }}" 
                                           placeholder="{{ $variable }}">
                                </div>
                                @endforeach
                                <button type="button" class="btn btn-sm btn-primary w-100" id="updatePreview">
                                    <i class="bi bi-arrow-clockwise me-1"></i>Update Preview
                                </button>
                            </form>
                        </div>
                    </div>

                    <!-- Quick Actions -->
                    <div class="d-grid gap-2 mt-3">
                        <a href="{{ route('blasts.create', ['template' => $template->id]) }}" class="btn btn-success">
                            <i class="bi bi-send me-2"></i>Create Blast Campaign
                        </a>
                        <button type="button" class="btn btn-outline-primary" onclick="copyContent()">
                            <i class="bi bi-clipboard me-2"></i>Copy Content
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Form (Hidden) -->
<form id="delete-form" action="{{ route('templates.destroy', $template) }}" method="POST" class="d-none">
    @csrf
    @method('DELETE')
</form>

<script>
// Update preview with custom data
document.getElementById('updatePreview')?.addEventListener('click', function() {
    const data = {};
    document.querySelectorAll('.preview-input').forEach(input => {
        data[input.dataset.variable] = input.value;
    });

    fetch('{{ route("templates.preview", $template) }}', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        },
        body: JSON.stringify(data)
    })
    .then(response => response.json())
    .then(result => {
        if (result.success) {
            // Update preview in the message bubble
            const previewDiv = document.querySelector('.text-break');
            previewDiv.innerHTML = result.preview;
        }
    });
});

// Copy content to clipboard
function copyContent() {
    const content = `{{ str_replace(["\r\n", "\n", "\r"], "\\n", addslashes($template->content)) }}`;
    navigator.clipboard.writeText(content).then(() => {
        alert('Template content copied to clipboard!');
    });
}

// Delete confirmation
function confirmDelete() {
    if (confirm('Are you sure you want to delete this template? This action cannot be undone.')) {
        document.getElementById('delete-form').submit();
    }
}
</script>

<style>
.sticky-top {
    position: sticky;
}
</style>
@endsection