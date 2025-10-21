@extends('layouts.app')

@section('title', 'Edit Template')

@section('content')
<div class="container-fluid py-4">
    <!-- Header -->
    <div class="d-flex align-items-center mb-4">
        <a href="{{ route('templates.show', $template) }}" class="btn btn-outline-secondary me-3">
            <i class="bi bi-arrow-left"></i>
        </a>
        <div>
            <h2 class="mb-1">Edit Template</h2>
            <p class="text-muted mb-0">{{ $template->name }}</p>
        </div>
    </div>

    <form action="{{ route('templates.update', $template) }}" method="POST" id="templateForm">
        @csrf
        @method('PUT')
        
        <div class="row g-4">
            <!-- Left Column: Form -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm">
                    <div class="card-body p-4">
                        <h5 class="mb-4">Template Information</h5>

                        <!-- Template Name -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Template Name <span class="text-danger">*</span></label>
                            <input type="text" name="name" class="form-control @error('name') is-invalid @enderror" 
                                   placeholder="e.g., Welcome Message" value="{{ old('name', $template->name) }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Category -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Category <span class="text-danger">*</span></label>
                            <select name="category" class="form-select @error('category') is-invalid @enderror" required>
                                <option value="">Select Category</option>
                                <option value="marketing" {{ old('category', $template->category) == 'marketing' ? 'selected' : '' }}>Marketing</option>
                                <option value="notification" {{ old('category', $template->category) == 'notification' ? 'selected' : '' }}>Notification</option>
                                <option value="reminder" {{ old('category', $template->category) == 'reminder' ? 'selected' : '' }}>Reminder</option>
                                <option value="greeting" {{ old('category', $template->category) == 'greeting' ? 'selected' : '' }}>Greeting</option>
                                <option value="other" {{ old('category', $template->category) == 'other' ? 'selected' : '' }}>Other</option>
                            </select>
                            @error('category')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Description -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Description</label>
                            <textarea name="description" class="form-control @error('description') is-invalid @enderror" 
                                      rows="2" placeholder="Brief description of this template...">{{ old('description', $template->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <!-- Message Content -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Message Content <span class="text-danger">*</span></label>
                            <textarea name="content" id="templateContent" class="form-control @error('content') is-invalid @enderror" 
                                      rows="8" placeholder="Type your message here... Use {variable_name} for dynamic content" required>{{ old('content', $template->content) }}</textarea>
                            @error('content')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Character count: <span id="charCount">0</span></small>
                        </div>

                        <!-- Available Variables -->
                        <div class="mb-4">
                            <label class="form-label fw-semibold">Available Variables</label>
                            <div class="border rounded p-3 bg-light">
                                <small class="text-muted d-block mb-2">Click to insert into message:</small>
                                <div class="d-flex flex-wrap gap-2">
                                    @foreach($availableVariables as $key => $label)
                                        <button type="button" class="btn btn-sm btn-outline-primary insert-variable" 
                                                data-variable="{{ $key }}">
                                            {{'{'.$key.'}'}}
                                        </button>
                                    @endforeach
                                </div>
                            </div>
                        </div>

                        <!-- Media Attachment -->
                        <div class="card bg-light border-0 mb-4">
                            <div class="card-body">
                                <h6 class="mb-3">Media Attachment (Optional)</h6>
                                
                                <div class="mb-3">
                                    <label class="form-label fw-semibold">Media Type</label>
                                    <select name="media_type" id="mediaType" class="form-select">
                                        <option value="">No Media</option>
                                        <option value="image" {{ old('media_type', $template->media_type) == 'image' ? 'selected' : '' }}>Image</option>
                                        <option value="document" {{ old('media_type', $template->media_type) == 'document' ? 'selected' : '' }}>Document</option>
                                        <option value="video" {{ old('media_type', $template->media_type) == 'video' ? 'selected' : '' }}>Video</option>
                                    </select>
                                </div>

                                <div class="mb-0" id="mediaUrlGroup" style="display: none;">
                                    <label class="form-label fw-semibold">Media URL</label>
                                    <input type="url" name="media_url" class="form-control" 
                                           placeholder="https://example.com/image.jpg" value="{{ old('media_url', $template->media_url) }}">
                                    <small class="text-muted">Enter the full URL to your media file</small>
                                </div>
                            </div>
                        </div>

                        <!-- Status -->
                        <div class="mb-4">
                            <div class="form-check form-switch">
                                <input class="form-check-input" type="checkbox" name="is_active" id="isActive" 
                                       value="1" {{ old('is_active', $template->is_active) ? 'checked' : '' }}>
                                <label class="form-check-label" for="isActive">
                                    Template Active
                                </label>
                            </div>
                            <small class="text-muted">Inactive templates cannot be used in blast campaigns</small>
                        </div>

                        <!-- Action Buttons -->
                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-check-circle me-2"></i>Update Template
                            </button>
                            <a href="{{ route('templates.show', $template) }}" class="btn btn-outline-secondary">
                                Cancel
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Column: Preview -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-body p-4">
                        <h5 class="mb-3">
                            <i class="bi bi-eye me-2"></i>Live Preview
                        </h5>

                        <!-- WhatsApp-like Preview -->
                        <div class="border rounded p-3 bg-white" style="min-height: 300px;">
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
                            <div id="mediaPreview" style="display: none;" class="mb-3">
                                <div class="bg-light rounded p-3 text-center">
                                    <i class="bi bi-file-earmark text-muted fs-1"></i>
                                    <p class="mb-0 small text-muted mt-2">Media Attachment</p>
                                </div>
                            </div>

                            <!-- Message Preview -->
                            <div class="bg-light rounded p-3">
                                <div id="messagePreview" class="text-break" style="white-space: pre-wrap; min-height: 100px;">
                                    <span class="text-muted">Your message preview will appear here...</span>
                                </div>
                                <small class="text-muted d-block mt-2">
                                    <i class="bi bi-clock me-1"></i><span id="timePreview">{{ now()->format('H:i') }}</span>
                                </small>
                            </div>

                            <!-- Sample Data Info -->
                            <div class="mt-3 p-2 bg-info-subtle rounded">
                                <small class="text-muted">
                                    <i class="bi bi-info-circle me-1"></i>
                                    Preview uses sample data
                                </small>
                            </div>
                        </div>

                        <!-- Detected Variables -->
                        <div class="mt-3">
                            <label class="form-label fw-semibold small">Detected Variables:</label>
                            <div id="detectedVariables" class="d-flex flex-wrap gap-1">
                                <span class="badge bg-secondary-subtle text-secondary">None</span>
                            </div>
                        </div>

                        <!-- Usage Stats -->
                        <div class="mt-3 p-3 bg-light rounded">
                            <small class="text-muted d-block mb-2">Template Stats:</small>
                            <div class="d-flex justify-content-between">
                                <span><i class="bi bi-graph-up me-1"></i>Used {{ $template->usage_count }} times</span>
                                @if($template->last_used_at)
                                    <span><i class="bi bi-clock me-1"></i>{{ $template->last_used_at->diffForHumans() }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
// Sample data for preview
const sampleData = {
    name: 'John Doe',
    phone: '08123456789',
    email: 'john@example.com',
    company: 'PT Example',
    first_name: 'John',
    last_name: 'Doe',
    date: '{{ now()->format("d M Y") }}',
    time: '{{ now()->format("H:i") }}',
    custom_field_1: 'Custom Value 1',
    custom_field_2: 'Custom Value 2'
};

// Update preview on content change
const contentInput = document.getElementById('templateContent');
const previewDiv = document.getElementById('messagePreview');
const charCount = document.getElementById('charCount');
const detectedVarsDiv = document.getElementById('detectedVariables');

function updatePreview() {
    let content = contentInput.value;
    
    // Update character count
    charCount.textContent = content.length;
    
    // Replace variables with sample data
    let preview = content;
    const variables = [];
    
    // Extract variables
    const regex = /{([^}]+)}/g;
    let match;
    while ((match = regex.exec(content)) !== null) {
        variables.push(match[1]);
        if (sampleData[match[1]]) {
            preview = preview.replace(match[0], `<strong class="text-primary">${sampleData[match[1]]}</strong>`);
        }
    }
    
    // Update preview
    if (content.trim() === '') {
        previewDiv.innerHTML = '<span class="text-muted">Your message preview will appear here...</span>';
    } else {
        previewDiv.innerHTML = preview;
    }
    
    // Update detected variables
    if (variables.length > 0) {
        const uniqueVars = [...new Set(variables)];
        detectedVarsDiv.innerHTML = uniqueVars.map(v => 
            `<span class="badge bg-primary-subtle text-primary border border-primary">{${v}}</span>`
        ).join(' ');
    } else {
        detectedVarsDiv.innerHTML = '<span class="badge bg-secondary-subtle text-secondary">None</span>';
    }
}

contentInput.addEventListener('input', updatePreview);

// Insert variable on click
document.querySelectorAll('.insert-variable').forEach(btn => {
    btn.addEventListener('click', function() {
        const variable = this.dataset.variable;
        const cursorPos = contentInput.selectionStart;
        const textBefore = contentInput.value.substring(0, cursorPos);
        const textAfter = contentInput.value.substring(cursorPos);
        
        contentInput.value = textBefore + `{${variable}}` + textAfter;
        contentInput.focus();
        contentInput.setSelectionRange(cursorPos + variable.length + 2, cursorPos + variable.length + 2);
        
        updatePreview();
    });
});

// Media type change
const mediaType = document.getElementById('mediaType');
const mediaUrlGroup = document.getElementById('mediaUrlGroup');
const mediaPreview = document.getElementById('mediaPreview');

mediaType.addEventListener('change', function() {
    if (this.value) {
        mediaUrlGroup.style.display = 'block';
        mediaPreview.style.display = 'block';
    } else {
        mediaUrlGroup.style.display = 'none';
        mediaPreview.style.display = 'none';
    }
});

// Initial preview
updatePreview();

// Show media URL field if media type is selected on page load
if (mediaType.value) {
    mediaUrlGroup.style.display = 'block';
    mediaPreview.style.display = 'block';
}
</script>

<style>
.sticky-top {
    position: sticky;
}
</style>
@endsection