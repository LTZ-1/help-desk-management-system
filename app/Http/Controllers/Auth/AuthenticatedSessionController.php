<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;
use Inertia\Response;

class AuthenticatedSessionController extends Controller
{
    /**
     * Show the login page.
     */
    public function create(Request $request): Response
    {
        return Inertia::render('auth/login', [
            'canResetPassword' => Route::has('password.request'),
            'status' => $request->session()->get('status'),
        ]);
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse|\Illuminate\Http\JsonResponse
    {
        $request->authenticate();

        $request->session()->regenerate();
         $user = $request->user();
    
     // Return user data with role information
     // Check if request wants JSON (API call)
     if ($request->wantsJson()) {
        return response()->json([
            'user' => $user->load('department'),
            'is_admin' => $user->is_admin,
            'is_resolver' => $user->is_resolver,
            'department' => $user->department,
            'redirect' => route('dashboard')
        ]);
        
     }
 

     // Web request - redirect
     session([
        'user_data' => [
            'is_admin' => $user->is_admin,
            'is_resolver' => $user->is_resolver,
            'department' => $user->department
        ]
     ]);

     return redirect()->intended(route('dashboard', absolute: false));
    }
    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect('/');
    }
}
