@extends('layouts.app')

@section('content')
<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('contact-lists.index') }}">Contact Lists</a></li>
            <li class="breadcrumb-item active">{{ $contactList->name }}</li>
        </ol>
    </nav>

    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2>{{ $contactList->name }}</h2>
            <p class="text-muted mb-0">{{ $contactList->description }}</p>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('contact-tags.index', $contactList) }}" class="btn btn-outline-secondary">
                <i class="bi bi-tags"></i> Manage Tags
            </a>
            <a href="{{ route('contacts.import.form', $contactList) }}" class="btn btn-success">
                <i class="bi bi-upload"></i> Import CSV
            </a>
            <a href="{{ route('contacts.create', $contactList) }}" class="btn btn-primary">
                <i class="bi bi-plus-circle"></i> Add Contact
            </a>
        </div>
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
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="mb-0">{{ $contactList->total_contacts ?? 0 }}</h3>
                    <p class="text-muted mb-0">Total Contacts</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="mb-0">{{ $contacts->total() }}</h3>
                    <p class="text-muted mb-0">{{ request('tag_id') ? 'Filtered' : 'All' }} Results</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="mb-0">{{ $tags->count() }}</h3>
                    <p class="text-muted mb-0">Tags</p>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card text-center">
                <div class="card-body">
                    <h3 class="mb-0">{{ $contactList->contacts()->where('is_blacklisted', false)->whereNull('opted_out_at')->count() }}</h3>
                    <p class="text-muted mb-0">Can Receive</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filters -->
    <div class="card mb-4">
        <div class="card-body">
            <form action="{{ route('contacts.index', $contactList) }}" method="GET" class="row g-3">
                <div class="col-md-5">
                    <input type="text" 
                           name="search" 
                           class="form-control" 
                           placeholder="Search name, phone, email..." 
                           value="{{ request('search') }}">
                </div>
                <div class="col-md-3">
                    <select name="tag_id" class="form-select">
                        <option value="">All Tags</option>
                        @foreach($tags as $tag)
                            <option value="{{ $tag->id }}" {{ request('tag_id') == $tag->id ? 'selected' : '' }}>
                                {{ $tag->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="bi bi-search"></i> Filter
                    </button>
                </div>
                <div class="col-md-2">
                    <a href="{{ route('contacts.index', $contactList) }}" class="btn btn-secondary w-100">
                        <i class="bi bi-x-circle"></i> Clear
                    </a>
                </div>
            </form>
        </div>
    </div>

    <!-- Bulk Actions -->
    <div class="card mb-3">
        <div class="card-body">
            <form action="{{ route('contacts.bulk-delete', $contactList) }}" method="POST" id="bulkActionForm">
                @csrf
                <div class="row align-items-center">
                    <div class="col-auto">
                        <input type="checkbox" id="selectAll" class="form-check-input">
                        <label for="selectAll" class="form-check-label">Select All</label>
                    </div>
                    <div class="col-auto">
                        <select class="form-select form-select-sm" id="bulkAction" disabled>
                            <option value="">Bulk Actions</option>
                            <option value="delete">Delete Selected</option>
                            <option value="tag">Add Tag</option>
                        </select>
                    </div>
                    <div class="col-auto d-none" id="tagSelector">
                        <select name="tag_id" class="form-select form-select-sm">
                            <option value="">Select Tag</option>
                            @foreach($tags as $tag)
                                <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="col-auto">
                        <button type="button" class="btn btn-sm btn-primary" id="applyBulkAction" disabled>
                            Apply
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Contacts Table -->
    @if($contacts->isEmpty())
        <div class="alert alert-info">
            <i class="bi bi-info-circle"></i> No contacts found. 
            <a href="{{ route('contacts.create', $contactList) }}">Add your first contact</a> or 
            <a href="{{ route('contacts.import.form', $contactList) }}">import from CSV</a>.
        </div>
    @else
        <div class="card">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th width="30" class="ps-3">
                                    <input type="checkbox" class="form-check-input" disabled>
                                </th>
                                <th>Name</th>
                                <th>Phone</th>
                                <th>Email</th>
                                <th>Tags</th>
                                <th>Status</th>
                                <th width="120">Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($contacts as $contact)
                                <tr class="{{ !$contact->canReceiveMessage() ? 'table-secondary' : '' }}">
                                    <td class="ps-3">
                                        <input type="checkbox" 
                                               name="contact_ids[]" 
                                               value="{{ $contact->id }}" 
                                               class="form-check-input contact-checkbox">
                                    </td>
                                    <td>
                                        <strong>{{ $contact->name ?: 'N/A' }}</strong>
                                    </td>
                                    <td>
                                        {{ $contact->phone_number }}
                                        <a href="https://wa.me/{{ $contact->formatted_phone }}" 
                                           target="_blank" 
                                           class="btn btn-sm btn-success ms-1" 
                                           title="Chat on WhatsApp">
                                            <i class="bi bi-whatsapp"></i>
                                        </a>
                                    </td>
                                    <td>{{ $contact->email ?: '-' }}</td>
                                    <td>
                                        @forelse($contact->tags as $tag)
                                            <span class="badge" style="background-color: {{ $tag->color }}">
                                                {{ $tag->name }}
                                            </span>
                                        @empty
                                            <span class="text-muted">-</span>
                                        @endforelse
                                    </td>
                                    <td>
                                        @if($contact->is_blacklisted)
                                            <span class="badge bg-danger">Blacklisted</span>
                                        @elseif($contact->hasOptedOut())
                                            <span class="badge bg-warning">Opted Out</span>
                                        @else
                                            <span class="badge bg-success">Active</span>
                                        @endif
                                    </td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="{{ route('contacts.edit', [$contactList, $contact]) }}" 
                                               class="btn btn-outline-primary" 
                                               title="Edit">
                                                <i class="bi bi-pencil"></i>
                                            </a>
                                            <form action="{{ route('contacts.destroy', [$contactList, $contact]) }}" 
                                                  method="POST" 
                                                  onsubmit="return confirm('Delete this contact?')"
                                                  style="display: inline;">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" 
                                                        class="btn btn-outline-danger btn-sm" 
                                                        title="Delete">
                                                    <i class="bi bi-trash"></i>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="d-flex justify-content-center mt-4">
            {{ $contacts->links() }}
        </div>
    @endif
</div>

<script>
// Select All functionality
document.getElementById('selectAll')?.addEventListener('change', function() {
    const checkboxes = document.querySelectorAll('.contact-checkbox');
    checkboxes.forEach(cb => cb.checked = this.checked);
    toggleBulkActions();
});

// Enable/disable bulk actions
document.querySelectorAll('.contact-checkbox').forEach(checkbox => {
    checkbox.addEventListener('change', toggleBulkActions);
});

// Show/hide tag selector
document.getElementById('bulkAction')?.addEventListener('change', function() {
    const tagSelector = document.getElementById('tagSelector');
    if (this.value === 'tag') {
        tagSelector.classList.remove('d-none');
    } else {
        tagSelector.classList.add('d-none');
    }
});

function toggleBulkActions() {
    const checkedBoxes = document.querySelectorAll('.contact-checkbox:checked');
    const bulkAction = document.getElementById('bulkAction');
    const applyBtn = document.getElementById('applyBulkAction');
    
    bulkAction.disabled = checkedBoxes.length === 0;
    applyBtn.disabled = checkedBoxes.length === 0;
}

document.getElementById('applyBulkAction')?.addEventListener('click', function() {
    const action = document.getElementById('bulkAction').value;
    const checkedBoxes = document.querySelectorAll('.contact-checkbox:checked');
    
    if (!action || checkedBoxes.length === 0) return;
    
    const form = document.getElementById('bulkActionForm');
    
    if (action === 'delete') {
        if (confirm(`Delete ${checkedBoxes.length} selected contact(s)?`)) {
            form.action = '{{ route("contacts.bulk-delete", $contactList) }}';
            form.submit();
        }
    } else if (action === 'tag') {
        const tagId = document.querySelector('#tagSelector select').value;
        if (!tagId) {
            alert('Please select a tag');
            return;
        }
        form.action = '{{ route("contact-tags.bulk-tag", $contactList) }}';
        form.submit();
    }
});
</script>
@endsection