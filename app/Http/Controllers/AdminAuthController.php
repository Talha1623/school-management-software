<?php

namespace App\Http\Controllers;

use App\Models\AdminRole;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    /**
     * Show the admin login form.
     */
    public function showLoginForm(): View
    {
        // If already logged in, redirect to dashboard
        if (Auth::guard('admin')->check()) {
            return redirect()->route('dashboard');
        }
        
        return view('admin.login');
    }

    /**
     * Handle admin login.
     */
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        // Find admin by email
        $admin = AdminRole::where('email', $credentials['email'])->first();

        if (!$admin) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        // Check password
        if (!Hash::check($credentials['password'], $admin->password)) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        // Check if super admin
        if (!$admin->isSuperAdmin()) {
            return back()->withErrors([
                'email' => 'Only Super Admins can access this login.',
            ])->onlyInput('email');
        }

        // Login the admin
        Auth::guard('admin')->login($admin, $request->filled('remember'));

        $request->session()->regenerate();

        return redirect()->intended(route('dashboard'));
    }

    /**
     * Handle admin logout.
     */
    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }

    /**
     * Show admin dashboard.
     */
    public function dashboard(): View
    {
        $admin = Auth::guard('admin')->user();
        
        return view('admin.dashboard', compact('admin'));
    }
}
