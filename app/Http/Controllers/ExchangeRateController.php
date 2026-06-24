<?php

namespace App\Http\Controllers;

use App\Services\ExchangeRateService;
use Illuminate\Http\Request;

class ExchangeRateController extends Controller
{
    public function rates(Request $request, ExchangeRateService $rates)
    {
        $force = $request->boolean('force');
        $barRates = $rates->ratesForBar($force);

        return response()->json([
            'base' => registry()->defaultCurrency()?->code ?? 'TRY',
            'updated_at' => $rates->lastUpdatedLabel(),
            'updated_at_iso' => $rates->lastUpdated()?->toIso8601String(),
            'timezone' => app_timezone(),
            'sync_minutes' => $rates->syncIntervalMinutes(),
            'rates' => $barRates,
            'forced' => $force,
        ])->header('Cache-Control', 'no-store, no-cache, must-revalidate');
    }

    public function convert(Request $request, ExchangeRateService $rates)
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'from' => 'required|string|size:3',
            'to' => 'required|string|size:3',
            'rate_type' => 'nullable|in:tcmb,market',
        ]);

        $result = $rates->convert(
            (float) $validated['amount'],
            strtoupper($validated['from']),
            strtoupper($validated['to']),
            $validated['rate_type'] ?? 'tcmb'
        );

        if ($result === null) {
            return response()->json(['error' => 'Kur bulunamadı'], 422);
        }

        return response()->json([
            'amount' => (float) $validated['amount'],
            'from' => strtoupper($validated['from']),
            'to' => strtoupper($validated['to']),
            'result' => $result,
        ]);
    }

    public function sync(Request $request, ExchangeRateService $rates)
    {
        try {
            $result = $rates->sync(true);

            if ($request->expectsJson() || $request->boolean('ajax')) {
                return response()->json([
                    'success' => true,
                    'message' => __('currencies.refreshed', ['count' => $result['updated']]),
                    'updated_at' => $rates->lastUpdatedLabel(),
                    'rates' => $rates->ratesForBar(),
                    'result' => $result,
                ]);
            }

            return back()->with('success', __('messages.saved') . " ({$result['updated']} kur güncellendi)");
        } catch (\Throwable $e) {
            if ($request->expectsJson() || $request->boolean('ajax')) {
                return response()->json(['success' => false, 'error' => $e->getMessage()], 422);
            }

            return back()->withErrors(['rates' => $e->getMessage()]);
        }
    }
}
