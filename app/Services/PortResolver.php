<?php

namespace App\Services;

use App\Models\Port;
use Illuminate\Support\Facades\Cache;

class PortResolver
{
    public function resolve(?string $hint): ?Port
    {
        $hint = trim((string) $hint);

        if ($hint === '') {
            return null;
        }

        $key = strtoupper(preg_replace('/\s+/', '', $hint));

        return Cache::remember("port.resolve.{$key}", 3600, function () use ($key, $hint) {
            $port = Port::query()->where('code', $key)->first();

            if ($port) {
                return $port;
            }

            $aliasCode = config("ticari.ais_port_aliases.{$key}");

            if ($aliasCode) {
                $port = Port::query()->where('code', strtoupper($aliasCode))->first();

                if ($port) {
                    return $port;
                }
            }

            if (strlen($key) === 5 && ctype_alpha($key)) {
                $port = Port::query()
                    ->where('code', 'LIKE', '%' . substr($key, 2))
                    ->where('country', substr($key, 0, 2))
                    ->first();

                if ($port) {
                    return $port;
                }
            }

            return Port::query()
                ->where(function ($q) use ($key, $hint) {
                    $q->where('code', 'LIKE', "%{$key}%")
                        ->orWhereRaw('UPPER(name) LIKE ?', ['%' . strtoupper($hint) . '%']);
                })
                ->orderByRaw('CASE WHEN code = ? THEN 0 ELSE 1 END', [$key])
                ->first();
        });
    }

    public function label(?Port $port = null, ?string $hint = null): ?string
    {
        $port ??= $this->resolve($hint ?? '');

        if ($port) {
            return $this->format($port->country, $port->name, $port->code);
        }

        $hint = trim((string) ($hint ?? ''));

        if ($hint === '') {
            return null;
        }

        $key = strtoupper(preg_replace('/\s+/', '', $hint));
        $place = config("ticari.ais_port_places.{$key}");

        if (is_array($place) && ! empty($place['name'])) {
            return $this->format($place['country'] ?? null, $place['name'], $place['code'] ?? $key);
        }

        return $hint;
    }

    protected function format(?string $countryCode, string $name, ?string $code = null): string
    {
        $country = country_label($countryCode);
        $label = $country !== '' ? "{$country} — {$name}" : $name;

        if ($code && strtoupper($code) !== strtoupper(preg_replace('/\s+/', '', $name))) {
            $label .= " ({$code})";
        }

        return $label;
    }
}
