<?php

namespace App\Http\Controllers;

use App\Models\GeneralSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class GeneralSettingsController extends Controller
{
    public function edit(): View
    {
        $settings = GeneralSetting::getSettings();

        $currencies = [
            'PKR' => 'PKR (₨)',
            'USD' => 'USD ($)',
            'EUR' => 'EUR (€)',
            'GBP' => 'GBP (£)',
            'AED' => 'AED (د.إ)',
        ];

        $timezones = [
            'Asia/Karachi',
            'UTC',
            'Asia/Dubai',
            'Asia/Kolkata',
            'Europe/London',
            'America/New_York',
        ];

        return view('settings.general', compact('settings', 'currencies', 'timezones'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'school_name' => ['nullable', 'string', 'max:255'],
            'sms_signature' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'school_phone' => ['nullable', 'string', 'max:255'],
            'school_email' => ['nullable', 'email', 'max:255'],
            'currency' => ['nullable', 'string', 'max:10'],
            'timezone' => ['nullable', 'string', 'max:255'],
            'running_session' => ['nullable', 'string', 'max:50'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'system_name' => ['nullable', 'string', 'max:100'],
        ]);

        $settings = GeneralSetting::getSettings();

        // Handle logo upload
        if ($request->hasFile('logo')) {
            // Delete old logo if exists
            if ($settings->logo && Storage::disk('public')->exists($settings->logo)) {
                Storage::disk('public')->delete($settings->logo);
            }

            // Store new logo
            $logoPath = $request->file('logo')->store('logos', 'public');
            $validated['logo'] = $logoPath;
        }

        $settings->update($validated);

        return redirect()
            ->route('settings.general')
            ->with('success', 'Settings saved successfully!');
    }
}
