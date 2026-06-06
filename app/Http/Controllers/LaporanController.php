<?php

namespace App\Http\Controllers;

use App\Services\LaporanKepatuhanService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class LaporanController extends Controller
{
    public function kepatuhan(Request $request): View
    {
        $validated = $request->validate([
            'start' => 'nullable|date',
            'end' => 'nullable|date|after_or_equal:start',
        ]);

        $report = LaporanKepatuhanService::getReport(
            $validated['start'] ?? null,
            $validated['end'] ?? null,
        );

        return view('laporan.kepatuhan', ['report' => $report]);
    }
}
