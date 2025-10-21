@extends('layouts.app')

@section('content')
<div class="container py-4">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Contact Lists</h2>
            <p class="text-muted mb-0">Manage your contact lists and organize contacts</p>
        </div>
        <a href="{{ route('contact-lists.create') }}" class="btn btn-primary">
            <i class="bi bi-plus-circle"></i> Create New List
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
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="mb-0">{{ $contactLists->total() }}</h3>
                    <p class="text-muted mb-0">Total Lists</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="mb-0">{{ $contactLists->sum('contact_count') }}</h3>
                    <p class="text-muted mb-0">Total Contacts</p>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="mb-0">{{ $contactLists->where('contact_count', '>', 0)->count() }}</h3>
                    <p class="text-muted mb-0">Active Lists</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Contact Lists -->
    @if($contactLists->isEmpty())
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> You don't have any contact lists yet. 
            <a href="{{ route('contact-lists.create') }}">Create your first list</a> to get started!
        </div>
    @else
        <div class="row">
            @foreach($contactLists as $list)
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm hover-shadow">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-folder-fill text-primary"></i>
                                    {{ $list->name }}
                                </h5>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="{{ route('contacts.index', $list) }}">
                                                <i class="bi bi-eye"></i> View Contacts
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('contact-tags.index', $list) }}">
                                                <i class="bi bi-tags"></i> Manage Tags
                                            </a>
                                        </li>
                                        <li>
                                            <a class="dropdown-item" href="{{ route('contact-lists.edit', $list) }}">
                                                <i class="bi bi-pencil"></i> Edit List
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('contact-lists.destroy', $list) }}" 
                                                  method="POST" 
                                                  onsubmit="return confirm('Delete this list and all its contacts? This cannot be undone!')">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="dropdown-item text-danger">
                                                    <i class="bi bi-trash"></i> Delete List
                                                </button>
                                            </form>
                                        </li>
                                    </ul>
                                </div>
                            </div>

                            <p class="card-text text-muted small">
                                {{ Str::limit($list->description, 80) ?: 'No description' }}
                            </p>

                            <div class="d-flex justify-content-between align-items-center mt-3 pt-3 border-top">
                                <div>
                                    <i class="bi bi-people-fill"></i>
                                    <strong>{{ $list->contact_count }}</strong> contacts
                                </div>
                                <small class="text-muted">
                                    {{ $list->updated_at->diffForHumans() }}
                                </small>
                            </div>
                        </div>
                        <div class="card-footer bg-transparent">
                            <div class="d-grid gap-2">
                                <a href="{{ route('contacts.index', $list) }}" 
                                   class="btn btn-sm btn-outline-primary">
                                    <i class="bi bi-eye"></i> View Contacts
                                </a>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <!-- Pagination -->
        <div class="d-flex justify-content-center mt-4">
            {{ $contactLists->links() }}
        </div>
    @endif
</div>

<style>
.hover-shadow {
    transition: all 0.3s ease;
}
.hover-shadow:hover {
    transform: translateY(-5px);
    box-shadow: 0 .5rem 1rem rgba(0,0,0,.15)!important;
}
</style>
@endsection