@extends('layouts.app')

@section('title', 'System Settings')
@section('page-title', 'System Settings')

@section('content')
<div class="row">
    <div class="col-12">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <ul class="nav nav-tabs card-header-tabs" role="tablist">
                    @foreach($groups as $key => $label)
                    <li class="nav-item">
                        <a class="nav-link {{ $loop->first ? 'active' : '' }}" 
                           id="{{ $key }}-tab" 
                           data-bs-toggle="tab" 
                           href="#{{ $key }}" 
                           role="tab">
                            @if($key === 'general')
                                <i class="bi bi-gear"></i>
                            @elseif($key === 'waha')
                                <i class="bi bi-whatsapp"></i>
                            @elseif($key === 'n8n')
                                <i class="bi bi-diagram-3"></i>
                            @elseif($key === 'ai')
                                <i class="bi bi-robot"></i>
                            @elseif($key === 'email')
                                <i class="bi bi-envelope"></i>
                            @endif
                            {{ $label }}
                        </a>
                    </li>
                    @endforeach
                </ul>
            </div>

            <form action="{{ route('admin.settings.update') }}" method="POST">
                @csrf
                <div class="card-body">
                    <div class="tab-content">
                        @foreach($groups as $groupKey => $groupLabel)
                        <div class="tab-pane fade {{ $loop->first ? 'show active' : '' }}" 
                             id="{{ $groupKey }}" 
                             role="tabpanel">
                            
                            @if(isset($settings[$groupKey]))
                                @foreach($settings[$groupKey] as $setting)
                                <div class="mb-4">
                                    <label for="{{ $setting->key }}" class="form-label fw-bold">
                                        {{ $setting->label }}
                                        @if($setting->is_encrypted)
                                            <i class="bi bi-lock text-warning" title="Encrypted"></i>
                                        @endif
                                    </label>
                                    
                                    @if($setting->description)
                                    <div class="text-muted small mb-2">{{ $setting->description }}</div>
                                    @endif

                                    @if($setting->type === 'text' || $setting->type === 'url')
                                        <input type="text" 
                                               class="form-control" 
                                               id="{{ $setting->key }}" 
                                               name="settings[{{ $setting->key }}]" 
                                               value="{{ old('settings.' . $setting->key, $setting->getActualValue()) }}"
                                               placeholder="Enter {{ strtolower($setting->label) }}">
                                    
                                    @elseif($setting->type === 'password')
                                        <div class="input-group">
                                            <input type="password" 
                                                   class="form-control" 
                                                   id="{{ $setting->key }}" 
                                                   name="settings[{{ $setting->key }}]" 
                                                   value="{{ $setting->value ? '********' : '' }}"
                                                   placeholder="Enter {{ strtolower($setting->label) }}">
                                            <button class="btn btn-outline-secondary" 
                                                    type="button" 
                                                    onclick="togglePassword('{{ $setting->key }}')">
                                                <i class="bi bi-eye" id="icon-{{ $setting->key }}"></i>
                                            </button>
                                        </div>
                                        <small class="text-muted">Leave as ******** to keep current value</small>
                                    
                                    @elseif($setting->type === 'number')
                                        <input type="number" 
                                               class="form-control" 
                                               id="{{ $setting->key }}" 
                                               name="settings[{{ $setting->key }}]" 
                                               value="{{ old('settings.' . $setting->key, $setting->value) }}"
                                               step="any"
                                               placeholder="Enter {{ strtolower($setting->label) }}">
                                    
                                    @elseif($setting->type === 'boolean')
                                        <div class="form-check form-switch">
                                            <input class="form-check-input" 
                                                   type="checkbox" 
                                                   id="{{ $setting->key }}" 
                                                   name="settings[{{ $setting->key }}]" 
                                                   value="true"
                                                   {{ old('settings.' . $setting->key, $setting->value) == 'true' ? 'checked' : '' }}>
                                            <label class="form-check-label" for="{{ $setting->key }}">
                                                Enable
                                            </label>
                                        </div>
                                    
                                    @elseif($setting->type === 'select')
                                        @if($setting->key === 'ai_provider')
                                        <select class="form-select" 
                                                id="{{ $setting->key }}" 
                                                name="settings[{{ $setting->key }}]">
                                            <option value="gemini" {{ $setting->value === 'gemini' ? 'selected' : '' }}>
                                                Google Gemini
                                            </option>
                                            <option value="openai" {{ $setting->value === 'openai' ? 'selected' : '' }}>
                                                OpenAI
                                            </option>
                                        </select>
                                        @elseif($setting->key === 'smtp_encryption')
                                        <select class="form-select" 
                                                id="{{ $setting->key }}" 
                                                name="settings[{{ $setting->key }}]">
                                            <option value="tls" {{ $setting->value === 'tls' ? 'selected' : '' }}>
                                                TLS
                                            </option>
                                            <option value="ssl" {{ $setting->value === 'ssl' ? 'selected' : '' }}>
                                                SSL
                                            </option>
                                        </select>
                                        @endif
                                    @endif
                                </div>
                                @endforeach

                                <!-- Connection Test Buttons -->
                                @if($groupKey === 'waha')
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i>
                                    <strong>WAHA Integration:</strong> Make sure your WAHA instance is running and accessible.
                                    <button type="button" 
                                            class="btn btn-sm btn-primary float-end" 
                                            onclick="testConnection('waha')">
                                        <i class="bi bi-plug"></i> Test Connection
                                    </button>
                                </div>
                                @endif

                                @if($groupKey === 'n8n')
                                <div class="alert alert-info">
                                    <i class="bi bi-info-circle"></i>
                                    <strong>n8n Integration:</strong> Configure your n8n workflow webhook URL.
                                    <button type="button" 
                                            class="btn btn-sm btn-primary float-end" 
                                            onclick="testConnection('n8n')">
                                        <i class="bi bi-plug"></i> Test Connection
                                    </button>
                                </div>
                                @endif

                                @if($groupKey === 'ai')
                                <div class="alert alert-warning">
                                    <i class="bi bi-exclamation-triangle"></i>
                                    <strong>API Keys:</strong> Keep your API keys secure. They are encrypted in the database.
                                </div>
                                @endif
                            @else
                                <div class="alert alert-secondary">
                                    <i class="bi bi-info-circle"></i>
                                    No settings available for this group.
                                </div>
                            @endif
                        </div>
                        @endforeach
                    </div>
                </div>

                <div class="card-footer bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <div class="text-muted small">
                            <i class="bi bi-shield-check"></i> 
                            All encrypted fields are stored securely
                        </div>
                        <div>
                            <button type="button" class="btn btn-secondary" onclick="window.location.reload()">
                                <i class="bi bi-arrow-clockwise"></i> Reset
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Save Settings
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Test Result Modal -->
<div class="modal fade" id="testResultModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Connection Test Result</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body" id="testResultBody">
                <!-- Result will be inserted here -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
            </div>
        </div>
    </div>
