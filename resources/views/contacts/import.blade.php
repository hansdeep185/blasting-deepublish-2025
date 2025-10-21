@extends('layouts.app')

@section('content')

<div class="container py-4">
<!-- Breadcrumb -->
<nav aria-label="breadcrumb" class="mb-3">
<ol class="breadcrumb">
<li class="breadcrumb-item"><a href="{{ route('contact-lists.index') }}">Contact Lists</a></li>
<li class="breadcrumb-item"><a href="{{ route('contacts.index', $contactList) }}">{{ $contactList->name }}</a></li>
<li class="breadcrumb-item active">Import Contacts</li>
</ol>
</nav>

<div class="row justify-content-center">
    <div class="col-md-8">
        <div class="card shadow-sm">
            <div class="card-header bg-primary text-white">
                <h4 class="mb-0">
                    <i class="bi bi-upload"></i> Import Contacts from CSV/Excel
                </h4>
            </div>
            <div class="card-body">
                <!-- Instructions -->
                <div class="alert alert-info">
                    <h6><i class="bi bi-info-circle"></i> Import Instructions</h6>
                    <ol class="mb-0">
                        <li>Download the CSV template below</li>
                        <li>Fill in your contact data (phone_number is required)</li>
                        <li>Supported columns: <code>phone_number</code>, <code>name</code>, <code>email</code></li>
                        <li>Upload the completed file (CSV or Excel)</li>
                        <li>Optionally assign a tag to all imported contacts</li>
                    </ol>
                </div>

                <!-- Download Template -->
                <div class="mb-4">
                    <a href="{{ route('contacts.template', $contactList) }}" class="btn btn-outline-primary">
                        <i class="bi bi-download"></i> Download CSV Template
                    </a>
                </div>

                <hr>

                <!-- Upload Form -->
                <form action="{{ route('contacts.import', $contactList) }}" method="POST" enctype="multipart/form-data">
                    @csrf
                    {{-- MENAMBAHKAN INPUT TERSEMBUNYI UNTUK contact_list_id --}}
                    <input type="hidden" name="contact_list_id" value="{{ $contactList->id }}">
                    
                    <div class="mb-3">
                        <label for="csv_file" class="form-label">
                            Select File <span class="text-danger">*</span>
                        </label>
                        {{-- MENGGANTI name="file" MENJADI name="csv_file" --}}
                        <input type="file" 
                               class="form-control @error('csv_file') is-invalid @enderror" 
                               id="csv_file" 
                               name="csv_file" 
                               accept=".csv,.xlsx,.xls"
                               required>
                        @error('csv_file')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">
                            Accepted formats: CSV, XLSX, XLS (Max: 10MB)
                        </small>
                    </div>

                    <div class="mb-4">
                        <label for="tag_id" class="form-label">
                            Assign Tag (Optional)
                        </label>
                        {{-- MENGGANTI name="tag_id" MENJADI name="tag_ids[]" dan menambahkan multiple --}}
                        <select name="tag_ids[]" id="tag_ids" class="form-select @error('tag_ids') is-invalid @enderror" multiple>
                            <option value="">-- No tag --</option>
                            @foreach($tags as $tag)
                                <option value="{{ $tag->id }}">{{ $tag->name }}</option>
                            @endforeach
                        </select>
                        @error('tag_ids')
                            <div class="invalid-feedback">{{ $message }}</div>
                        @enderror
                        <small class="text-muted">
                            All imported contacts will be tagged automatically. Hold Ctrl/Cmd to select multiple.
                        </small>
                    </div>

                    <!-- CSV Format Example -->
                    <div class="card bg-light mb-4">
                        <div class="card-header">
                            <h6 class="mb-0">CSV Format Example:</h6>
                        </div>
                        <div class="card-body">
                            <pre class="mb-0"><code>phone_number,name,email


08123456789,John Doe,john@example.com
628987654321,Jane Smith,jane@example.com
+6281234567890,Bob Wilson,bob@example.com</code></pre>
</div>
</div>

                    <div class="d-flex gap-2">
                        <button type="submit" class="btn btn-success">
                            <i class="bi bi-upload"></i> Import Contacts
                        </button>
                        <a href="{{ route('contacts.index', $contactList) }}" class="btn btn-secondary">
                            <i class="bi bi-arrow-left"></i> Back to List
                        </a>
                    </div>
                </form>
            </div>
        </div>

        <!-- Tips Card -->
        <div class="card mt-4">
            <div class="card-header">
                <h6 class="mb-0"><i class="bi bi-lightbulb"></i> Pro Tips</h6>
            </div>
            <div class="card-body">
                <ul class="mb-0">
                    <li>Phone numbers will be automatically cleaned (only numbers kept)</li>
                    <li>Formats accepted: <code>08xxx</code>, <code>62xxx</code>, <code>+62xxx</code></li>
                    <li>Duplicate phone numbers will be skipped</li>
                    <li>Column names are case-insensitive</li>
                    <li>Alternative column names: <code>phone</code>, <code>number</code>, <code>no</code>, <code>nama</code></li>
                    <li>Empty rows will be automatically skipped</li>
                </ul>
            </div>
        </div>
    </div>
</div>


</div>
@endsection