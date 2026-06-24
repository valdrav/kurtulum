<?php

namespace Tests\Feature;

use Tests\FeatureTestCase;

class AuthTest extends FeatureTestCase
{
    public function test_login_page_is_accessible(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk();
        $response->assertSee('Kurtulum');
    }

    public function test_user_can_login_with_valid_credentials(): void
    {
        $user = $this->createAdminUser([
            'email' => 'admin@test.com',
        ]);

        $response = $this->post(route('login'), [
            'email' => 'admin@test.com',
            'password' => 'password',
        ]);

        $response->assertRedirect(route('dashboard'));
        $this->assertAuthenticatedAs($user);
    }

    public function test_user_cannot_login_with_invalid_credentials(): void
    {
        $this->createAdminUser(['email' => 'admin@test.com']);

        $response = $this->post(route('login'), [
            'email' => 'admin@test.com',
            'password' => 'wrong-password',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertGuest();
    }

    public function test_guest_is_redirected_from_dashboard_to_login(): void
    {
        $response = $this->get(route('dashboard'));

        $response->assertRedirect(route('login'));
    }
}
