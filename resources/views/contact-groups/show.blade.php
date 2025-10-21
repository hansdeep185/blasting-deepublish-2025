@extends('layouts.app')

@section('content')
<div class="container py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>{{ $contactGroup->name }}</h2>
            <p class="text-muted mb-0">{{ $contactGroup->description }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('contact-groups.edit', $contactGroup) }}" class="btn btn-outline-primary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="{{ route('contact-groups.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    <!-- Stats -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="mb-0">{{ $contactGroup->contacts->count() }}</h3>
                    <p class="text-muted mb-0">Total Contacts</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="mb-0">{{ $contactGroup->contacts->where('is_active', true)->count() }}</h3>
                    <p class="text-muted mb-0">Active Contacts</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="mb-0">{{ $availableContacts->count() }}</h3>
                    <p class="text-muted mb-0">Available to Add</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Add Contacts Section -->
    @if($availableContacts->isNotEmpty())
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Add Contacts to Group</h5>
        </div>
        <div class="card-body">
            <form action="{{ route('contact-groups.add-contacts', $contactGroup) }}" method="POST">
                @csrf
                <div class="mb-3">
                    <label class="form-label">Select Contacts</label>
                    <select name="contact_ids[]" class="form-select" multiple size="5" required>
                        @foreach($availableContacts as $contact)
                            <option value="{{ $contact->id }}">
                                {{ $contact->name }} ({{ $contact->phone }})
                            </option>
                        @endforeach
                    </select>
                    <small class="text-muted">Hold Ctrl/Cmd to select multiple contacts</small>
                </div>
                <button type="submit" class="btn btn-primary">
                    <i class="bi bi-plus-circle"></i> Add Selected Contacts
                </button>
            </form>
        </div>
    </div>
    @endif

    <!-- Contacts in Group -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Contacts in This Group</h5>
        </div>
        <div class="card-body">
            @if($contactGroup->contacts->isEmpty())
                <div class="alert alert-info mb-0">
                    <i class="bi bi-info-circle"></i> No contacts in this group yet.
                </div>
            @else
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Company</th>
                                <th>Status</th>
                                <th width="100">Action</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($contactGroup->contacts as $contact)
                                <tr>
                                    <td>
                                        <strong>{{ $contact->name }}</strong>
                                    </td>
                                    <td>{{ $contact->phone }}</td>
                                    <td>{{ $contact->email ?: '-' }}</td>
                                    <td>{{ $contact->company ?: '-' }}</td>
                                    <td>
                                        @if($contact->is_active)
                                            <span class="badge bg-success">Active</span>
                                        @else
                                            <span class="badge bg-secondary">Inactive</span>
                                        @endif
                                    </td>
                                    <td>
                                        <form action="{{ route('contact-groups.remove-contact', [$contactGroup, $contact]) }}" 
                                              method="POST" 
                                              onsubmit="return confirm('Remove this contact from group?')">
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="btn btn-sm btn-outline-danger" title="Remove from group">
                                                <i class="bi bi-x-circle"></i>
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection