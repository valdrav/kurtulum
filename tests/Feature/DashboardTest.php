<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;

class DashboardTest extends FeatureTestCase
{
    public function test_authenticated_user_can_view_dashboard(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('dashboard'));

        $response->assertOk();
    }

    public function test_dashboard_shows_stat_cards(): void
    {
        $this->actingAsAdmin();

        $response = $this->get(route('dashboard'));

        $response->assertOk();
        $response->assertSee(__('app.customers'));
        $response->assertSee(__('app.orders'));
    }
}
