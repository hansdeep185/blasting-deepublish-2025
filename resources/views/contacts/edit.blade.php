@extends('layouts.app')

@section('content')
<div class="container py-4">
    <!-- Breadcrumb -->
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb">
            <li class="breadcrumb-item"><a href="{{ route('contact-lists.index') }}">Contact Lists</a></li>
            <li class="breadcrumb-item"><a href="{{ route('contacts.index', $contactList) }}">{{ $contactList->name }}</a></li>
            <li class="breadcrumb-item active">Edit Contact</li>
        </ol>
    </nav>

    <div class="row justify-content-center">
        <div class="col-md-8">
            <div class="card shadow-sm">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">Edit Contact</h4>
                    <div>
                        @if($contact->is_blacklisted)
                            <span class="badge bg-danger">Blacklisted</span>
                        @elseif($contact->hasOptedOut())
                            <span class="badge bg-warning">Opted Out</span>
                        @else
                            <span class="badge bg-success">Active</span>
                        @endif
                    </div>
                </div>
                <div class="card-body">
                    <form action="{{ route('contacts.update', [$contactList, $contact]) }}" method="POST">
                        @csrf
                        @method('PUT')
                        
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label for="phone_number" class="form-label">
                                    Phone Number <span class="text-danger">*</span>
                                </label>
                                <input type="text" 
                                       class="form-control @error('phone_number') is-invalid @enderror" 
                                       id="phone_number" 
                                       name="phone_number" 
                                       value="{{ old('phone_number', $contact->phone_number) }}" 
                                       required>
                                @error('phone_number')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>

                            <div class="col-md-6 mb-3">
                                <label for="name" class="form-label">Full Name</label>
                                <input type="text" 
                                       class="form-control @error('name') is-invalid @enderror" 
                                       id="name" 
                                       name="name" 
                                       value="{{ old('name', $contact->name) }}">
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="email" class="form-label">Email</label>
                            <input type="email" 
                                   class="form-control @error('email') is-invalid @enderror" 
                                   id="email" 
                                   name="email" 
                                   value="{{ old('email', $contact->email) }}">
                            @error('email')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                        </div>

                        <div class="mb-3">
                            <label for="tag_ids" class="form-label">Tags</label>
                            <select name="tag_ids[]" 
                                    id="tag_ids" 
                                    class="form-select @error('tag_ids') is-invalid @enderror" 
                                    multiple 
                                    size="5">
                                @forelse($tags as $tag)
                                    <option value="{{ $tag->id }}" 
                                        {{ in_array($tag->id, old('tag_ids', $contact->tags->pluck('id')->toArray())) ? 'selected' : '' }}>
                                        {{ $tag->name }}
                                    </option>
                                @empty
                                    <option disabled>No tags available</option>
                                @endforelse
                            </select>
                            @error('tag_ids')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <small class="text-muted">Hold Ctrl/Cmd to select multiple tags</small>
                        </div>

                        <hr>

                        <div class="d-flex gap-2">
                            <button type="submit" class="btn btn-primary">
                                <i class="bi bi-save"></i> Update Contact
                            </button>
                            <a href="{{ route('contacts.index', $contactList) }}" class="btn btn-secondary">
                                <i class="bi bi-arrow-left"></i> Back
                            </a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Contact Info -->
            <div class="card mt-3">
                <div class="card-header">
                    <h6 class="mb-0">Contact Information</h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Created:</strong></p>
                            <p>{{ $contact->created_at->format('d M Y, H:i') }}</p>
                        </div>
                        <div class="col-md-6">
                            <p class="mb-1"><strong>Last Updated:</strong></p>
                            <p>{{ $contact->updated_at->format('d M Y, H:i') }}</p>
                        </div>
                    </div>
                    
                    @if($contact->hasOptedOut())
                        <div class="alert alert-warning">
                            <i class="bi bi-exclamation-triangle"></i> 
                            This contact opted out on {{ $contact->opted_out_at->format('d M Y, H:i') }}
                        </div>
                    @endif

                    @if($contact->is_blacklisted)
                        <div class="alert alert-danger">
                            <i class="bi bi-ban"></i> 
                            This contact is blacklisted and will not receive any messages
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection