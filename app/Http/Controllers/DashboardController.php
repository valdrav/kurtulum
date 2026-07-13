<?php

namespace App\Http\Controllers;

use App\Services\DashboardService;
use App\Services\IncomeExpenseReportService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:dashboard.view');
    }

    public function index(DashboardService $dashboard)
    {
        $data = $dashboard->build(auth()->user());

        return view('dashboard.index', $data);
    }
}
