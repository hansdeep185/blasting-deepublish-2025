@extends('layouts.app')

@section('content')
<div class="container py-4">
    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header">
                    <h4 class="mb-0">Edit Contact List</h4>
                </div>
                <div class="card-body">
                    <form action="{{ route('contact-lists.update', $contactList) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="mb-3">
                            <label for="name" class="form-label">
                                List Name <span class="text-danger">*</span>
                            </label>
                            <input type="text" 
                                   class="form-control @error('name') is-invalid @enderror" 
                                   id="name" 
                                   name="name" 
                                   value="{{ old('name', $contactList->name) }}" 
                                   required>
                            @error('name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-4">
                            <label for="description" class="form-label">Description</label>
                            <textarea class="form-control @error('description') is-invalid @enderror" 
                                      id="description" 
                                      name="description" 
                                      rows="3">{{ old('description', $contactList->description) }}</textarea>
                            @error('description')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="card bg-light mb-4">
                            <div class="card-body">
                                <h6>List Statistics</h6>
                                <div class="row">
                                    <div class="col-md-4">
                                        <p class="mb-1"><strong>Total Contacts:</strong></p>
                                        <p class="text-muted">{{ $contactList->contact_count }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <p class="mb-1"><strong>Created:</strong></p>
                                        <p class="text-muted">{{ $contactList->created_at->format('d M Y') }}</p>
                                    </div>
                                    <div class="col-md-4">
                                        <p class="mb-1"><strong>Last Updated:</strong></p>
                                        <p class="text-muted">{{ $contactList->updated_at->diffForHumans() }}</p>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <hr>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Update List
                            </button>
                            <a href="{{ route('contact-lists.index') }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back
                            </a>
                            <a href="{{ route('contacts.index', $contactList) }}" class="btn btn-outline-primary ms-auto">
                                <i class="bi bi-people"></i> View Contacts
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Danger Zone -->
            <div class="card mt-4 border-danger">
                <div class="card-header bg-danger text-white">
                    <h6 class="mb-0"><i class="bi bi-exclamation-triangle"></i> Danger Zone</h6>
                </div>
                <div class="card-body">
                    <p class="text-muted">
                        Deleting this list will permanently remove <strong>{{ $contactList->contact_count }} contacts</strong> 
                        and all associated data. This action cannot be undone.
                    </p>
                    <form action="{{ route('contact-lists.destroy', $contactList) }}" 
                          method="POST" 
                          onsubmit="return confirm('Are you absolutely sure? This will delete all contacts in this list. Type DELETE to confirm.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="btn btn-danger">
                            <i class="bi bi-trash"></i> Delete This List
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection