<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\User;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    /**
     * Display a listing of all WhatsApp accounts (Admin only)
     */
    public function index(Request $request)
    {
        $query = Account::with('user');

        // Filter by user
        if ($request->filled('user_id')) {
            $query->where('user_id', $request->user_id);
        }

        // Filter by status
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        // Search
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone_number', 'like', "%{$search}%")
                  ->orWhere('session_id', 'like', "%{$search}%");
            });
        }

        $accounts = $query->latest()->paginate(15);

        // Stats
        $stats = [
            'total' => Account::count(),
            'connected' => Account::where('status', 'connected')->count(),
            'disconnected' => Account::where('status', 'disconnected')->count(),
            'users' => User::where('role', 'user')->count(),
        ];

        // Users for filter
        $users = User::where('role', 'user')->orderBy('name')->get();

        return view('admin.accounts.index', compact('accounts', 'stats', 'users'));
    }

    /**
     * Display the specified account
     */
    public function show(Account $account)
    {
        $account->load('user');
        
        return view('admin.accounts.show', compact('account'));
    }

    /**
     * Force disconnect account (Admin action)
     */
    public function forceDisconnect(Account $account)
    {
        try {
            $account->update([
                'status' => 'disconnected',
                'qr_code' => null,
            ]);

            return back()->with('success', 'Account has been force disconnected.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to disconnect account: ' . $e->getMessage());
        }
    }

    /**
     * Delete account (Admin action)
     */
    public function destroy(Account $account)
    {
        try {
            $account->delete();

            return redirect()
                ->route('admin.accounts.index')
                ->with('success', 'Account has been deleted.');
        } catch (\Exception $e) {
            return back()->with('error', 'Failed to delete account: ' . $e->getMessage());
        }
    }
}