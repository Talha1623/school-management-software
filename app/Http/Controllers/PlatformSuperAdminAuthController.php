<?php

namespace App\Http\Controllers;

use App\Models\PlatformSchool;
use App\Models\PlatformSuperAdmin;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\View;

class PlatformSuperAdminAuthController extends Controller
{
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::guard('platform_super_admin')->check()) {
            return redirect()->route('platform-admin.dashboard');
        }

        $errors = session('errors') ?: new ViewErrorBag();

        return view('platform-super-admin.login', ['errors' => $errors]);
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        $admin = PlatformSuperAdmin::where('email', $credentials['email'])->first();

        if (!$admin || !$admin->is_active || !Hash::check($credentials['password'], $admin->password)) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        Auth::guard('platform_super_admin')->login($admin, $request->filled('remember'));
        $request->session()->regenerate();

        return redirect()->intended(route('platform-admin.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('platform_super_admin')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('platform-admin.login');
    }

    public function dashboard(): View
    {
        $admin = Auth::guard('platform_super_admin')->user();
        $totalSchools = PlatformSchool::count();
        $activeSchools = PlatformSchool::where('status', 'active')->count();
        $inactiveSchools = PlatformSchool::where('status', 'inactive')->count();
        $recentSchools = PlatformSchool::latest()->take(5)->get();

        $monthlyLabels = [];
        $monthlyCounts = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthDate = Carbon::now()->subMonths($i);
            $monthlyLabels[] = $monthDate->format('M Y');
            $monthlyCounts[] = PlatformSchool::whereYear('created_at', $monthDate->year)
                ->whereMonth('created_at', $monthDate->month)
                ->count();
        }

        return view('platform-super-admin.dashboard', compact(
            'admin',
            'totalSchools',
            'activeSchools',
            'inactiveSchools',
            'recentSchools',
            'monthlyLabels',
            'monthlyCounts'
        ));
    }
}
