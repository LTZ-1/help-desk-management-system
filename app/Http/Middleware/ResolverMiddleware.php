<?php
// app/Http/Middleware/ResolverMiddleware.php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolverMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        if (!$request->user() || !$request->user()->is_resolver) {
            return redirect()->route('dashboard')->with('error', 'Resolver access required.');
        }
        
        return $next($request);
    }
}