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
}
