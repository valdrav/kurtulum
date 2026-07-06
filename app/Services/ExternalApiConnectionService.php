<?php

namespace App\Services;

use App\Models\ExternalApiConnection;
use Illuminate\Support\Str;

class ExternalApiConnectionService
{
    /** @return array{connection: ExternalApiConnection, plain_token: string} */
    public function create(array $data, int $createdBy): array
    {
        $plainToken = $this->generatePlainToken();

        $connection = ExternalApiConnection::create([
            'name' => $data['name'],
            'customer_id' => $data['customer_id'],
            'token_prefix' => substr($plainToken, 0, 16),
            'token_hash' => $this->hashToken($plainToken),
            'is_active' => $data['is_active'] ?? true,
            'view_customer' => $data['view_customer'] ?? true,
            'view_directory' => $data['view_directory'] ?? false,
            'view_orders' => $data['view_orders'] ?? false,
            'view_shipments' => $data['view_shipments'] ?? false,
            'view_shipment_costs' => $data['view_shipment_costs'] ?? false,
            'created_by' => $createdBy,
        ]);

        return [
            'connection' => $connection->load('customer'),
            'plain_token' => $plainToken,
        ];
    }

    public function update(ExternalApiConnection $connection, array $data): ExternalApiConnection
    {
        $connection->update([
            'name' => $data['name'],
            'customer_id' => $data['customer_id'],
            'is_active' => $data['is_active'] ?? false,
            'view_customer' => $data['view_customer'] ?? true,
            'view_directory' => $data['view_directory'] ?? false,
            'view_orders' => $data['view_orders'] ?? false,
            'view_shipments' => $data['view_shipments'] ?? false,
            'view_shipment_costs' => $data['view_shipment_costs'] ?? false,
        ]);

        return $connection->fresh('customer');
    }

    /** @return array{connection: ExternalApiConnection, plain_token: string} */
    public function regenerateToken(ExternalApiConnection $connection): array
    {
        $plainToken = $this->generatePlainToken();

        $connection->update([
            'token_prefix' => substr($plainToken, 0, 16),
            'token_hash' => $this->hashToken($plainToken),
        ]);

        return [
            'connection' => $connection->fresh('customer'),
            'plain_token' => $plainToken,
        ];
    }

    public function hashToken(string $plainToken): string
    {
        return hash('sha256', $plainToken);
    }

    protected function generatePlainToken(): string
    {
        return 'ef_'.Str::random(48);
    }
}
