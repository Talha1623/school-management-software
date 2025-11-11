<?php

namespace App\Http\Controllers;

use App\Models\SalarySetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class SalarySettingController extends Controller
{
    /**
     * Display the salary settings form.
     */
    public function index(): View
    {
        $settings = SalarySetting::getSettings();
        
        return view('salary-loan.salary-setting', compact('settings'));
    }

    /**
     * Update the salary settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'late_arrival_time' => ['required', 'string'],
            'free_absents' => ['required', 'integer', 'min:0'],
            'leave_deduction' => ['required', 'in:Yes,No'],
        ]);

        // Convert time format from "08:00 AM" to "08:00:00"
        $time = $this->convertTimeFormat($validated['late_arrival_time']);
        $validated['late_arrival_time'] = $time;

        $settings = SalarySetting::getSettings();
        $settings->update($validated);

        return redirect()
            ->route('salary-loan.salary-setting')
            ->with('success', 'Salary settings updated successfully!');
    }

    /**
     * Convert time format from "08:00 AM" to "08:00:00"
     */
    private function convertTimeFormat($time)
    {
        // If already in 24-hour format, return as is
        if (preg_match('/^\d{2}:\d{2}$/', $time)) {
            return $time . ':00';
        }

        // Convert from 12-hour format to 24-hour format
        try {
            $dateTime = \DateTime::createFromFormat('h:i A', $time);
            if ($dateTime) {
                return $dateTime->format('H:i:s');
            }
        } catch (\Exception $e) {
            // If conversion fails, try other formats
        }

        // Default fallback
        return '08:00:00';
    }
}

