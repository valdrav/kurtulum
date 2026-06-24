<?php

namespace Tests\Feature;

use App\Models\Port;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PortTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_create_port_via_api(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->postJson(route('ports.store'), [
            'name' => 'Sarp Sınır Kapısı',
            'country' => 'TR',
            'city' => 'Artvin',
            'type' => 'land',
        ]);

        $response->assertCreated()
            ->assertJsonPath('port.name', 'Sarp Sınır Kapısı')
            ->assertJsonPath('port.type', 'land');

        $this->assertDatabaseHas('ports', [
            'name' => 'Sarp Sınır Kapısı',
            'country' => 'TR',
            'type' => 'land',
        ]);
    }

    public function test_port_code_is_generated_when_missing(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->postJson(route('ports.store'), [
            'name' => 'Kapıkule',
            'country' => 'TR',
            'type' => 'land',
        ])->assertCreated();

        $port = Port::where('name', 'Kapıkule')->first();
        $this->assertNotNull($port);
        $this->assertStringStartsWith('TR', $port->code);
    }
}
