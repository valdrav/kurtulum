<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Customer;
use App\Models\IncomeExpense;
use App\Models\Order;
use App\Models\Payment;
use App\Models\Shipment;
use App\Models\Supplier;
use App\Services\IncomeExpenseReportService;
use App\Services\TradeFinanceService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __construct(protected TradeFinanceService $tradeFinance)
    {
        $this->middleware('permission:reports.view')->only([
            'index', 'sales', 'logistics', 'finance', 'customers', 'suppliers', 'exchangeRates',
        ]);
    }

    public function index()
    {
        return view('reports.index');
    }

    public function sales(Request $request)
    {
        $year = (int) $request->input('year', now()->year);
        $monthExpr = $this->monthExpression('order_date');

        $data = Order::query()
            ->selectRaw("{$monthExpr} as month")
            ->selectRaw('SUM(total_amount) as total')
            ->selectRaw('currency')
            ->whereYear('order_date', $year)
            ->whereNotIn('status', ['cancelled'])
            ->groupBy('month', 'currency')
            ->orderBy('month')
            ->get()
            ->groupBy('month');

        $monthly = collect(range(1, 12))->map(function (int $month) use ($data) {
            $rows = $data->get($month, collect());
            $usd = (float) $rows->where('currency', 'USD')->sum('total');
            $try = (float) $rows->where('currency', 'TRY')->sum('total');
            $other = (float) $rows->whereNotIn('currency', ['USD', 'TRY'])->sum('total');

            return (object) [
                'month' => $month,
                'total' => $usd + $try + $other,
                'usd' => $usd,
                'try' => $try,
            ];
        });

        return view('reports.sales', compact('monthly', 'year'));
    }

    public function logistics()
    {
        $byMode = Shipment::query()
            ->select('transport_mode', DB::raw('count(*) as count'))
            ->groupBy('transport_mode')
            ->orderByDesc('count')
            ->get();

        $byStatus = Shipment::query()
            ->select('status', DB::raw('count(*) as count'))
            ->groupBy('status')
            ->orderByDesc('count')
            ->get();

        $delayed = Shipment::query()
            ->where('eta', '<', now())
            ->whereNotIn('status', ['delivered', 'completed', 'cancelled'])
            ->count();

        $total = (int) Shipment::count();

        return view('reports.logistics', compact('byMode', 'byStatus', 'delayed', 'total'));
    }

    public function finance(Request $request, IncomeExpenseReportService $reports)
    {
        $periodMeta = $reports->resolvePeriod(
            $request->input('period', 'year'),
            $request->input('date'),
            $request->input('from'),
            $request->input('to')
        );

        $summary = $reports->summary($periodMeta['start'], $periodMeta['end']);
        $byCategory = $reports->byCategory($periodMeta['start'], $periodMeta['end'])->take(10);
        $timeline = $reports->timeline($periodMeta['start'], $periodMeta['end'], $periodMeta['period']);

        return view('reports.finance', compact('periodMeta', 'summary', 'byCategory', 'timeline'));
    }

    public function customers()
    {
        $topCustomers = Customer::query()
            ->withCount('orders')
            ->withSum('orders', 'total_amount')
            ->orderByDesc('orders_sum_total_amount')
            ->limit(10)
            ->get();

        return view('reports.customers', compact('topCustomers'));
    }

    public function suppliers()
    {
        $topSuppliers = Supplier::query()
            ->withCount('orders')
            ->withSum('orders', 'purchase_total')
            ->withSum('orders', 'total_amount')
            ->orderByDesc('orders_count')
            ->limit(20)
            ->get();

        $byType = Supplier::query()
            ->select('type', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->groupBy('type')
            ->orderByDesc('count')
            ->get();

        $byCountry = Supplier::query()
            ->select('country', \Illuminate\Support\Facades\DB::raw('count(*) as count'))
            ->whereNotNull('country')
            ->where('country', '!=', '')
            ->groupBy('country')
            ->orderByDesc('count')
            ->limit(15)
            ->get();

        $activeCount = Supplier::where('status', 'active')->count();
        $totalCount = Supplier::count();

        return view('reports.suppliers', compact('topSuppliers', 'byType', 'byCountry', 'activeCount', 'totalCount'));
    }

    public function exchangeRates(Request $request)
    {
        $from = $request->input('from', now()->startOfMonth()->toDateString());
        $to = $request->input('to', now()->toDateString());

        $collections = Collection::with(['account', 'order'])
            ->whereDate('collection_date', '>=', $from)
            ->whereDate('collection_date', '<=', $to)
            ->where('currency', '!=', registry()->defaultCurrency()?->code ?? 'TRY')
            ->latest('collection_date')
            ->limit(200)
            ->get();

        $payments = Payment::with(['account', 'order'])
            ->whereDate('payment_date', '>=', $from)
            ->whereDate('payment_date', '<=', $to)
            ->where('currency', '!=', registry()->defaultCurrency()?->code ?? 'TRY')
            ->latest('payment_date')
            ->limit(200)
            ->get();

        return view('reports.exchange-rates', compact('collections', 'payments', 'from', 'to'));
    }

    protected function monthExpression(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "cast(strftime('%m', {$column}) as integer)",
            'pgsql' => "EXTRACT(MONTH FROM {$column})::integer",
            default => "MONTH({$column})",
        };
    }
}
