<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\Task;
use App\Models\Account;
use App\Models\IncomeExpense;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:dashboard.view');
    }

    public function index()
    {
        $stats = [
            'customers' => Customer::count(),
            'orders' => Order::whereNotIn('status', ['cancelled'])->count(),
            'shipments_active' => Shipment::whereIn('status', ['booked', 'in_transit', 'at_port', 'customs'])->count(),
            'tasks_pending' => Task::where('status', 'pending')->count(),
            'receivables' => Account::where('type', 'customer')->sum('current_balance'),
            'monthly_income' => IncomeExpense::where('type', 'income')
                ->whereMonth('transaction_date', now()->month)->sum('amount_base'),
            'monthly_expense' => IncomeExpense::where('type', 'expense')
                ->whereMonth('transaction_date', now()->month)->sum('amount_base'),
            'monthly_margin' => Order::whereMonth('order_date', now()->month)->sum('margin_total'),
            'total_margin' => Order::whereNotIn('status', ['cancelled', 'draft'])->sum('margin_total'),
        ];

        $stats['monthly_profit'] = $stats['monthly_income'] - $stats['monthly_expense'];

        $recentShipments = Shipment::with(['customer', 'originPort', 'destinationPort'])
            ->latest()->limit(6)->get();

        $recentOrders = Order::with('customer')->latest()->limit(5)->get();

        $shipmentsByMode = Shipment::select('transport_mode', DB::raw('count(*) as total'))
            ->groupBy('transport_mode')->pluck('total', 'transport_mode');

        $upcomingTasks = Task::with('assignee')
            ->where('status', '!=', 'completed')
            ->where('due_date', '>=', now())
            ->orderBy('due_date')->limit(5)->get();

        $chartMonths = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i));
        $revenueChart = [
            'labels' => $chartMonths->map(fn ($d) => $d->translatedFormat('M Y'))->values(),
            'income' => $chartMonths->map(fn ($d) => (float) IncomeExpense::where('type', 'income')
                ->whereYear('transaction_date', $d->year)
                ->whereMonth('transaction_date', $d->month)
                ->sum('amount_base'))->values(),
            'expense' => $chartMonths->map(fn ($d) => (float) IncomeExpense::where('type', 'expense')
                ->whereYear('transaction_date', $d->year)
                ->whereMonth('transaction_date', $d->month)
                ->sum('amount_base'))->values(),
            'margin' => $chartMonths->map(fn ($d) => (float) Order::whereYear('order_date', $d->year)
                ->whereMonth('order_date', $d->month)
                ->sum('margin_total'))->values(),
        ];

        return view('dashboard.index', compact(
            'stats', 'recentShipments', 'recentOrders', 'shipmentsByMode', 'upcomingTasks', 'revenueChart'
        ));
    }
}
