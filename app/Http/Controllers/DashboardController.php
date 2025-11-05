<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index()
    {
        return view('dashboards.index');
    }

    public function crm()
    {
        return view('dashboards.crm');
    }

    public function projectManagement()
    {
        return view('dashboards.project-management');
    }

    public function lms()
    {
        return view('dashboards.lms');
    }

    public function helpDesk()
    {
        return view('dashboards.help-desk');
    }

    public function hrManagement()
    {
        return view('dashboards.hr-management');
    }

    public function school()
    {
        return view('dashboards.school');
    }

    public function marketing()
    {
        return view('dashboards.marketing');
    }

    public function analytics()
    {
        return view('dashboards.analytics');
    }

    public function hospital()
    {
        return view('dashboards.hospital');
    }

    public function finance()
    {
        return view('dashboards.finance');
    }
}

