<?php

namespace App\Http\Controllers;

use App\Services\AiService;
use Illuminate\Http\Request;

class AiController extends Controller
{
    public function __construct(protected AiService $ai) {}

    public function index()
    {
        return view('ai.index', ['configured' => $this->ai->isConfigured()]);
    }

    public function generateEmail(Request $request)
    {
        $validated = $request->validate([
            'context' => 'required|string|max:5000',
            'tone' => 'nullable|string|max:50',
            'language' => 'nullable|in:tr,en,ar',
        ]);

        $result = $this->ai->generateEmail(
            $validated['context'],
            $validated['tone'] ?? 'professional',
            $validated['language'] ?? app()->getLocale()
        );

        return response()->json(['result' => $result ?? __('ai.not_configured')]);
    }

    public function summarizeReport(Request $request)
    {
        $validated = $request->validate(['data' => 'required|string|max:10000']);
        $result = $this->ai->summarizeReport($validated['data'], app()->getLocale());
        return response()->json(['result' => $result ?? __('ai.not_configured')]);
    }

    public function operationSuggestions(Request $request)
    {
        $validated = $request->validate(['shipment' => 'required|array']);
        $result = $this->ai->operationSuggestions($validated['shipment'], app()->getLocale());
        return response()->json(['result' => $result ?? __('ai.not_configured')]);
    }

    public function financialAnalysis(Request $request)
    {
        $validated = $request->validate(['data' => 'required|array']);
        $result = $this->ai->financialAnalysis($validated['data'], app()->getLocale());
        return response()->json(['result' => $result ?? __('ai.not_configured')]);
    }

    public function translate(Request $request)
    {
        $validated = $request->validate([
            'text' => 'required|string|max:5000',
            'from' => 'required|string|max:5',
            'to' => 'required|string|max:5',
        ]);

        $result = $this->ai->translate($validated['text'], $validated['from'], $validated['to']);
        return response()->json(['result' => $result ?? __('ai.not_configured')]);
    }
}
