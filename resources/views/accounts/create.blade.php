@extends('layouts.app')

@section('title', 'Add New Account')
@section('page-title', 'Add New WhatsApp Account')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white">
                <h5 class="mb-0">Create New WhatsApp Account</h5>
            </div>
            <div class="card-body">
                <div class="alert alert-info">
                    <i class="bi bi-info-circle"></i>
                    <strong>Important:</strong> After creating the account, you will need to scan a QR code with your WhatsApp mobile app to connect.
                </div>

                <form action="{{ route('accounts.store') }}" method="POST">
                    @csrf

                    <div class="mb-4">
                        <label for="session_name" class="form-label">
                            Account Name <span class="text-danger">*</span>
                        </label>
                        <input type="text" 
                               class="form-control @error('session_name') is-invalid @enderror" 
                               id="session_name" 
                               name="session_name" 
                               value="{{ old('session_name') }}"
                               placeholder="e.g., My Business Account"
                               required>
                        <div class="form-text">
                            Give your WhatsApp account a memorable name
                        </div>
                        @error('session_name')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                    </div>

                    <div class="card bg-light border-0 mb-4">
                        <div class="card-body">
                            <h6 class="mb-3">
                                <i class="bi bi-question-circle"></i> How it works:
                            </h6>
                            <ol class="mb-0 ps-3">
                                <li class="mb-2">Click "Create Account" below</li>
                                <li class="mb-2">A QR code will be generated</li>
                                <li class="mb-2">Open WhatsApp on your phone</li>
                                <li class="mb-2">Go to Settings → Linked Devices</li>
                                <li>Scan the QR code to connect</li>
                            </ol>
                        </div>
                    </div>

                    <div class="d-flex justify-content-between align-items-center">
                        <a href="{{ route('accounts.index') }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back
                        </a>
                        <button type="submit" class="btn btn-whatsapp">
                            <i class="bi bi-plus-circle"></i> Create Account
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Info Card -->
        <div class="card border-0 shadow-sm mt-4">
            <div class="card-body">
                <h6 class="mb-3">
                    <i class="bi bi-shield-check"></i> Security & Privacy
                </h6>
                <ul class="mb-0">
                    <li class="mb-2">Your WhatsApp account stays on your phone</li>
                    <li class="mb-2">End-to-end encryption is maintained</li>
                    <li class="mb-2">You can disconnect at any time</li>
                    <li>Messages are sent from your actual WhatsApp number</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection