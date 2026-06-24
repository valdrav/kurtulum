<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InstallTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        config(['ticari.installed' => false]);
    }

    public function test_uninstalled_app_redirects_to_installer(): void
    {
        $response = $this->get('/');

        $response->assertRedirect(route('install.welcome'));
    }

    public function test_install_welcome_page_is_accessible_when_not_installed(): void
    {
        $response = $this->get(route('install.welcome'));

        $response->assertOk();
        $response->assertSee('Kurulum');
    }

    public function test_install_requirements_page_works(): void
    {
        $response = $this->get(route('install.requirements'));

        $response->assertOk();
        $response->assertSee('PHP');
    }
}
