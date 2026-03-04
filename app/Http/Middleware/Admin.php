<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class Admin
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        
        // Check if user is authenticated and is an admin
        if (!$user || !$user->is_admin) {
            return redirect()->route('dashboard')->with('error', 'Administrator access required.');
        }
        
        return $next($request);
    }
}