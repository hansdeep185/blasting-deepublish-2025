<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ config('app.name') }} - @yield('title')</title>
    
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
</head>
<body>
    <div class="d-flex">
        <!-- Sidebar -->
        <nav class="sidebar text-white p-3" style="width: 250px;">
            <div class="mb-4">
                <h4 class="fw-bold">
                    <i class="bi bi-whatsapp"></i>
                    WA Blast
                </h4>
                <small class="text-white-50">{{ auth()->user()->role === 'admin' ? 'Admin Panel' : 'User Dashboard' }}</small>
            </div>

            <hr class="text-white-50">

            <!-- User Menu -->
            @if(auth()->user()->isUser())
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a href="{{ route('dashboard') }}" class="nav-link text-white {{ request()->routeIs('dashboard') ? 'active bg-white bg-opacity-25 rounded' : '' }}">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ route('accounts.index') }}" class="nav-link text-white {{ request()->routeIs('accounts.*') ? 'active bg-white bg-opacity-25 rounded' : '' }}">
                        <i class="bi bi-phone"></i> WA Accounts
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ route('contact-lists.index') }}" class="nav-link text-white {{ request()->routeIs('contact-lists.*') || request()->routeIs('contacts.*') ? 'active bg-white bg-opacity-25 rounded' : '' }}">
                        <i class="bi bi-people"></i> Contacts
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ route('templates.index') }}" class="nav-link text-white {{ request()->routeIs('templates.*') ? 'active bg-white bg-opacity-25 rounded' : '' }}">
                        <i class="bi bi-file-text"></i> Templates
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ route('blasts.index') }}" class="nav-link text-white {{ request()->routeIs('blasts.*') ? 'active bg-white bg-opacity-25 rounded' : '' }}">
                        <i class="bi bi-send"></i> Blast Campaigns
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ route('chats.index') }}" class="nav-link text-white {{ request()->routeIs('chats.*') ? 'active bg-white bg-opacity-25 rounded' : '' }}">
                        <i class="bi bi-chat-dots"></i> Chat UI
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ route('training.index') }}" class="nav-link text-white {{ request()->routeIs('training.*') ? 'active bg-white bg-opacity-25 rounded' : '' }}">
                        <i class="bi bi-robot"></i> AI Training
                    </a>
                </li>
            </ul>
            @endif

            <!-- Admin Menu -->
            @if(auth()->user()->isAdmin())
            <ul class="nav flex-column">
                <li class="nav-item mb-2">
                    <a href="{{ route('admin.dashboard') }}" class="nav-link text-white {{ request()->routeIs('admin.dashboard') ? 'active bg-white bg-opacity-25 rounded' : '' }}">
                        <i class="bi bi-speedometer2"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ route('admin.users.index') }}" class="nav-link text-white {{ request()->routeIs('admin.users.*') ? 'active bg-white bg-opacity-25 rounded' : '' }}">
                        <i class="bi bi-people"></i> Users
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ route('admin.accounts.index') }}" class="nav-link text-white {{ request()->routeIs('admin.accounts.*') ? 'active bg-white bg-opacity-25 rounded' : '' }}">
                        <i class="bi bi-phone"></i> All Accounts
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ route('admin.monitor.index') }}" class="nav-link text-white {{ request()->routeIs('admin.monitor.*') ? 'active bg-white bg-opacity-25 rounded' : '' }}">
                        <i class="bi bi-activity"></i> System Monitor
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ route('admin.audit-logs.index') }}" class="nav-link text-white {{ request()->routeIs('admin.audit-logs.*') ? 'active bg-white bg-opacity-25 rounded' : '' }}">
                        <i class="bi bi-clock-history"></i> Audit Logs
                    </a>
                </li>
                <li class="nav-item mb-2">
                    <a href="{{ route('admin.settings.index') }}" class="nav-link text-white {{ request()->routeIs('admin.settings.*') ? 'active bg-white bg-opacity-25 rounded' : '' }}">
                        <i class="bi bi-gear"></i> Settings
                    </a>
                </li>
            </ul>
            @endif

            <hr class="text-white-50 mt-4">

            <!-- User Info & Logout -->
            <div class="mt-auto">
                <div class="d-flex align-items-center mb-3">
                    <img src="{{ auth()->user()->avatar ?? 'https://ui-avatars.com/api/?name='.urlencode(auth()->user()->name) }}" 
                         alt="Avatar" 
                         class="rounded-circle me-2" 
                         width="40" 
                         height="40">
                    <div class="flex-grow-1">
                        <div class="small fw-bold">{{ auth()->user()->name }}</div>
                        <div class="small text-white-50">{{ auth()->user()->email }}</div>
                    </div>
                </div>
                
                @if(auth()->user()->isUser())
                <div class="small text-white-50 mb-2">
                    Quota: {{ number_format(auth()->user()->message_quota - auth()->user()->message_used) }} / {{ number_format(auth()->user()->message_quota) }}
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
        <main class="flex-grow-1">
            <!-- Top Bar -->
            <div class="bg-white border-bottom p-3 mb-4">
                <div class="container-fluid">
                    <h5 class="mb-0">@yield('page-title', 'Dashboard')</h5>
                </div>
            </div>

            <!-- Content -->
            <div class="container-fluid px-4">
                <!-- Alerts -->
                @if(session('success'))
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="bi bi-check-circle"></i> {{ session('success') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                @if(session('error'))
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i> {{ session('error') }}
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                @endif

                @if($errors->any())
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="bi bi-exclamation-triangle"></i>
                    <ul class="mb-0">
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