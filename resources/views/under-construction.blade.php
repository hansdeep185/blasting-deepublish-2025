@extends('layouts.app')

@section('title', 'Under Construction')
@section('page-title', 'Under Construction')

@section('content')
<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card border-0 shadow-sm text-center py-5">
            <div class="card-body">
                <i class="bi bi-tools text-warning" style="font-size: 5rem;"></i>
                <h2 class="mt-4 mb-3">Under Construction</h2>
                <p class="text-muted mb-4">
                    This feature is currently being developed and will be available soon.
                </p>
                <div class="alert alert-info d-inline-block">
                    <i class="bi bi-info-circle"></i>
                    We're working hard to bring you this feature. Stay tuned!
                </div>
                <div class="mt-4">
                    @if(auth()->user()->isAdmin())
                        <a href="{{ route('admin.dashboard') }}" class="btn btn-primary">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                    @else
                        <a href="{{ route('dashboard') }}" class="btn btn-primary">
                            <i class="bi bi-arrow-left"></i> Back to Dashboard
                        </a>
                    @endif
                </div>
            </div>
        </div>
    </div>
</div>
@endsection