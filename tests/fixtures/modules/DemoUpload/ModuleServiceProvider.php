<?php

namespace Modules\DemoUpload;

class ModuleServiceProvider
{
    public function boot(): void {}

    public function registerPermissions(): array
    {
        return ['demo-upload.view'];
    }
}
