<?php

namespace App\Http\Controllers;

use App\Models\GeneralSetting;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

class AutomationSettingsController extends Controller
{
    public function index(): View
    {
        $settings = GeneralSetting::getSettings();
        $automation = $this->automationSettings($settings);

        return view('settings.automation', compact('automation'));
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'attendance_time_limit' => ['nullable', 'integer', 'min:0'],
            'fee_reminder_days' => ['nullable', 'integer', 'min:0'],
            'late_fee_percentage' => ['nullable', 'numeric', 'min:0'],
            'backup_frequency' => ['nullable', 'string', 'in:daily,weekly,monthly'],
            'backup_time' => ['nullable', 'date_format:H:i'],
        ]);

        $settings = GeneralSetting::getSettings();
        $this->ensureAutomationColumn();

        $settings->automation_settings = [
            'auto_attendance' => $request->boolean('auto_attendance'),
            'auto_absent' => $request->boolean('auto_absent'),
            'attendance_time_limit' => $validated['attendance_time_limit'] ?? null,
            'auto_notify_absent' => $request->boolean('auto_notify_absent'),
            'auto_notify_fee' => $request->boolean('auto_notify_fee'),
            'fee_reminder_days' => $validated['fee_reminder_days'] ?? null,
            'auto_generate_fee' => $request->boolean('auto_generate_fee'),
            'auto_late_fee' => $request->boolean('auto_late_fee'),
            'late_fee_percentage' => $validated['late_fee_percentage'] ?? null,
            'auto_backup' => $request->boolean('auto_backup'),
            'backup_frequency' => $validated['backup_frequency'] ?? 'daily',
            'backup_time' => $validated['backup_time'] ?? '02:00',
        ];
        $settings->save();

        return redirect()
            ->route('settings.automation')
            ->with('success', 'Automation settings saved successfully!');
    }

    /**
     * @return array<string, mixed>
     */
    private function automationSettings(GeneralSetting $settings): array
    {
        $this->ensureAutomationColumn();
        $settings->refresh();

        $defaults = [
            'auto_attendance' => false,
            'auto_absent' => false,
            'attendance_time_limit' => null,
            'auto_notify_absent' => false,
            'auto_notify_fee' => false,
            'fee_reminder_days' => null,
            'auto_generate_fee' => false,
            'auto_late_fee' => false,
            'late_fee_percentage' => null,
            'auto_backup' => false,
            'backup_frequency' => 'daily',
            'backup_time' => '02:00',
        ];

        $stored = $settings->automation_settings;
        if (! is_array($stored)) {
            return $defaults;
        }

        return array_merge($defaults, $stored);
    }

    private function ensureAutomationColumn(): void
    {
        if (! Schema::hasTable('general_settings')) {
            return;
        }

        if (Schema::hasColumn('general_settings', 'automation_settings')) {
            return;
        }

        Schema::table('general_settings', function (Blueprint $table) {
            $table->json('automation_settings')->nullable();
        });
    }
}
