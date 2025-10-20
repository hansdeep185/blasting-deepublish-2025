<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Laravel\Socialite\Facades\Socialite;
use Exception;

class GoogleAuthController extends Controller
{
    /**
     * Redirect to Google OAuth
     */
    public function redirect()
    {
        return Socialite::driver('google')->redirect();
    }

    /**
     * Handle Google OAuth callback
     */
    public function callback()
    {
        try {
            $googleUser = Socialite::driver('google')->user();
            
            // Find or create user
            $user = User::where('google_id', $googleUser->id)
                ->orWhere('email', $googleUser->email)
                ->first();

            if ($user) {
                // Update existing user
                $user->update([
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                    'name' => $googleUser->name ?? $user->name,
                ]);
            } else {
                // Create new user
                // First user becomes admin, others become regular users
                $isFirstUser = User::count() === 0;
                
                $user = User::create([
                    'name' => $googleUser->name,
                    'email' => $googleUser->email,
                    'google_id' => $googleUser->id,
                    'avatar' => $googleUser->avatar,
                    'email_verified_at' => now(),
                    'role' => $isFirstUser ? 'admin' : 'user', // First user = admin
                    'message_quota' => $isFirstUser ? 999999 : 10000,
                ]);
            }

            // Check if user is active
            if (!$user->is_active) {
                return redirect()->route('login')
                    ->with('error', 'Your account has been deactivated. Please contact administrator.');
            }

            // Login user
            Auth::login($user, true);

            // Log activity
            AuditLog::logActivity(
                action: 'login',
                description: 'User logged in via Google OAuth'
            );

            // Redirect based on role
            if ($user->isAdmin()) {
                return redirect()->route('admin.dashboard')
                    ->with('success', 'Welcome back, ' . $user->name . '!');
            }

            return redirect()->route('dashboard')
                ->with('success', 'Welcome back, ' . $user->name . '!');

        } catch (Exception $e) {
            return redirect()->route('login')
                ->with('error', 'Unable to login with Google. Please try again. Error: ' . $e->getMessage());
        }
    }

    /**
     * Logout user
     */
    public function logout()
    {
        AuditLog::logActivity(
            action: 'logout',
            description: 'User logged out'
        );

        Auth::logout();
        request()->session()->invalidate();
        request()->session()->regenerateToken();

        return redirect()->route('login')
            ->with('success', 'You have been logged out successfully.');
    }
}