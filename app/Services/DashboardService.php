<?php

namespace App\Services;

use App\Models\Collection;
use App\Models\Customer;
use App\Models\IncomeExpense;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\Supplier;
use App\Models\Task;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function __construct(
        protected TradeFinanceService $tradeFinance,
        protected IncomeExpenseReportService $reports,
        protected TreasuryLedgerService $treasuryLedger,
    ) {}

    public function build(?User $user = null): array
    {
        $user ??= auth()->user();
        $currency = registry()->defaultCurrency()?->code ?? 'TRY';

        return [
            'currency' => $currency,
            'permissions' => $this->permissions($user),
            'kpis' => $this->kpis($user, $currency),
            'quick_actions' => $this->quickActions($user),
            'charts' => $this->charts($user),
            'recent' => $this->recent($user),
            'alerts' => $this->alerts($user),
        ];
    }

    /** @return array<string, bool> */
    protected function permissions(?User $user): array
    {
        $check = fn (string $perm) => $user && can_access($perm);

        return [
            'finance' => $check('finance.view'),
            'orders' => $check('orders.view'),
            'shipments' => $check('shipments.view'),
            'customers' => $check('customers.view'),
            'suppliers' => $check('suppliers.view'),
            'tasks' => $check('tasks.view'),
            'reports' => $check('reports.view'),
            'documents' => $check('documents.view'),
            'emails' => $check('emails.view'),
            'employees' => $check('employees.view'),
        ];
    }

    protected function kpis(?User $user, string $currency): array
    {
        $kpis = [];
        $amountSql = $this->reports->amountExpression();

        if (can_access('finance.view')) {
            $monthStart = now()->startOfMonth();
            $monthEnd = now()->endOfMonth();

            $kpis[] = [
                'key' => 'bank_balance',
                'label' => __('dashboard.bank_balance'),
                'value' => company_treasury()->totalBalanceTry(),
                'format' => 'money',
                'currency' => $currency,
                'icon' => 'ti-building-bank',
                'color' => 'primary',
                'hint' => __('dashboard.bank_balance_hint'),
                'link' => route('finance.treasury'),
            ];

            $monthlyIncome = (float) IncomeExpense::query()
                ->where('type', 'income')
                ->whereBetween('transaction_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->sum($amountSql);

            $monthlyExpense = (float) IncomeExpense::query()
                ->where('type', 'expense')
                ->whereBetween('transaction_date', [$monthStart->toDateString(), $monthEnd->toDateString()])
                ->sum($amountSql);

            $kpis[] = [
                'key' => 'monthly_income',
                'label' => __('dashboard.monthly_income'),
                'value' => $monthlyIncome,
                'format' => 'money',
                'currency' => $currency,
                'icon' => 'ti-arrow-down-left',
                'color' => 'green',
                'link' => route('finance.profit-loss', ['period' => 'month', 'date' => now()->format('Y-m-d')]),
            ];

            $kpis[] = [
                'key' => 'monthly_expense',
                'label' => __('dashboard.monthly_expense'),
                'value' => $monthlyExpense,
                'format' => 'money',
                'currency' => $currency,
                'icon' => 'ti-arrow-up-right',
                'color' => 'red',
                'link' => route('finance.profit-loss', ['period' => 'month', 'date' => now()->format('Y-m-d')]),
            ];

            $kpis[] = [
                'key' => 'receivables',
                'label' => __('dashboard.receivables'),
                'value' => $this->tradeFinance->dualReceivables(),
                'format' => 'dual',
                'icon' => 'ti-wallet',
                'color' => 'purple',
                'link' => route('finance.accounts'),
            ];

            $kpis[] = [
                'key' => 'payables',
                'label' => __('dashboard.payables'),
                'value' => $this->tradeFinance->dualPayables(),
                'format' => 'dual',
                'icon' => 'ti-credit-card',
                'color' => 'red',
                'link' => route('finance.accounts'),
            ];
        }

        if (can_access('orders.view')) {
            $kpis[] = [
                'key' => 'orders',
                'label' => __('dashboard.active_orders'),
                'value' => Order::whereNotIn('status', ['cancelled'])->count(),
                'format' => 'number',
                'icon' => 'ti-shopping-cart',
                'color' => 'azure',
                'link' => route('orders.index'),
            ];

            if (can_access('finance.view')) {
                $kpis[] = [
                    'key' => 'margin',
                    'label' => __('dashboard.monthly_margin'),
                    'value' => $this->tradeFinance->dualMonthlyMargin(),
                    'format' => 'dual',
                    'icon' => 'ti-chart-line',
                    'color' => 'green',
                    'link' => route('orders.index'),
                ];
            }
        }

        if (can_access('shipments.view')) {
            $kpis[] = [
                'key' => 'shipments_active',
                'label' => __('dashboard.active_shipments'),
                'value' => Shipment::whereIn('status', ['booked', 'in_transit', 'at_port', 'customs'])->count(),
                'format' => 'number',
                'icon' => 'ti-truck',
                'color' => 'orange',
                'link' => route('shipments.index'),
            ];
        }

        if (can_access('customers.view')) {
            $kpis[] = [
                'key' => 'customers',
                'label' => __('dashboard.customers'),
                'value' => Customer::count(),
                'format' => 'number',
                'icon' => 'ti-users',
                'color' => 'indigo',
                'link' => route('customers.index'),
            ];
        }

        if (can_access('suppliers.view')) {
            $kpis[] = [
                'key' => 'suppliers',
                'label' => __('dashboard.suppliers'),
                'value' => Supplier::count(),
                'format' => 'number',
                'icon' => 'ti-building-store',
                'color' => 'cyan',
                'link' => route('suppliers.index'),
            ];
        }

        if (can_access('tasks.view')) {
            $taskQuery = $this->scopedTasks($user)->where('status', 'pending');
            $kpis[] = [
                'key' => 'tasks_pending',
                'label' => __('dashboard.pending_tasks'),
                'value' => $taskQuery->count(),
                'format' => 'number',
                'icon' => 'ti-checkbox',
                'color' => 'yellow',
                'link' => route('tasks.index'),
            ];
        }

        return $kpis;
    }

    protected function quickActions(?User $user): array
    {
        $actions = [];

        $push = function (string $perm, string $route, string $label, string $icon, string $color = 'primary') use (&$actions) {
            if (can_access($perm)) {
                $actions[] = compact('route', 'label', 'icon', 'color');
            }
        };

        $push('orders.create', route('orders.create'), __('dashboard.new_order'), 'ti-shopping-cart-plus', 'primary');
        $push('shipments.create', route('shipments.create'), __('dashboard.new_shipment'), 'ti-truck-delivery', 'azure');
        $push('finance.create', route('finance.income-expenses'), __('dashboard.new_expense'), 'ti-receipt', 'red');
        $push('finance.create', route('finance.collections'), __('dashboard.new_collection'), 'ti-cash', 'green');
        $push('finance.create', route('finance.payments'), __('dashboard.new_payment'), 'ti-credit-card', 'orange');
        $push('customers.create', route('customers.create'), __('dashboard.new_customer'), 'ti-user-plus', 'indigo');
        $push('tasks.create', route('tasks.index'), __('dashboard.new_task'), 'ti-checkbox', 'yellow');
        $push('reports.view', route('reports.index'), __('dashboard.reports'), 'ti-chart-bar', 'secondary');

        return $actions;
    }

    protected function charts(?User $user): array
    {
        $charts = [];

        if (can_access('finance.view') || can_access('orders.view')) {
            $chartMonths = collect(range(5, 0))->map(fn ($i) => now()->subMonths($i));
            $amountSql = $this->reports->amountExpression();

            $charts['revenue'] = [
                'labels' => $chartMonths->map(fn ($d) => $d->translatedFormat('M Y'))->values(),
                'income' => $chartMonths->map(fn ($d) => (float) IncomeExpense::where('type', 'income')
                    ->whereYear('transaction_date', $d->year)
                    ->whereMonth('transaction_date', $d->month)
                    ->sum($amountSql))->values(),
                'expense' => $chartMonths->map(fn ($d) => (float) IncomeExpense::where('type', 'expense')
                    ->whereYear('transaction_date', $d->year)
                    ->whereMonth('transaction_date', $d->month)
                    ->sum($amountSql))->values(),
                'margin' => can_access('orders.view')
                    ? $chartMonths->map(function ($d) {
                        $dual = $this->tradeFinance->dualTotals(
                            fn (Order $order) => (float) ($order->margin_total ?? 0),
                            Order::query()
                                ->whereYear('order_date', $d->year)
                                ->whereMonth('order_date', $d->month)
                                ->whereNotIn('status', ['cancelled'])
                        );

                        return $dual['USD'];
                    })->values()
                    : collect(),
            ];
        }

        if (can_access('shipments.view')) {
            $charts['shipments_by_mode'] = Shipment::select('transport_mode', DB::raw('count(*) as total'))
                ->groupBy('transport_mode')
                ->pluck('total', 'transport_mode');
        }

        return $charts;
    }

    protected function recent(?User $user): array
    {
        $recent = [];

        if (can_access('shipments.view')) {
            $recent['shipments'] = Shipment::with(['customer', 'originPort', 'destinationPort'])
                ->latest()->limit(6)->get();
        }

        if (can_access('orders.view')) {
            $recent['orders'] = Order::with('customer')->latest()->limit(6)->get();
        }

        if (can_access('finance.view')) {
            $amountSql = $this->reports->amountExpression();
            $recent['collections'] = Collection::with(['account.customer', 'paymentMethod'])
                ->latest()->limit(5)->get();
            $recent['payments'] = Payment::with(['account.supplier', 'paymentMethod'])
                ->latest()->limit(5)->get();
            $recent['income_expenses'] = IncomeExpense::with('account')
                ->latest('transaction_date')->latest('id')
                ->limit(6)->get();
            $recent['finance_totals'] = [
                'collections_month' => (float) Collection::whereMonth('collection_date', now()->month)
                    ->whereYear('collection_date', now()->year)
                    ->sum(DB::raw('COALESCE(amount * COALESCE(NULLIF(exchange_rate, 0), 1), amount)')),
                'payments_month' => (float) Payment::whereMonth('payment_date', now()->month)
                    ->whereYear('payment_date', now()->year)
                    ->sum(DB::raw('COALESCE(amount * COALESCE(NULLIF(exchange_rate, 0), 1), amount)')),
            ];
        }

        if (can_access('tasks.view')) {
            $recent['upcoming_tasks'] = $this->scopedTasks($user)
                ->with('assignee')
                ->where('status', '!=', 'completed')
                ->where('due_date', '>=', now())
                ->orderBy('due_date')->limit(5)->get();

            $recent['my_tasks'] = $this->scopedTasks($user)
                ->with('assignee')
                ->where('status', '!=', 'completed')
                ->when($user, fn ($q) => $q->where('assigned_to', $user->id))
                ->orderBy('due_date')->limit(5)->get();
        }

        return $recent;
    }

    protected function alerts(?User $user): array
    {
        $alerts = [];

        if (can_access('shipments.view')) {
            $alerts['delayed_shipments'] = Shipment::with(['customer', 'order'])
                ->where('eta', '<', now())
                ->whereNotIn('status', ['delivered', 'completed', 'cancelled'])
                ->orderBy('eta')
                ->limit(6)
                ->get();
        }

        if (can_access('tasks.view')) {
            $alerts['overdue_tasks'] = $this->scopedTasks($user)
                ->with('assignee')
                ->where('status', '!=', 'completed')
                ->where(function ($q) {
                    $q->where('due_date', '<', now())
                        ->orWhere(function ($q2) {
                            $q2->whereNotNull('reminder_at')->where('reminder_at', '<=', now());
                        });
                })
                ->orderBy('due_date')->limit(6)->get();
        }

        return $alerts;
    }

    protected function scopedTasks(?User $user)
    {
        $query = Task::query();

        if ($user && ! $user->hasAnyRole(['super-admin', 'admin', 'manager', 'patron'])) {
            $query->where(function ($q) use ($user) {
                $q->where('assigned_to', $user->id)
                    ->orWhere('created_by', $user->id);
            });
        }

        return $query;
    }
}
