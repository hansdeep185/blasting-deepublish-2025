<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        // DEBUG: Tampilkan info
        if (app()->environment('local')) {
            logger('RoleMiddleware Debug', [
                'required_role' => $role,
                'user_role' => auth()->user()->role,
                'user_email' => auth()->user()->email,
            ]);
        }

        if (auth()->user()->role !== $role) {
            // Redirect ke dashboard yang sesuai dengan role mereka
            if (auth()->user()->isAdmin()) {
                return redirect()->route('admin.dashboard')
                    ->with('error', 'You are already on the admin dashboard.');
            }
            
            if (auth()->user()->isUser()) {
                return redirect()->route('dashboard')
                    ->with('error', 'You are already on the user dashboard.');
            }
            
            abort(403, 'Unauthorized access. Your role: ' . auth()->user()->role . ', Required: ' . $role);
        }

        return $next($request);
    }
}