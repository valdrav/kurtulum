<?php

namespace Tests\Feature;

use App\Models\Customer;
use Tests\FeatureTestCase;

class CustomerTest extends FeatureTestCase
{
    public function test_admin_can_list_customers(): void
    {
        $this->actingAsAdmin();
        Customer::create([
            'company_name' => 'Test Export Ltd.',
            'type' => 'buyer',
            'status' => 'active',
            'currency' => 'USD',
        ]);

        $response = $this->get(route('customers.index'));

        $response->assertOk();
        $response->assertSee('Test Export Ltd.');
    }

    public function test_admin_can_create_customer(): void
    {
        $this->actingAsAdmin();

        $response = $this->post(route('customers.store'), [
            'company_name' => 'Acme Trading',
            'contact_person' => 'John Doe',
            'email' => 'john@acme.com',
            'type' => 'buyer',
            'status' => 'active',
            'currency' => 'USD',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('customers', [
            'company_name' => 'Acme Trading',
            'email' => 'john@acme.com',
        ]);
    }

    public function test_admin_can_view_customer_detail(): void
    {
        $this->actingAsAdmin();
        $customer = Customer::create([
            'company_name' => 'Detail Corp',
            'type' => 'buyer',
            'status' => 'active',
            'currency' => 'EUR',
        ]);

        $response = $this->get(route('customers.show', $customer));

        $response->assertOk();
        $response->assertSee('Detail Corp');
    }
}
