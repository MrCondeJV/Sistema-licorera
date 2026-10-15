<?php

namespace App\Http\Controllers;

use App\Models\CashSession;
use App\Models\Table;
use App\Services\ReportService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    protected ReportService $reportService;

    public function __construct(ReportService $reportService)
    {
        $this->reportService = $reportService;
    }

    public function index()
    {
        if (auth()->user()?->is_superadmin && !session('superadmin_impersonating')) {
            return redirect()->route('superadmin.index');
        }

        $metrics = $this->reportService->getDashboardMetrics();
        $tables = Table::orderBy('sort_order')->get();
        $openCashSession = CashSession::getActiveSession();

        return view('dashboard.index', [
            'metrics' => $metrics,
            'tables' => $tables,
            'openCashSession' => $openCashSession,
        ]);
    }
}
