<?php
// app/Http/Middleware/DepartmentAdminMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DepartmentAdminMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        // Check if user is authenticated
        if (!auth()->check()) {
            return redirect()->route('login');
        }

        $user = auth()->user();
        
        // Check if user is a department admin (is_admin = true AND has department assigned)
        if (!$user->is_admin || !$user->department_id) {
            return redirect()->route('dashboard')->with('error', 'Access denied. Department admin privileges required.');
        }

        return $next($request);
    }
}