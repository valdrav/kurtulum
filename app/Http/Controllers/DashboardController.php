<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\Task;
use App\Models\IncomeExpense;
use App\Services\TradeFinanceService;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(
        protected TradeFinanceService $tradeFinance,
    ) {
        $this->middleware('permission:dashboard.view');
    }

    public function index()
    {
        $tradeCurrency = $this->tradeFinance->tradeCurrency();
        $defaultCurrency = registry()->defaultCurrency()?->code ?? 'TRY';

        $monthlyIncome = (float) IncomeExpense::where('type', 'income')
            ->whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year)
            ->sum('amount_base');
        $monthlyExpense = (float) IncomeExpense::where('type', 'expense')
            ->whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year)
            ->sum('amount_base');

        $stats = [
            'currency' => $tradeCurrency,
            'treasury_currency' => $defaultCurrency,
            'customers' => Customer::count(),
            'orders' => Order::whereNotIn('status', ['cancelled'])->count(),
            'shipments_active' => Shipment::whereIn('status', ['booked', 'in_transit', 'at_port', 'customs'])->count(),
            'tasks_pending' => Task::where('status', 'pending')->count(),
            'receivables' => $this->tradeFinance->totalReceivables(),
            'payables' => $this->tradeFinance->totalPayables(),
            'monthly_income' => $monthlyIncome,
            'monthly_expense' => $monthlyExpense,
            'monthly_margin' => $this->tradeFinance->monthlyMargin(),
            'total_margin' => $this->tradeFinance->totalMargin(),
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
                ->whereNotIn('status', ['cancelled'])
                ->get()
                ->sum(fn (Order $order) => $this->tradeFinance->toTradeCurrency(
                    (float) ($order->margin_total ?? 0),
                    $order->currency ?? $tradeCurrency
                )))->values(),
            'currency' => $tradeCurrency,
        ];

        return view('dashboard.index', compact(
            'stats', 'recentShipments', 'recentOrders', 'shipmentsByMode', 'upcomingTasks', 'revenueChart'
        ));
    }
}
