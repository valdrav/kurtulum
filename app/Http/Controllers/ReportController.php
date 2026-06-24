<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\IncomeExpense;
use App\Models\Order;
use App\Models\Shipment;
use App\Services\IncomeExpenseReportService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReportController extends Controller
{
    public function __construct()
    {
        $this->middleware('permission:reports.view')->only(['index', 'sales', 'logistics', 'finance', 'customers']);
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
            ->whereYear('order_date', $year)
            ->whereNotIn('status', ['cancelled'])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $monthly = collect(range(1, 12))->map(function (int $month) use ($data) {
            $row = $data->firstWhere('month', $month);

            return (object) [
                'month' => $month,
                'total' => (float) ($row->total ?? 0),
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

    protected function monthExpression(string $column): string
    {
        return match (DB::connection()->getDriverName()) {
            'sqlite' => "cast(strftime('%m', {$column}) as integer)",
            'pgsql' => "EXTRACT(MONTH FROM {$column})::integer",
            default => "MONTH({$column})",
        };
    }
}
