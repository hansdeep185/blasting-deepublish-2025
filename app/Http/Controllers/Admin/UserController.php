<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    /**
     * Display a listing of users
     */
    public function index()
    {
        $users = User::where('role', 'user')
            ->withCount(['accounts', 'contactLists', 'blastSchedules'])
            ->latest()
            ->paginate(20);

        $stats = [
            'total' => User::where('role', 'user')->count(),
            'active' => User::where('role', 'user')->where('is_active', true)->count(),
            'inactive' => User::where('role', 'user')->where('is_active', false)->count(),
        ];

        return view('admin.users.index', compact('users', 'stats'));
    }

    /**
     * Show the form for creating a new user
     */
    public function create()
    {
        return view('admin.users.create');
    }

    /**
     * Store a newly created user
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email',
            'message_quota' => 'required|integer|min:0',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'role' => 'user',
            'message_quota' => $request->message_quota,
            'message_used' => 0,
            'is_active' => $request->has('is_active') ? true : false,
            'email_verified_at' => now(),
        ]);

        AuditLog::logActivity(
            action: 'create_user',
            description: "Created user: {$user->name}",
            modelType: User::class,
            modelId: $user->id
        );

        return redirect()->route('admin.users.index')
            ->with('success', 'User created successfully! User can login with Google using their email.');
    }

    /**
     * Display the specified user
     */
    public function show(User $user)
    {
        $user->load(['accounts', 'contactLists', 'blastSchedules', 'trainingData']);
        
        $stats = [
            'accounts' => $user->accounts()->count(),
            'contact_lists' => $user->contactLists()->count(),
            'total_contacts' => $user->contactLists()->withCount('contacts')->get()->sum('contacts_count'),
            'blasts' => $user->blastSchedules()->count(),
            'training_data' => $user->trainingData()->count(),
        ];

        return view('admin.users.show', compact('user', 'stats'));
    }

    /**
     * Show the form for editing user
     */
    public function edit(User $user)
    {
        return view('admin.users.edit', compact('user'));
    }

    /**
     * Update the specified user
     */
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => ['required', 'email', Rule::unique('users')->ignore($user->id)],
            'message_quota' => 'required|integer|min:0',
        ]);

        $oldValues = $user->only(['name', 'email', 'message_quota', 'is_active']);

        $user->update([
            'name' => $request->name,
            'email' => $request->email,
            'message_quota' => $request->message_quota,
            'is_active' => $request->has('is_active') ? true : false,
        ]);

        AuditLog::logActivity(
            action: 'update_user',
            description: "Updated user: {$user->name}",
            modelType: User::class,
            modelId: $user->id,
            oldValues: $oldValues,
            newValues: $user->only(['name', 'email', 'message_quota', 'is_active'])
        );

        return redirect()->route('admin.users.index')
            ->with('success', 'User updated successfully!');
    }

    /**
     * Remove the specified user
     */
    public function destroy(User $user)
    {
        // Prevent deleting admin
        if ($user->isAdmin()) {
            return back()->with('error', 'Cannot delete admin user!');
        }

        // Prevent self-deletion
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot delete yourself!');
        }

        AuditLog::logActivity(
            action: 'delete_user',
            description: "Deleted user: {$user->name}",
            modelType: User::class,
            modelId: $user->id
        );

        $user->delete();

        return redirect()->route('admin.users.index')
            ->with('success', 'User deleted successfully!');
    }

    /**
     * Toggle user active status
     */
    public function toggleStatus(User $user)
    {
        // Prevent toggling admin
        if ($user->isAdmin()) {
            return back()->with('error', 'Cannot change admin status!');
        }

        // Prevent self-toggle
        if ($user->id === auth()->id()) {
            return back()->with('error', 'You cannot change your own status!');
        }

        $user->is_active = !$user->is_active;
        $user->save();

        AuditLog::logActivity(
            action: 'toggle_user_status',
            description: "Changed user status to " . ($user->is_active ? 'active' : 'inactive'),
            modelType: User::class,
            modelId: $user->id
        );

        return back()->with('success', 'User status updated successfully!');
    }

    /**
     * Reset user message quota
     */
    public function resetQuota(User $user)
    {
        $oldUsed = $user->message_used;
        
        $user->update(['message_used' => 0]);

        AuditLog::logActivity(
            action: 'reset_quota',
            description: "Reset message quota for user: {$user->name}",
            modelType: User::class,
            modelId: $user->id,
            oldValues: ['message_used' => $oldUsed],
            newValues: ['message_used' => 0]
        );

        return back()->with('success', 'Message quota reset successfully!');
    }
}