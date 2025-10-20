<?php

use App\Http\Controllers\Auth\GoogleAuthController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\SettingController;
use Illuminate\Support\Facades\Route;

// Guest routes
Route::middleware('guest')->group(function () {
    Route::get('/', function () {
        return view('auth.login');
    })->name('login');
    
    Route::get('/auth/google', [GoogleAuthController::class, 'redirect'])
        ->name('auth.google');
    
    Route::get('/auth/google/callback', [GoogleAuthController::class, 'callback'])
        ->name('auth.google.callback');
});

// Authenticated routes
Route::middleware('auth')->group(function () {
    Route::post('/logout', [GoogleAuthController::class, 'logout'])
        ->name('logout');
    
    // Auto redirect to correct dashboard
    Route::get('/home', function () {
        if (auth()->user()->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }
        return redirect()->route('dashboard');
    })->name('home');
});

// User routes
Route::middleware(['auth', 'role:user'])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
    
    // Account Management
    Route::resource('accounts', App\Http\Controllers\AccountController::class);
    Route::post('/accounts/{account}/reconnect', [App\Http\Controllers\AccountController::class, 'reconnect'])
        ->name('accounts.reconnect');
    Route::post('/accounts/{account}/toggle-ai', [App\Http\Controllers\AccountController::class, 'toggleAi'])
        ->name('accounts.toggle-ai');
    
    // Placeholder routes - akan diimplementasikan di fase selanjutnya
    Route::get('/contact-lists', fn() => view('under-construction'))->name('contact-lists.index');
    Route::get('/templates', fn() => view('under-construction'))->name('templates.index');
    Route::get('/blasts', fn() => view('under-construction'))->name('blasts.index');
    Route::get('/blasts/create', fn() => view('under-construction'))->name('blasts.create');
    Route::get('/chats', fn() => view('under-construction'))->name('chats.index');
    Route::get('/chats/{account}', fn() => view('under-construction'))->name('chats.show');
    Route::get('/training', fn() => view('under-construction'))->name('training.index');
});

// Admin routes
Route::middleware(['auth', 'role:admin'])->prefix('admin')->name('admin.')->group(function () {
    Route::get('/dashboard', [AdminDashboardController::class, 'index'])
        ->name('dashboard');
    
    // User Management
    Route::resource('users', App\Http\Controllers\Admin\UserController::class);
    Route::post('/users/{user}/toggle-status', [App\Http\Controllers\Admin\UserController::class, 'toggleStatus'])
        ->name('users.toggle-status');
    Route::post('/users/{user}/reset-quota', [App\Http\Controllers\Admin\UserController::class, 'resetQuota'])
        ->name('users.reset-quota');
    
    // Settings Management
    Route::get('/settings', [SettingController::class, 'index'])->name('settings.index');
    Route::post('/settings', [SettingController::class, 'update'])->name('settings.update');
    Route::post('/settings/test-waha', [SettingController::class, 'testWaha'])->name('settings.test-waha');
    Route::post('/settings/test-n8n', [SettingController::class, 'testN8n'])->name('settings.test-n8n');
    
    // Placeholder routes - akan diimplementasikan di fase selanjutnya
    Route::get('/accounts', fn() => view('under-construction'))->name('accounts.index');
    Route::get('/audit-logs', fn() => view('under-construction'))->name('audit-logs.index');
    Route::get('/monitor', fn() => view('under-construction'))->name('monitor.index');

    // Di dalam admin routes group, tambahkan:
    Route::post('/settings/test-waha', [App\Http\Controllers\Admin\SettingController::class, 'testWaha'])->name('settings.test-waha');
    Route::post('/settings/test-n8n', [App\Http\Controllers\Admin\SettingController::class, 'testN8n'])->name('settings.test-n8n');
});