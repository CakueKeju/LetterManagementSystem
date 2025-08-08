<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckUserActive
{
    // ================================= CEK USER AKTIF =================================
    
    public function handle(Request $request, Closure $next): Response
    {
        // cek user udah login belum
        if (Auth::check()) {
            $user = Auth::user();
            
            // admin bebas lewat
            if ($user->isAdmin()) {
                return $next($request);
            }
            
            // cek user aktif atau ngga
            if (!$user->is_active) {
                // logout user
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
