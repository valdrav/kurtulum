<?php

namespace Tests;

use App\Models\User;
use Database\Seeders\ExtensibilitySeeder;
use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class FeatureTestCase extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(RolesAndPermissionsSeeder::class);
        $this->seed(ExtensibilitySeeder::class);
    }

    protected function createAdminUser(array $attributes = []): User
    {
        $user = User::factory()->create(array_merge([
            'is_active' => true,
            'locale' => 'tr',
            'theme' => 'light',
        ], $attributes));

        $user->assignRole('super-admin');

        return $user;
    }

    protected function actingAsAdmin(array $attributes = []): User
    {
        $user = $this->createAdminUser($attributes);
        $this->actingAs($user);

        return $user;
    }
}
