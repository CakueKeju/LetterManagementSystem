<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserActive
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (Auth::check()) {
            $user = Auth::user();
            
            // Admin users are exempt from active check
            if ($user->isAdmin()) {
                return $next($request);
            }
            
            // Check if user is not active
            if (!$user->is_active) {
                // Log the user out
                Auth::logout();
                
                // Invalidate the session
                $request->session()->invalidate();
                $request->session()->regenerateToken();
                
                // Redirect to login with error message
                return redirect()->route('login')->withErrors([
                    'email' => 'Akun Anda telah dinonaktifkan. Silakan hubungi administrator.',
                ]);
            }
        }
        
        return $next($request);
    }
}
