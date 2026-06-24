<?php

namespace App\Core;

class HookManager
{
    protected array $hooks = [];

    public function register(string $name, callable $callback, int $priority = 10): void
    {
        $this->hooks[$name][$priority][] = $callback;
    }

    public function fire(string $name, mixed $payload = null): mixed
    {
        if (!isset($this->hooks[$name])) {
            return $payload;
        }

        ksort($this->hooks[$name]);

        foreach ($this->hooks[$name] as $callbacks) {
            foreach ($callbacks as $callback) {
                $payload = $callback($payload);
            }
        }

        return $payload;
    }

    public function filter(string $name, mixed $value, mixed ...$args): mixed
    {
        if (!isset($this->hooks[$name])) {
            return $value;
        }

        ksort($this->hooks[$name]);

        foreach ($this->hooks[$name] as $callbacks) {
            foreach ($callbacks as $callback) {
                $value = $callback($value, ...$args);
            }
        }

        return $value;
    }

    public function has(string $name): bool
    {
        return isset($this->hooks[$name]) && count($this->hooks[$name]) > 0;
    }

    public function clear(string $name): void
    {
        unset($this->hooks[$name]);
    }
}
