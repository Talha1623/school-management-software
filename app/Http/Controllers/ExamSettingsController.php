<?php

namespace App\Http\Controllers;

use App\Models\ExamSetting;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ExamSettingsController extends Controller
{
    /**
     * Display the exam settings form.
     */
    public function index(): View
    {
        $settings = ExamSetting::getSettings();

        return view('settings.exam', compact('settings'));
    }

    /**
     * Update the exam settings.
     */
    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'admit_card_instructions' => ['nullable', 'string'],
            'fail_student_if' => ['nullable', 'string', 'in:less_than_passing,fail_any_subject'],
        ]);

        $settings = ExamSetting::getSettings();
        $settings->update($validated);

        return redirect()
            ->route('settings.exam')
            ->with('success', 'Exam settings saved successfully!');
    }
}
