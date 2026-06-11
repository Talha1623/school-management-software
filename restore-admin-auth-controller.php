<?php
/**
 * Restores a clean AdminAuthController when live file was wiped by a bad merge/fix.
 * Run: php restore-admin-auth-controller.php
 * Delete this file after use.
 */
$target = __DIR__ . '/app/Http/Controllers/AdminAuthController.php';

$content = <<<'PHP'
<?php

namespace App\Http\Controllers;

use App\Models\AdminRole;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\View;

class AdminAuthController extends Controller
{
    /**
     * Show the admin login form.
     */
    public function showLoginForm(): View|RedirectResponse
    {
        if (Auth::guard('admin')->check()) {
            return redirect()->intended(route('dashboard'));
        }

        $errors = session('errors') ?: new ViewErrorBag();

        return view('admin.login', ['errors' => $errors]);
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

        $admin = AdminRole::where('email', $credentials['email'])->first();

        if (!$admin) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

        if (!Hash::check($credentials['password'], $admin->password)) {
            return back()->withErrors([
                'email' => 'The provided credentials do not match our records.',
            ])->onlyInput('email');
        }

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

PHP;

if (!is_dir(dirname($target))) {
    fwrite(STDERR, "Controllers directory missing.\n");
    exit(1);
}

file_put_contents($target, $content);
echo "RESTORED: app/Http/Controllers/AdminAuthController.php\n";
echo "Run: composer dump-autoload -o && php artisan optimize:clear\n";
