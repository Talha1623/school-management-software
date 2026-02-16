<?php

namespace App\Http\Controllers;

use App\Models\Test;
use Illuminate\Http\Request;
use Illuminate\View\View;

class TestReportsController extends Controller
{
    /**
     * Display the test reports page.
     */
    public function index(Request $request): View
    {
        // Get total tests count
        $totalTests = Test::count();
        
        // Get declared results count (result_status = 1)
        $declaredResults = Test::where('result_status', 1)->count();
        
        return view('test.reports.index', compact('totalTests', 'declaredResults'));
    }

    /**
     * Print blank tabulation sheet.
     */
    public function printBlankTabulationSheet(): View
    {
        return view('test.reports.print.blank-tabulation-sheet');
    }

    /**
     * Print blank attendance sheet.
     */
    public function printBlankAttendanceSheet(): View
    {
        return view('test.reports.print.blank-attendance-sheet');
    }

    /**
     * Print blank marksheet.
     */
    public function printBlankMarksheet(): View
    {
        return view('test.reports.print.blank-marksheet');
    }
}
