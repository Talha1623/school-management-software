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
            'early_exit_time' => ['nullable', 'string'],
            'free_absents' => ['required', 'integer', 'min:0'],
            'leave_deduction' => ['required', 'in:Yes,No'],
        ]);

        // Convert time format from "08:00 AM" to "08:00:00"
        $time = $this->convertTimeFormat($validated['late_arrival_time']);
        $validated['late_arrival_time'] = $time;
        
        // Convert early exit time if provided
        if (!empty($validated['early_exit_time'])) {
            $earlyExitTime = $this->convertTimeFormat($validated['early_exit_time']);
            $validated['early_exit_time'] = $earlyExitTime;
        } else {
            $validated['early_exit_time'] = null;
        }

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
        if (empty($time)) {
            return null;
        }
        
        // Trim whitespace
        $time = trim($time);
        
        // If already in 24-hour format (HH:MM or HH:MM:SS), return as HH:MM:SS
        if (preg_match('/^(\d{1,2}):(\d{2})(?::\d{2})?$/', $time, $matches)) {
            $hours = str_pad($matches[1], 2, '0', STR_PAD_LEFT);
            $minutes = $matches[2];
            return $hours . ':' . $minutes . ':00';
        }

        // Convert from 12-hour format to 24-hour format (e.g., "08:00 AM" or "8:00 AM")
        if (preg_match('/(\d{1,2}):(\d{2})\s*(AM|PM)/i', $time, $matches)) {
            $hours = (int)$matches[1];
            $minutes = $matches[2];
            $ampm = strtoupper($matches[3]);
            
            if ($ampm === 'PM' && $hours != 12) {
                $hours += 12;
            } elseif ($ampm === 'AM' && $hours == 12) {
                $hours = 0;
            }
            
            return str_pad($hours, 2, '0', STR_PAD_LEFT) . ':' . $minutes . ':00';
        }

        // Try DateTime parsing as fallback
        try {
            $dateTime = \DateTime::createFromFormat('h:i A', $time);
            if ($dateTime) {
                return $dateTime->format('H:i:s');
            }
        } catch (\Exception $e) {
            // If conversion fails, continue
        }

        // Default fallback for late_arrival_time (required field)
        return '08:00:00';
    }
}

