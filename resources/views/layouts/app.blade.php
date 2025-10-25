<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name', 'Laravel') }} - @yield('title', 'Dashboard')</title>

    {{-- Bootstrap Icons --}}
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body class="bg-light">
    <div class="d-flex" style="min-height: 100vh;">
        
        {{-- ====================================================== --}}
        {{-- AWAL PERBAIKAN: Bungkus semua yang butuh user login --}}
        {{-- ====================================================== --}}
        @auth
        <!-- Sidebar -->
        <nav class="sidebar text-white p-3 d-flex flex-column" style="width: 250px; min-height: 100vh; background: linear-gradient(180deg, #25D366 0%, #128C7E 100%);">
            <div>
                <div class="mb-4">
                    <h4 class="fw-bold">
                        <i class="bi bi-whatsapp"></i>
                        WA Blast
                    </h4>
                    <small class="text-white-50">
                        {{ auth()->user()->role === 'admin' ? 'Admin Panel' : 'User Dashboard' }}
                    </small>
                </div>

                <hr class="text-white-50">

                <!-- User Menu -->
                @if(auth()->user()->isUser())
                <ul class="nav flex-column">
                    <li class="nav-item mb-2">
                        <a href="{{ route('dashboard') }}" 
                           class="nav-link text-white {{ request()->routeIs('dashboard') ? 'active bg-white bg-opacity-25 rounded' : '' }}">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="{{ route('accounts.index') }}" 
                           class="nav-link text-white {{ request()->routeIs('accounts.*') ? 'active bg-white bg-opacity-25 rounded' : '' }}">
                            <i class="bi bi-phone me-2"></i> WA Accounts
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="{{ route('contact-lists.index') }}" 
                           class="nav-link text-white {{ request()->routeIs('contact-lists.*', 'contacts.*', 'contact-tags.*') ? 'active bg-white bg-opacity-25 rounded' : '' }}">
                            <i class="bi bi-people me-2"></i> Contacts
                        </a>
                    </li>
                    
                    <li class="nav-item mb-2">
                        <hr class="text-white-50 my-2">
                    </li>

                    <li class="nav-item mb-2">
                        <a href="{{ route('templates.index') }}" 
                           class="nav-link text-white {{ request()->routeIs('templates.*') ? 'active bg-white bg-opacity-25 rounded' : '' }}">
                            <i class="bi bi-file-text me-2"></i> Templates
                        </a>
                    </li>

                    <li class="nav-item mb-2">
                        {{-- PERBAIKAN KECIL: Gunakan 'blasts.*' untuk mencakup semua route di bawahnya --}}
                        <a href="{{ route('blasts.index') }}" 
                           class="nav-link text-white {{ request()->routeIs('blasts.*') ? 'active bg-white bg-opacity-25 rounded' : '' }}">
                            <i class="bi bi-send me-2"></i> Blast Campaigns
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="{{ route('chats.index') }}" 
                            class="nav-link text-white {{ request()->routeIs('chats.*') ? 'active bg-white bg-opacity-25 rounded' : '' }}">
                            <i class="bi bi-chat-dots me-2"></i> Chat UI
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="#" class="nav-link text-white-50" style="opacity: 0.6; cursor: not-allowed;">
                            <i class="bi bi-robot me-2"></i> AI Training
                            <span class="badge bg-secondary ms-2" style="font-size: 0.65rem;">Soon</span>
                        </a>
                    </li>
                </ul>
                @endif

                <!-- Admin Menu -->
                @if(auth()->user()->isAdmin())
                <ul class="nav flex-column">
                    <li class="nav-item mb-2">
                        <a href="{{ route('admin.dashboard') }}" 
                           class="nav-link text-white {{ request()->routeIs('admin.dashboard') ? 'active bg-white bg-opacity-25 rounded' : '' }}">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="{{ route('admin.users.index') }}" 
                           class="nav-link text-white {{ request()->routeIs('admin.users.*') ? 'active bg-white bg-opacity-25 rounded' : '' }}">
                            <i class="bi bi-people me-2"></i> Users
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="{{ route('admin.settings.index') }}" 
                           class="nav-link text-white {{ request()->routeIs('admin.settings.*') ? 'active bg-white bg-opacity-25 rounded' : '' }}">
                            <i class="bi bi-gear me-2"></i> Settings
                        </a>
                    </li>

                    <li class="nav-item mb-2">
                        <hr class="text-white-50 my-2">
                    </li>

                    <li class="nav-item mb-2">
                        <a href="{{ route('admin.accounts.index') }}" 
                           class="nav-link text-white {{ request()->routeIs('admin.accounts.*') ? 'active bg-white bg-opacity-25 rounded' : '' }}">
                            <i class="bi bi-phone me-2"></i> All Accounts
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="#" class="nav-link text-white-50" style="opacity: 0.6; cursor: not-allowed;">
                            <i class="bi bi-activity me-2"></i> System Monitor
                            <span class="badge bg-secondary ms-2" style="font-size: 0.65rem;">Soon</span>
                        </a>
                    </li>
                    <li class="nav-item mb-2">
                        <a href="#" class="nav-link text-white-50" style="opacity: 0.6; cursor: not-allowed;">
                            <i class="bi bi-clock-history me-2"></i> Audit Logs
                            <span class="badge bg-secondary ms-2" style="font-size: 0.65rem;">Soon</span>
                        </a>
                    </li>
                </ul>
                @endif
            </div>

            <!-- User Info & Logout (di bagian bawah sidebar) -->
            <div class="mt-auto">
                <hr class="text-white-50">
                <div class="d-flex align-items-center mb-3">
                    <img src="{{ auth()->user()->avatar ?? 'https://ui-avatars.com/api/?name='.urlencode(auth()->user()->name).'&background=25D366&color=fff' }}" 
                         alt="Avatar" 
                         class="rounded-circle me-2 border border-2 border-white" 
                         width="40" 
                         height="40">
                    <div class="flex-grow-1">
                        <div class="small fw-bold text-truncate" style="max-width: 140px;">
                            {{ auth()->user()->name }}
                        </div>
                        <div class="small text-white-50 text-truncate" style="max-width: 140px;">
                            {{ auth()->user()->email }}
                        </div>
                    </div>
                </div>
                
                @if(auth()->user()->isUser())
                <div class="card bg-white bg-opacity-10 border-0 mb-3">
                    <div class="card-body py-2 px-3">
                        <div class="small text-white-50 mb-1">Message Quota</div>
                        <div class="d-flex justify-content-between align-items-center">
                            <span class="fw-bold">
                                {{ number_format(auth()->user()->message_quota - auth()->user()->message_used) }}
                            </span>
                            <span class="text-white-50 small">
                                / {{ number_format(auth()->user()->message_quota) }}
                            </span>
                        </div>
                        <div class="progress mt-2" style="height: 4px;">
                            @php
                                $percentage = auth()->user()->message_quota > 0 
                                    ? ((auth()->user()->message_quota - auth()->user()->message_used) / auth()->user()->message_quota) * 100 
                                    : 0;
                            @endphp
                            <div class="progress-bar bg-white" 
                                 role="progressbar" 
                                 style="width: {{ $percentage }}%"
                                 aria-valuenow="{{ $percentage }}" 
                                 aria-valuemin="0" 
                                 aria-valuemax="100">
                            </div>
                        </div>
                    </div>
                </div>
                @endif

                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="btn btn-outline-light btn-sm w-100">
                        <i class="bi bi-box-arrow-right me-2"></i> Logout
                    </button>
                </form>
            </div>
        </nav>
        @endauth
        {{-- ====================================================== --}}
        {{-- AKHIR PERBAIKAN --}}
        {{-- ====================================================== --}}


        <!-- Main Content -->
        <main class="flex-grow-1">
            <!-- Top Bar -->
            <div class="bg-white border-bottom p-3 mb-4 shadow-sm">
                <div class="container-fluid d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">@yield('page-title', 'Dashboard')</h5>
                    @auth
                    <div class="text-muted small">
                        <i class="bi bi-calendar3 me-2"></i>
                        {{ now()->format('l, d F Y') }}
                    </div>
                    @endauth
                </div>
            </div>

            <!-- Content -->
            <div class="container-fluid px-4 pb-4">
                <!-- Alerts -->
                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill me-2"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill me-2"></i>
                    <strong>Please fix the following errors:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    @stack('scripts')
</body>
</html>
