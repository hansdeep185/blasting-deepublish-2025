@extends('layouts.app')

@section('title', 'Create Blast Campaign')
@section('page-title', 'Create Campaign')

@section('content')
<div class="container-fluid">
    <form action="{{ route('blasts.store') }}" method="POST">
        @csrf
        <div class="row g-4">
            <div class="col-lg-8">
                
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Campaign Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="name" class="form-label">Campaign Name <span class="text-danger">*</span></label>
                            <input type="text" class="form-control @error('name') is-invalid @enderror" 
                                   id="name" name="name" value="{{ old('name') }}" required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="description" class="form-label">Description (Optional)</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" name="description" rows="3">{{ old('description') }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Source & Template</h5>
                    </div>
                    <div class="card-body">
                        <div class="mb-3">
                            <label for="account_id" class="form-label">WhatsApp Account <span class="text-danger">*</span></label>
                            <select class="form-select @error('account_id') is-invalid @enderror" 
                                    id="account_id" name="account_id" required>
                                <option value="">Select Account...</option>
                                @foreach($accounts as $account)
                                    <option value="{{ $account->id }}" {{ old('account_id') == $account->id ? 'selected' : '' }}>
                                        {{ $account->name }} ({{ $account->phone_number }})
                                    </option>
                                @endforeach
                            </select>
                            @error('account_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                        <div class="mb-3">
                            <label for="template_id" class="form-label">Message Template <span class="text-danger">*</span></label>
                            <select class="form-select @error('template_id') is-invalid @enderror" 
                                    id="template_id" name="template_id" required>
                                <option value="">Select Template...</option>
                                @foreach($templates as $template)
                                    <option value="{{ $template->id }}" {{ old('template_id') == $template->id ? 'selected' : '' }}>
                                        {{ $template->name }}
                                    </option>
                                @endforeach
                            </select>
                            @error('template_id')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Target Recipients</h5>
                    </div>
                    <div class="card-body">
                        @error('target_type')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror
                        @error('target_ids')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror

                        <div class="list-group list-group-flush">
                            <label class="list-group-item list-group-item-action">
                                <input class="form-check-input me-2" type="radio" name="target_type" value="all" 
                                       id="target_all" {{ old('target_type', 'all') == 'all' ? 'checked' : '' }}>
                                <strong>All Contacts</strong>
                                <small class="d-block text-muted">Send to all active contacts associated with your account.</small>
                            </label>
                            
                            <label class="list-group-item list-group-item-action">
                                <input class="form-check-input me-2" type="radio" name="target_type" value="contact_list" 
                                       id="target_list" {{ old('target_type') == 'contact_list' ? 'checked' : '' }}>
                                <strong>By Contact List</strong>
                                <small class="d-block text-muted">Send to one or more specific contact lists.</small>
                                <div id="contact_list_div" class="mt-3" style="display: none;">
                                    <label class="form-label small">Select Contact Lists</label>
                                    <select class="form-select" name="target_ids[]" multiple size="5">
                                        @foreach($contactLists as $list)
                                            <option value="{{ $list->id }}" {{ in_array($list->id, old('target_ids', [])) ? 'selected' : '' }}>
                                                {{ $list->name }} ({{ $list->contacts_count }} contacts)
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </label>

                            <label class="list-group-item list-group-item-action">
                                <input class="form-check-input me-2" type="radio" name="target_type" value="contact_group" 
                                       id="target_group" {{ old('target_type') == 'contact_group' ? 'checked' : '' }}>
                                <strong>By Contact Group (Tags)</strong>
                                <small class="d-block text-muted">Send to contacts with specific tags.</small>
                                <div id="contact_group_div" class="mt-3" style="display: none;">
                                    <label class="form-label small">Select Groups (Tags)</label>
                                    <select class="form-select" name="target_ids[]" multiple size="5">
                                        @foreach($contactTags as $tag)
                                            <option value="{{ $tag->id }}" {{ in_array($tag->id, old('target_ids', [])) ? 'selected' : '' }}>
                                                {{ $tag->name }} ({{ $tag->contacts_count }} contacts)
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </label>
                        </div>
                    </div>
                </div>

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">Scheduling</h5>
                    </div>
                    <div class="card-body">
                        @error('schedule_type')
                            <div class="alert alert-danger">{{ $message }}</div>
                        @enderror

                        <div class="form-check mb-3">
                            <input class="form-check-input" type="radio" name="schedule_type" id="schedule_immediate" 
                                   value="immediate" {{ old('schedule_type', 'immediate') == 'immediate' ? 'checked' : '' }}>
                            <label class="form-check-label" for="schedule_immediate">
                                <strong>Send Immediately</strong>
                                <small class="d-block text-muted">The campaign will be queued for processing right away.</small>
                            </label>
                        </div>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="schedule_type" id="schedule_scheduled" 
                                   value="scheduled" {{ old('schedule_type') == 'scheduled' ? 'checked' : '' }}>
                            <label class="form-check-label" for="schedule_scheduled">
                                <strong>Schedule for Later</strong>
                                <small class="d-block text-muted">Select a specific date and time to send the campaign.</small>
                            </label>
                        </div>
                        
                        <div id="scheduled_at_div" class="mt-3" style="display: none;">
                            <label for="scheduled_at" class="form-label">Schedule Date & Time</label>
                            <input type="datetime-local" class="form-control @error('scheduled_at') is-invalid @enderror" 
                                   id="scheduled_at" name="scheduled_at" value="{{ old('scheduled_at') }}">
                            @error('scheduled_at')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>
                </div>

                <div class="d-flex justify-content-end">
                    <a href="{{ route('blasts.index') }}" class="btn btn-outline-secondary me-2">Cancel</a>
                    <button type="submit" class="btn btn-primary">
                        <i class="bi bi-send me-2"></i>Create Campaign
                    </button>
                </div>
            </div>

            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-header bg-white py-3">
                        <h5 class="mb-0">
                            <i class="bi bi-display me-2"></i>Template Preview
                        </h5>
                    </div>
                    <div class="card-body">
                        <p class="text-muted small">
                            Select a template to see a preview. The preview will use sample data.
                        </p>
                        <div id="template-preview-content" class="bg-light p-3 rounded border" style="min-height: 200px; white-space: pre-wrap; font-size: 0.9rem;">
                            Select a template...
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>
@endsection

@push('scripts')
<script>
    document.addEventListener('DOMContentLoaded', function () {
        // --- Logic untuk Target Recipients ---
        const targetRadios = document.querySelectorAll('input[name="target_type"]');
        const listDiv = document.getElementById('contact_list_div');
        const groupDiv = document.getElementById('contact_group_div');
        
        function toggleTargetDivs() {
            const selected = document.querySelector('input[name="target_type"]:checked').value;
            if (selected === 'contact_list') {
                listDiv.style.display = 'block';
                groupDiv.style.display = 'none';
                // Aktifkan select list, non-aktifkan select group
                listDiv.querySelector('select').disabled = false;
                groupDiv.querySelector('select').disabled = true;
            } else if (selected === 'contact_group') {
                listDiv.style.display = 'none';
                groupDiv.style.display = 'block';
                // Non-aktifkan select list, aktifkan select group
                listDiv.querySelector('select').disabled = true;
                groupDiv.querySelector('select').disabled = false;
            } else { // 'all'
                listDiv.style.display = 'none';
                groupDiv.style.display = 'none';
                // Non-aktifkan keduanya
                listDiv.querySelector('select').disabled = true;
                groupDiv.querySelector('select').disabled = true;
            }
        }
        
        targetRadios.forEach(radio => radio.addEventListener('change', toggleTargetDivs));
        toggleTargetDivs(); // Jalankan saat load

        // --- Logic untuk Scheduling ---
        const scheduleRadios = document.querySelectorAll('input[name="schedule_type"]');
        const scheduleDiv = document.getElementById('scheduled_at_div');

        function toggleScheduleDiv() {
            const selected = document.querySelector('input[name="schedule_type"]:checked').value;
            if (selected === 'scheduled') {
                scheduleDiv.style.display = 'block';
                scheduleDiv.querySelector('input').disabled = false;
            } else {
                scheduleDiv.style.display = 'none';
                scheduleDiv.querySelector('input').disabled = true;
            }
        }
        
        scheduleRadios.forEach(radio => radio.addEventListener('change', toggleScheduleDiv));
        toggleScheduleDiv(); // Jalankan saat load

        // --- Logic untuk Template Preview (AJAX) ---
        const templateSelect = document.getElementById('template_id');
        const previewContent = document.getElementById('template-preview-content');
        const previewUrl = "{{ route('blasts.preview') }}";
        const csrfToken = document.querySelector('meta[name="csrf-token"]').getAttribute('content'); // Asumsi ada meta tag CSRF di layout

        templateSelect.addEventListener('change', function() {
            const templateId = this.value;
            
            if (!templateId) {
                previewContent.textContent = 'Select a template...';
                return;
            }

            previewContent.textContent = 'Loading preview...';

            fetch(previewUrl, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-CSRF-TOKEN': csrfToken,
                    'Accept': 'application/json'
                },
                body: JSON.stringify({
                    template_id: templateId
                    // contact_id bisa ditambahkan di sini jika ada selector kontak
                })
            })
            .then(response => response.json())
            .then(data => {
                if(data.success && data.preview) {
                    previewContent.textContent = data.preview;
                } else {
                    previewContent.textContent = 'Failed to load preview.';
                }
            })
            .catch(error => {
                console.error('Error fetching preview:', error);
                previewContent.textContent = 'Error loading preview.';
            });
        });

        // Trigger preview jika ada old value saat load
        if (templateSelect.value) {
            templateSelect.dispatchEvent(new Event('change'));
        }
    });
</script>
@endpush