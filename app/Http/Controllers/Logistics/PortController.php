<?php

namespace App\Http\Controllers\Logistics;

use App\Http\Controllers\Controller;
use App\Models\Port;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;

class PortController extends Controller
{
    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'country' => 'required|string|size:2',
            'city' => 'nullable|string|max:100',
            'code' => 'nullable|string|max:20|unique:ports,code',
            'type' => ['required', Rule::in(['sea', 'air', 'rail', 'land', 'road'])],
        ]);

        $code = isset($validated['code']) && trim($validated['code']) !== ''
            ? strtoupper(trim($validated['code']))
            : $this->generatePortCode($validated['name'], $validated['country']);

        $port = Port::create([
            'name' => trim($validated['name']),
            'country' => strtoupper($validated['country']),
            'city' => isset($validated['city']) ? trim($validated['city']) : null,
            'code' => $code,
            'type' => $validated['type'],
        ]);

        return response()->json([
            'message' => __('logistics.port_saved'),
            'port' => $this->portPayload($port),
        ], 201);
    }

    protected function portPayload(Port $port): array
    {
        return [
            'id' => $port->id,
            'name' => $port->name,
            'code' => $port->code,
            'type' => $port->type,
            'country' => $port->country,
            'label' => port_display_label($port),
        ];
    }

    protected function generatePortCode(string $name, string $country): string
    {
        $country = strtoupper(substr($country, 0, 2));
        $ascii = Str::ascii($name);
        $letters = strtoupper(preg_replace('/[^A-Z]/', '', $ascii));
        $suffix = substr($letters, 0, 3) ?: 'LOC';
        $code = $country . $suffix;
        $base = $code;
        $i = 2;

        while (Port::where('code', $code)->exists()) {
            $code = $base . $i;
            $i++;
        }

        return $code;
    }
}
