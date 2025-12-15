<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class AdminAuthController extends Controller
{
    /**
     * Show admin login form
     */
    public function showLoginForm()
    {
        return view('admin.auth.login');
    }

    /**
     * Handle admin login
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Check if user exists and is admin
        $user = \App\Models\User::where('email', $request->email)->first();

        if (!$user || !$user->is_admin) {
            throw ValidationException::withMessages([
                'email' => ['You do not have admin access.'],
            ]);
        }

        // Attempt login
        if (Auth::attempt($request->only('email', 'password'), $request->filled('remember'))) {
            $request->session()->regenerate();

            // Log admin activity
            \App\Models\AdminActivityLog::logActivity(
                'admin_login',
                'Admin logged in',
                \App\Models\User::class,
                Auth::id()
            );

            return redirect()->intended(route('filament.admin.pages.dashboard'));
        }

        throw ValidationException::withMessages([
            'email' => ['The provided credentials do not match our records.'],
        ]);
    }

    /**
     * Handle admin logout
     */
    public function logout(Request $request)
    {
        // Log admin activity
        \App\Models\AdminActivityLog::logActivity(
            'admin_logout',
            'Admin logged out',
            \App\Models\User::class,
            Auth::id()
        );

        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}