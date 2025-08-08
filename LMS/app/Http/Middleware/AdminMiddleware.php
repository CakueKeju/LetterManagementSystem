<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AdminMiddleware
{
    // ================================= CEK AKSES ADMIN =================================
    
    public function handle(Request $request, Closure $next)
    {
        // cek user udah login belum
        if (!Auth::check()) {
            return redirect()->route('login');
        }

        // cek user admin atau bukan
        if (!Auth::user()->isAdmin()) {
            return redirect()->route('home')->with('error', 'Akses ditolak. Lu bukan admin!');
        }

        return $next($request);
    }
} 