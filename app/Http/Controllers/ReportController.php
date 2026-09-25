<?php

namespace App\Http\Controllers;

use App\Services\ReportService;
use Illuminate\Contracts\View\View;
use Illuminate\Support\Facades\Auth;

class ReportController extends Controller
{
    /**
     * マイ読書レポートを表示する。
     */
    public function index(ReportService $reportService): View
    {
        $stats = $reportService->getStats(Auth::user());

        return view('reports.index', compact('stats'));
    }
}
