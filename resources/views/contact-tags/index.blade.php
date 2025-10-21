@extends('layouts.app')

@section('content')
<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('contact-lists.index') }}">Contact Lists</a></li>
            <li class="breadcrumb-item"><a href="{{ route('contacts.index', $contactList) }}">{{ $contactList->name }}</a></li>
            <li class="breadcrumb-item active">Tags</li>
        </ol>
    </nav>

    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>Contact Tags</h2>
            <p class="text-muted mb-0">Organize your contacts with tags for easy filtering</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#createTagModal">
                <i class="bi bi-plus-circle"></i> Create Tag
            </button>
            <a href="{{ route('contacts.index', $contactList) }}" class="btn btn-outline-secondary">
                <i class="bi bi-arrow-left"></i> Back to Contacts
            </a>
        </div>
    </div>

    @if(session('success'))
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            {{ session('success') }}
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    @endif

    @if($tags->isEmpty())
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No tags yet. Create your first tag to start organizing contacts!
        </div>
    @else
        <div class="row">
            @foreach($tags as $tag)
                <div class="col-md-6 col-lg-4 mb-4">
                    <div class="card h-100 shadow-sm">
                        <div class="card-body">
                            <div class="d-flex justify-content-between align-items-start mb-3">
                                <h5 class="card-title mb-0">
                                    <span class="badge" style="background-color: {{ $tag->color }}">
                                        {{ $tag->name }}
                                    </span>
                                </h5>
                                <div class="dropdown">
                                    <button class="btn btn-sm btn-outline-secondary" type="button" data-bs-toggle="dropdown">
                                        <i class="bi bi-three-dots-vertical"></i>
                                    </button>
                                    <ul class="dropdown-menu dropdown-menu-end">
                                        <li>
                                            <a class="dropdown-item" href="#" 
                                               data-bs-toggle="modal" 
                                               data-bs-target="#editTagModal{{ $tag->id }}">
                                                <i class="bi bi-pencil"></i> Edit
                                            </a>
                                        </li>
                                        <li><hr class="dropdown-divider"></li>
                                        <li>
                                            <form action="{{ route('contact-tags.destroy', [$contactList, $tag]) }}" 
                                                  method="POST" 
                                                  onsubmit="return confirm('Delete this tag? Contacts will not be deleted.')">
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

                            <p class="card-text text-muted">
                                {{ $tag->description ?: 'No description' }}
                            </p>

                            <div class="d-flex justify-content-between align-items-center mt-3">
                                <div>
                                    <i class="bi bi-people"></i>
                                    <strong>{{ $tag->contacts_count }}</strong> contacts
                                </div>
                                <a href="{{ route('contacts.index', ['contactList' => $contactList, 'tag_id' => $tag->id]) }}" 
                                   class="btn btn-sm btn-outline-primary">
                                    View Contacts
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Edit Tag Modal -->
                    <div class="modal fade" id="editTagModal{{ $tag->id }}" tabindex="-1">
                        <div class="modal-dialog">
                            <div class="modal-content">
                                <form action="{{ route('contact-tags.update', [$contactList, $tag]) }}" method="POST">
                                    @csrf
                                    @method('PUT')
                                    <div class="modal-header">
                                        <h5 class="modal-title">Edit Tag</h5>
                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                    </div>
                                    <div class="modal-body">
                                        <div class="mb-3">
                                            <label class="form-label">Tag Name *</label>
                                            <input type="text" name="name" class="form-control" value="{{ $tag->name }}" required>
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Color</label>
                                            <input type="color" name="color" class="form-control" value="{{ $tag->color }}">
                                        </div>
                                        <div class="mb-3">
                                            <label class="form-label">Description</label>
                                            <textarea name="description" class="form-control" rows="2">{{ $tag->description }}</textarea>
                                        </div>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                        <button type="submit" class="btn btn-primary">Update Tag</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    @endif
</div>

<!-- Create Tag Modal -->
<div class="modal fade" id="createTagModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('contact-tags.store', $contactList) }}" method="POST">
                @csrf
                <div class="modal-header">
                    <h5 class="modal-title">Create New Tag</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Tag Name <span class="text-danger">*</span></label>
                        <input type="text" name="name" class="form-control" placeholder="VIP, Customer, Lead, etc." required>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Color</label>
                        <input type="color" name="color" class="form-control" value="#6c757d">
                        <small class="text-muted">Choose a color to identify this tag</small>
                    </div>
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea name="description" class="form-control" rows="2" placeholder="Optional description..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Create Tag</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection