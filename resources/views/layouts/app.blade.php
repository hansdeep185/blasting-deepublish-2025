<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} - @yield('title', 'Dashboard')</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
    <div class="d-flex" style="min-height: 100vh;">
        <!-- Sidebar -->
        <nav class="sidebar text-white p-3" style="width: 250px; min-height: 100vh; background: linear-gradient(180deg, #25D366 0%, #128C7E 100%);">
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
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ route('accounts.index') }}" 
                       class="nav-link text-white {{ request()->routeIs('accounts.*') ? 'active bg-white bg-opacity-25 rounded' : '' }}">
                        <i class="bi bi-phone"></i> WA Accounts
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ route('contact-lists.index') }}" 
                       class="nav-link text-white {{ request()->routeIs('contact-lists.*') || request()->routeIs('contacts.*') || request()->routeIs('contact-tags.*') ? 'active bg-white bg-opacity-25 rounded' : '' }}">
                        <i class="bi bi-people"></i> Contacts
                    </a>
                </li>
                
                <li class="nav-item mb-2">
                    <hr class="text-white-50 my-2">
                </li>

                <!-- Templates - NOW ACTIVE -->
                <li class="nav-item mb-2">
                    <a href="{{ route('templates.index') }}" 
                       class="nav-link text-white {{ request()->routeIs('templates.*') ? 'active bg-white bg-opacity-25 rounded' : '' }}">
                        <i class="bi bi-file-text"></i> Templates
                    </a>
                </li>

                <!-- Coming Soon Routes -->
                <li class="nav-item mb-2">
                    <a href="{{ url('/blasts') }}" 
                       class="nav-link text-white-50 {{ request()->is('blasts*') ? 'active bg-white bg-opacity-25 rounded' : '' }}"
                       style="opacity: 0.6;">
                        <i class="bi bi-send"></i> Blast Campaigns
                        <span class="badge bg-secondary ms-2" style="font-size: 0.65rem;">Soon</span>
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ url('/chats') }}" 
                       class="nav-link text-white-50 {{ request()->is('chats*') ? 'active bg-white bg-opacity-25 rounded' : '' }}"
                       style="opacity: 0.6;">
                        <i class="bi bi-chat-dots"></i> Chat UI
                        <span class="badge bg-secondary ms-2" style="font-size: 0.65rem;">Soon</span>
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ url('/training') }}" 
                       class="nav-link text-white-50 {{ request()->is('training*') ? 'active bg-white bg-opacity-25 rounded' : '' }}"
                       style="opacity: 0.6;">
                        <i class="bi bi-robot"></i> AI Training
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
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ route('admin.users.index') }}" 
                       class="nav-link text-white {{ request()->routeIs('admin.users.*') ? 'active bg-white bg-opacity-25 rounded' : '' }}">
                        <i class="bi bi-people"></i> Users
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ route('admin.settings.index') }}" 
                       class="nav-link text-white {{ request()->routeIs('admin.settings.*') ? 'active bg-white bg-opacity-25 rounded' : '' }}">
                        <i class="bi bi-gear"></i> Settings
                    </a>
                </li>

                <li class="nav-item mb-2">
                    <hr class="text-white-50 my-2">
                </li>

                <!-- Admin - All Accounts - NOW ACTIVE -->
                <li class="nav-item mb-2">
                    <a href="{{ route('admin.accounts.index') }}" 
                       class="nav-link text-white {{ request()->routeIs('admin.accounts.*') ? 'active bg-white bg-opacity-25 rounded' : '' }}">
                        <i class="bi bi-phone"></i> All Accounts
                    </a>
                </li>

                <!-- Coming Soon Admin Routes -->
                <li class="nav-item mb-2">
                    <a href="{{ url('/admin/monitor') }}" 
                       class="nav-link text-white-50 {{ request()->is('admin/monitor*') ? 'active bg-white bg-opacity-25 rounded' : '' }}"
                       style="opacity: 0.6;">
                        <i class="bi bi-activity"></i> System Monitor
                        <span class="badge bg-secondary ms-2" style="font-size: 0.65rem;">Soon</span>
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ url('/admin/audit-logs') }}" 
                       class="nav-link text-white-50 {{ request()->is('admin/audit-logs*') ? 'active bg-white bg-opacity-25 rounded' : '' }}"
                       style="opacity: 0.6;">
                        <i class="bi bi-clock-history"></i> Audit Logs
                        <span class="badge bg-secondary ms-2" style="font-size: 0.65rem;">Soon</span>
                    </a>
                </li>
            </ul>
            @endif

            <hr class="text-white-50 mt-4">

            <!-- User Info & Logout -->
            <div class="mt-auto">
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
                        <i class="bi bi-box-arrow-right"></i> Logout
                    </button>
                </form>
            </div>
        </nav>

        <!-- Main Content -->
        <main class="flex-grow-1 bg-light">
            <!-- Top Bar -->
            <div class="bg-white border-bottom p-3 mb-4 shadow-sm">
                <div class="container-fluid">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">@yield('page-title', 'Dashboard')</h5>
                        <div class="text-muted small">
                            <i class="bi bi-calendar3"></i>
                            {{ now()->format('l, d F Y') }}
                        </div>
                    </div>
                </div>
            </div>

            <!-- Content -->
            <div class="container-fluid px-4 pb-4">
                <!-- Alerts -->
                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi bi-check-circle-fill"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
                    <i class="bi bi-exclamation-triangle-fill"></i>
                    <strong>Please fix the following errors:</strong>
                    <ul class="mb-0 mt-2">
                        @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                @yield('content')
            </div>
        </main>
    </div>

    @stack('scripts')
</body>
</html>