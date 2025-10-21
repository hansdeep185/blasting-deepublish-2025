@extends('layouts.app')

@section('content')
<div class="container py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>{{ $contactList->name }}</h2>
            <p class="text-muted mb-0">{{ $contactList->description }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('contacts.index', $contactList) }}" class="btn btn-primary">
                <i class="bi bi-people"></i> View Contacts
            </a>
            <a href="{{ route('contact-lists.edit', $contactList) }}" class="btn btn-outline-secondary">
                <i class="bi bi-pencil"></i> Edit
            </a>
            <a href="{{ route('contact-lists.index') }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back
            </a>
        </div>
    </div>

    <!-- Stats Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="bi bi-people-fill text-primary" style="font-size: 2rem;"></i>
                    <h3 class="mt-2 mb-0">{{ $contactList->contact_count }}</h3>
                    <p class="text-muted mb-0">Total Contacts</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="bi bi-tags-fill text-success" style="font-size: 2rem;"></i>
                    <h3 class="mt-2 mb-0">{{ $contactList->tags_count }}</h3>
                    <p class="text-muted mb-0">Tags</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="bi bi-calendar-check text-info" style="font-size: 2rem;"></i>
                    <h3 class="mt-2 mb-0">{{ $contactList->created_at->format('d M Y') }}</h3>
                    <p class="text-muted mb-0">Created</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <i class="bi bi-clock-history text-warning" style="font-size: 2rem;"></i>
                    <h3 class="mt-2 mb-0">{{ $contactList->updated_at->diffForHumans() }}</h3>
                    <p class="text-muted mb-0">Last Update</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="card mb-4">
        <div class="card-header">
            <h5 class="mb-0">Quick Actions</h5>
        </div>
        <div class="card-body">
            <div class="row g-3">
                <div class="col-md-3">
                    <a href="{{ route('contacts.create', $contactList) }}" class="btn btn-outline-primary w-100">
                        <i class="bi bi-person-plus"></i>
                        <div class="mt-2">Add Contact</div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('contacts.import.form', $contactList) }}" class="btn btn-outline-success w-100">
                        <i class="bi bi-upload"></i>
                        <div class="mt-2">Import CSV</div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('contact-tags.index', $contactList) }}" class="btn btn-outline-info w-100">
                        <i class="bi bi-tags"></i>
                        <div class="mt-2">Manage Tags</div>
                    </a>
                </div>
                <div class="col-md-3">
                    <a href="{{ route('contacts.index', $contactList) }}" class="btn btn-outline-secondary w-100">
                        <i class="bi bi-eye"></i>
                        <div class="mt-2">View All</div>
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Recent Activity or Contact Preview -->
    <div class="card">
        <div class="card-header">
            <h5 class="mb-0">Recent Contacts</h5>
        </div>
        <div class="card-body">
            @if($contactList->contacts()->count() > 0)
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Added</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($contactList->contacts()->latest()->take(5)->get() as $contact)
                                <tr>
                                    <td>{{ $contact->name ?: 'N/A' }}</td>
                                    <td>{{ $contact->phone_number }}</td>
                                    <td>{{ $contact->email ?: '-' }}</td>
                                    <td>{{ $contact->created_at->diffForHumans() }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="text-center mt-3">
                    <a href="{{ route('contacts.index', $contactList) }}" class="btn btn-outline-primary">
                        View All {{ $contactList->contact_count }} Contacts
                    </a>
                </div>
            @else
                <div class="text-center py-4">
                    <i class="bi bi-inbox" style="font-size: 3rem; color: #ccc;"></i>
                    <p class="text-muted mt-3">No contacts in this list yet</p>
                    <a href="{{ route('contacts.create', $contactList) }}" class="btn btn-primary">
                        Add First Contact
                    </a>
                </div>
            @endif
        </div>
    </div>
</div>
@endsection