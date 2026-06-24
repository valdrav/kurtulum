<?php

namespace App\Core\Contracts;

interface ModuleInterface
{
    public function getSlug(): string;

    public function getName(): string;

    public function getVersion(): string;

    public function boot(): void;

    public function registerRoutes(): void;

    public function registerPermissions(): array;

    public function registerMenuItems(): array;
}
