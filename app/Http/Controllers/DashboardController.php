<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class DashboardController extends Controller
{
    public function pasien(Request $request): View
    {
        return view('dashboard.pasien', DashboardService::untukPasien($request->user()));
    }

    public function pmo(Request $request): View
    {
        return view('dashboard.pmo', DashboardService::untukPmo($request->user()));
    }

    public function admin(Request $request): View
    {
        return view('dashboard.admin', DashboardService::untukAdmin('admin'));
    }

    public function superadmin(Request $request): View
    {
        return view('dashboard.superadmin', DashboardService::untukAdmin('superadmin'));
    }
}
