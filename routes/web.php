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
    Route::resource('accounts', App\Http\Controllers\AccountController::class)->where(['account' => '[0-9]+']);
    Route::post('/accounts/{account}/reconnect', [App\Http\Controllers\AccountController::class, 'reconnect'])
        ->name('accounts.reconnect');
    Route::post('/accounts/{account}/toggle-ai', [App\Http\Controllers\AccountController::class, 'toggleAi'])
        ->name('accounts.toggle-ai');
    // Contact Lists
    Route::resource('contact-lists', App\Http\Controllers\ContactListController::class);
    /*
    // Contacts
    Route::prefix('contact-lists/{contactList}')->name('contacts.')->group(function () {
        Route::get('/contacts', [App\Http\Controllers\ContactController::class, 'index'])->name('index');
        Route::get('/contacts/create', [App\Http\Controllers\ContactController::class, 'create'])->name('create');
        Route::post('/contacts', [App\Http\Controllers\ContactController::class, 'store'])->name('store');
        Route::post('/contacts/import', [App\Http\Controllers\ContactController::class, 'import'])->name('import');
        Route::get('/contacts/{contact}/edit', [App\Http\Controllers\ContactController::class, 'edit'])->name('edit');
        Route::put('/contacts/{contact}', [App\Http\Controllers\ContactController::class, 'update'])->name('update');
        Route::delete('/contacts/{contact}', [App\Http\Controllers\ContactController::class, 'destroy'])->name('destroy');
    });
    
    // Placeholder routes - akan diimplementasikan di fase selanjutnya
    Route::get('/contact-lists', fn() => view('under-construction'))->name('contact-lists.index');
    Route::get('/templates', fn() => view('under-construction'))->name('templates.index');
    Route::get('/blasts', fn() => view('under-construction'))->name('blasts.index');
    Route::get('/blasts/create', fn() => view('under-construction'))->name('blasts.create');
    Route::get('/chats', fn() => view('under-construction'))->name('chats.index');
    Route::get('/chats/{account}', fn() => view('under-construction'))->name('chats.show');
    Route::get('/training', fn() => view('under-construction'))->name('training.index');
    */
    Route::prefix('contact-lists/{contactList}')->group(function () {
        
        // Contact Tags
        Route::get('/tags', [App\Http\Controllers\ContactTagController::class, 'index'])->name('contact-tags.index');
        Route::post('/tags', [App\Http\Controllers\ContactTagController::class, 'store'])->name('contact-tags.store');
        Route::put('/tags/{contactTag}', [App\Http\Controllers\ContactTagController::class, 'update'])->name('contact-tags.update');
        Route::delete('/tags/{contactTag}', [App\Http\Controllers\ContactTagController::class, 'destroy'])->name('contact-tags.destroy');
        Route::post('/bulk-tag', [App\Http\Controllers\ContactTagController::class, 'bulkTag'])->name('contact-tags.bulk-tag');
        
        // Contacts - update yang sudah ada
        Route::get('/contacts', [App\Http\Controllers\ContactController::class, 'index'])->name('contacts.index');
        Route::get('/contacts/create', [App\Http\Controllers\ContactController::class, 'create'])->name('contacts.create');
        Route::post('/contacts', [App\Http\Controllers\ContactController::class, 'store'])->name('contacts.store');
        Route::get('/contacts/import-form', [App\Http\Controllers\ContactController::class, 'importForm'])->name('contacts.import.form');
        Route::post('/contacts/import', [App\Http\Controllers\ContactController::class, 'import'])->name('contacts.import');
        Route::get('/contacts/download-template', [App\Http\Controllers\ContactController::class, 'downloadTemplate'])->name('contacts.template');
        Route::post('/contacts/bulk-delete', [App\Http\Controllers\ContactController::class, 'bulkDelete'])->name('contacts.bulk-delete');
        Route::get('/contacts/{contact}/edit', [App\Http\Controllers\ContactController::class, 'edit'])->name('contacts.edit');
        Route::put('/contacts/{contact}', [App\Http\Controllers\ContactController::class, 'update'])->name('contacts.update');
        Route::delete('/contacts/{contact}', [App\Http\Controllers\ContactController::class, 'destroy'])->name('contacts.destroy');
    });

    Route::resource('templates', App\Http\Controllers\TemplateController::class);
        Route::post('/templates/{template}/duplicate', [App\Http\Controllers\TemplateController::class, 'duplicate'])
            ->name('templates.duplicate');
        Route::post('/templates/{template}/toggle-status', [App\Http\Controllers\TemplateController::class, 'toggleStatus'])
            ->name('templates.toggle-status');
        Route::post('/templates/{template}/preview', [App\Http\Controllers\TemplateController::class, 'preview'])
            ->name('templates.preview');
    
    // Blast Campaigns
    Route::prefix('blasts')->name('blasts.')->group(function () {
        Route::get('/', [App\Http\Controllers\BlastScheduleController::class, 'index'])->name('index');
        Route::get('/create', [App\Http\Controllers\BlastScheduleController::class, 'create'])->name('create');
        Route::post('/', [App\Http\Controllers\BlastScheduleController::class, 'store'])->name('store');
        Route::get('/{blast}', [App\Http\Controllers\BlastScheduleController::class, 'show'])->name('show');
        Route::post('/{blast}/cancel', [App\Http\Controllers\BlastScheduleController::class, 'cancel'])->name('cancel');
        Route::get('/{blast}/report', [App\Http\Controllers\BlastScheduleController::class, 'report'])->name('report');
        Route::post('/preview', [App\Http\Controllers\BlastScheduleController::class, 'preview'])->name('preview');
    });

    Route::prefix('chats')->name('chats.')->group(function () {
        Route::get('/', [App\Http\Controllers\ChatController::class, 'index'])->name('index');
        Route::get('/{chat}/messages/sync', [App\Http\Controllers\ChatController::class, 'syncMessages'])->name('messages.sync');
        Route::get('/{account}', [App\Http\Controllers\ChatController::class, 'show'])->name('show');
        Route::get('/{account}/{chat}', [App\Http\Controllers\ChatController::class, 'show'])->name('conversation');
        Route::post('/{account}/send', [App\Http\Controllers\ChatController::class, 'send'])->name('send');
        Route::get('/{chat}/messages', [App\Http\Controllers\ChatController::class, 'loadMessages'])->name('messages');
        
        Route::get('/{chat}/messages/new', [App\Http\Controllers\ChatController::class, 'getNewMessages'])->name('messages.new'); // 👈 TAMBAHKAN INI
        Route::post('/{chat}/archive', [App\Http\Controllers\ChatController::class, 'archive'])->name('archive');
        Route::post('/{chat}/unarchive', [App\Http\Controllers\ChatController::class, 'unarchive'])->name('unarchive');
        Route::delete('/{chat}', [App\Http\Controllers\ChatController::class, 'destroy'])->name('destroy');
    });
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

    // Admin - All Accounts Management
    Route::prefix('accounts')->name('accounts.')->group(function () {
        Route::get('/', [App\Http\Controllers\Admin\AccountController::class, 'index'])->name('index');
        Route::get('/{account}', [App\Http\Controllers\Admin\AccountController::class, 'show'])->name('show');
        Route::post('/{account}/force-disconnect', [App\Http\Controllers\Admin\AccountController::class, 'forceDisconnect'])->name('force-disconnect');
        Route::delete('/{account}', [App\Http\Controllers\Admin\AccountController::class, 'destroy'])->name('destroy');
    });
    
    // Placeholder routes - akan diimplementasikan di fase selanjutnya
    //Route::get('/accounts', fn() => view('under-construction'))->name('accounts.index');
    Route::get('/audit-logs', fn() => view('under-construction'))->name('audit-logs.index');
    Route::get('/monitor', fn() => view('under-construction'))->name('monitor.index');

    // Di dalam admin routes group, tambahkan:
    Route::post('/settings/test-waha', [App\Http\Controllers\Admin\SettingController::class, 'testWaha'])->name('settings.test-waha');
    Route::post('/settings/test-n8n', [App\Http\Controllers\Admin\SettingController::class, 'testN8n'])->name('settings.test-n8n');
});