</div>
@endsection

@push('scripts')
<script>
function togglePassword(fieldId) {
    const input = document.getElementById(fieldId);
    const icon = document.getElementById('icon-' + fieldId);
    
    if (input.type === 'password') {
        input.type = 'text';
        icon.classList.remove('bi-eye');
        icon.classList.add('bi-eye-slash');
    } else {
        input.type = 'password';
        icon.classList.remove('bi-eye-slash');
        icon.classList.add('bi-eye');
    }
}

function testConnection(type) {
    const btn = event.target.closest('button');
    const originalHtml = btn.innerHTML;
    
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner-border spinner-border-sm"></span> Testing...';
    
    fetch(`/admin/settings/test-${type}`, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
        }
    })
    .then(response => response.json())
    .then(data => {
        const resultBody = document.getElementById('testResultBody');
        
        if (data.success) {
            resultBody.innerHTML = `
                <div class="alert alert-success">
                    <i class="bi bi-check-circle"></i>
                    <strong>Success!</strong><br>
                    ${data.message}
                </div>
            `;
        } else {
            resultBody.innerHTML = `
                <div class="alert alert-danger">
                    <i class="bi bi-x-circle"></i>
                    <strong>Failed!</strong><br>
                    ${data.message}
                </div>
            `;
        }
        
        const modal = new bootstrap.Modal(document.getElementById('testResultModal'));
        modal.show();
    })
    .catch(error => {
        const resultBody = document.getElementById('testResultBody');
        resultBody.innerHTML = `
            <div class="alert alert-danger">
                <i class="bi bi-x-circle"></i>
                <strong>Error!</strong><br>
                ${error.message}
            </div>
        `;
        
        const modal = new bootstrap.Modal(document.getElementById('testResultModal'));
        modal.show();
    })
    .finally(() => {
        btn.disabled = false;
        btn.innerHTML = originalHtml;
    });
}

// Auto-hide success/error alerts after 5 seconds
document.addEventListener('DOMContentLoaded', function() {
    const alerts = document.querySelectorAll('.alert-dismissible');
    alerts.forEach(alert => {
        setTimeout(() => {
            const bsAlert = new bootstrap.Alert(alert);
            bsAlert.close();
        }, 5000);
    });
});
</script>
@endpush