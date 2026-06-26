<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\Order;
use App\Models\Shipment;
use App\Models\Task;
use App\Models\Account;
use App\Models\IncomeExpense;
use App\Services\ExchangeRateService;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function __construct(
        protected ExchangeRateService $rates,
    ) {
        $this->middleware('permission:dashboard.view');
    }

    public function index()
    {
        $defaultCurrency = registry()->defaultCurrency()?->code ?? 'TRY';

        $monthlyIncome = (float) IncomeExpense::where('type', 'income')
            ->whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year)
            ->sum('amount_base');
        $monthlyExpense = (float) IncomeExpense::where('type', 'expense')
            ->whereMonth('transaction_date', now()->month)
            ->whereYear('transaction_date', now()->year)
            ->sum('amount_base');

        $monthlyMargin = (float) Order::whereMonth('order_date', now()->month)
            ->whereYear('order_date', now()->year)
            ->get()
            ->sum(fn (Order $order) => $this->rates->amountForAccount(
                (float) ($order->margin_total ?? 0),
                $order->currency ?? $defaultCurrency,
                new Account(['currency' => $defaultCurrency])
            ));

        $totalMargin = (float) Order::whereNotIn('status', ['cancelled', 'draft'])
            ->get()
            ->sum(fn (Order $order) => $this->rates->amountForAccount(
                (float) ($order->margin_total ?? 0),
                $order->currency ?? $defaultCurrency,
                new Account(['currency' => $defaultCurrency])
            ));

        $receivables = (float) Account::query()
            ->where('type', 'customer')
            ->where('is_treasury', false)
            ->get()
            ->sum(fn (Account $account) => $this->rates->amountForAccount(
                (float) $account->current_balance,
                $account->currency ?? $defaultCurrency,
                $account
            ));

        $stats = [
            'currency' => $defaultCurrency,
            'customers' => Customer::count(),
            'orders' => Order::whereNotIn('status', ['cancelled'])->count(),
            'shipments_active' => Shipment::whereIn('status', ['booked', 'in_transit', 'at_port', 'customs'])->count(),
            'tasks_pending' => Task::where('status', 'pending')->count(),
            'receivables' => $receivables,
            'monthly_income' => $monthlyIncome,
            'monthly_expense' => $monthlyExpense,
            'monthly_margin' => $monthlyMargin,
            'total_margin' => $totalMargin,
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
