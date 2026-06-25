<?php

namespace App\Http\Controllers;

use App\Models\GeneralSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
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

    public function payment(): View
    {
        $settings = GeneralSetting::getSettings();

        return view('settings.payment', compact('settings'));
    }

    public function updatePayment(Request $request): RedirectResponse
    {
        $this->ensureFeeVoucherColumns();

        $validated = $request->validate([
            'bank_name' => ['nullable', 'string', 'max:255'],
            'account_number' => ['nullable', 'string', 'max:255'],
            'account_holder' => ['nullable', 'string', 'max:255'],
            'iban' => ['nullable', 'string', 'max:255'],
        ]);

        $settings = GeneralSetting::getSettings();
        $settings->update([
            'fee_voucher_bank_name' => $validated['bank_name'] ?? null,
            'fee_voucher_account_number' => $validated['account_number'] ?? null,
            'fee_voucher_account_title' => $validated['account_holder'] ?? null,
            'fee_voucher_iban' => $validated['iban'] ?? null,
        ]);

        return redirect()
            ->route('settings.payment')
            ->with('success', 'Bank account details saved. They will appear on fee voucher print.');
    }

    public function update(Request $request): RedirectResponse
    {
        $settings = GeneralSetting::getSettings();
        $this->ensureFeeVoucherColumns();
        $validated = $request->validate([
            'school_name' => ['nullable', 'string', 'max:255'],
            'sms_signature' => ['nullable', 'string', 'max:255'],
            'address' => ['nullable', 'string'],
            'school_phone' => ['nullable', 'string', 'max:255'],
            'school_email' => ['nullable', 'email', 'max:255'],
            'currency' => ['nullable', 'string', 'max:10'],
            'timezone' => ['nullable', 'string', 'max:255'],
            'running_session' => ['nullable', 'string', 'max:50'],
            'fee_voucher_notice' => ['nullable', 'string', 'max:1000'],
            'accounts_settlement_print_note' => ['nullable', 'string', 'max:1000'],
            'logo' => ['nullable', 'image', 'mimes:jpeg,png,jpg,gif,svg', 'max:2048'],
            'system_name' => ['nullable', 'string', 'max:100'],
        ]);

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

    private function ensureFeeVoucherColumns(): void
    {
        $missingColumns = [];
        $voucherColumns = [
            'fee_voucher_notice' => 'text',
            'accounts_settlement_print_note' => 'text',
            'fee_voucher_bank_name' => 'string',
            'fee_voucher_account_title' => 'string',
            'fee_voucher_account_number' => 'string',
            'fee_voucher_iban' => 'string',
        ];
        foreach ($voucherColumns as $column => $type) {
            if (!Schema::hasColumn('general_settings', $column)) {
                $missingColumns[$column] = $type;
            }
        }
        if (empty($missingColumns)) {
            return;
        }

        Schema::table('general_settings', function (Blueprint $table) use ($missingColumns) {
            foreach ($missingColumns as $column => $type) {
                if ($type === 'text') {
                    $table->text($column)->nullable();
                } else {
                    $table->string($column)->nullable();
                }
            }
        });
    }
}
