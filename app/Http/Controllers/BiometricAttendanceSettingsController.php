<?php

namespace App\Http\Controllers;

use App\Models\GeneralSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\View\View;

class BiometricAttendanceSettingsController extends Controller
{
    /**
     * Display the biometric attendance settings page.
     */
    public function index(): View
    {
        $settings = GeneralSetting::getSettings();
        
        // Generate bio token if not exists
        if (!$settings->bio_token) {
            $settings->bio_token = Str::random(32);
            $settings->save();
        }
        
        // Set default campus_id if not exists
        if (!$settings->campus_id) {
            $settings->campus_id = 1;
            $settings->save();
        }
        
        return view('settings.biometric-attendance', compact('settings'));
    }

    /**
     * Update biometric attendance settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'campus_id' => ['required', 'integer', 'min:1'],
        ]);

        $settings = GeneralSetting::getSettings();
        $settings->campus_id = $validated['campus_id'];
        $settings->save();

        return redirect()
            ->route('settings.biometric-attendance')
            ->with('success', 'Biometric attendance settings updated successfully!');
    }

    /**
     * Regenerate bio token.
     */
    public function regenerateToken(): RedirectResponse
    {
        $settings = GeneralSetting::getSettings();
        $settings->bio_token = Str::random(32);
        $settings->save();

        return redirect()
            ->route('settings.biometric-attendance')
            ->with('success', 'Bio Token regenerated successfully!');
    }
}
