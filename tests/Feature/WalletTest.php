<?php

namespace Tests\Feature;

use App\Models\CompanyWallet;
use App\Models\User;
use Tests\FeatureTestCase;

class WalletTest extends FeatureTestCase
{
    public function test_admin_can_view_wallet_page(): void
    {
        $admin = $this->actingAsAdmin();

        $response = $this->get(route('finance.wallet'));

        $response->assertOk();
        $response->assertSee(__('finance.wallet'));
        $response->assertSee($admin->name);
    }

    public function test_deposit_increases_wallet_balance(): void
    {
        $admin = $this->actingAsAdmin();

        $wallet = company_wallet()->ensureDefault($admin->id);
        $before = (float) $wallet->current_balance;

        $response = $this->post(route('finance.wallet.transactions.store'), [
            'company_wallet_id' => $wallet->id,
            'type' => 'deposit',
            'description' => 'Şirket avans transferi',
            'amount' => 5000,
            'transaction_date' => now()->toDateString(),
            'counterparty' => 'Kurtulum Ltd.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('wallet_transactions', [
            'company_wallet_id' => $wallet->id,
            'type' => 'deposit',
            'description' => 'Şirket avans transferi',
            'amount' => 5000,
        ]);

        $wallet->refresh();
        $this->assertEquals($before + 5000, (float) $wallet->current_balance);
    }

    public function test_expense_decreases_wallet_balance(): void
    {
        $admin = $this->actingAsAdmin();

        $wallet = CompanyWallet::create([
            'user_id' => $admin->id,
            'name' => 'Test IBAN',
            'currency' => 'TRY',
            'opening_balance' => 1000,
            'current_balance' => 1000,
            'is_active' => true,
        ]);

        $response = $this->post(route('finance.wallet.transactions.store'), [
            'company_wallet_id' => $wallet->id,
            'type' => 'expense',
            'description' => 'Ofis malzemesi',
            'amount' => 150,
            'transaction_date' => now()->toDateString(),
        ]);

        $response->assertRedirect();
        $wallet->refresh();
        $this->assertEquals(850, (float) $wallet->current_balance);
    }

    public function test_delete_transaction_reverses_balance(): void
    {
        $admin = $this->actingAsAdmin();

        $wallet = CompanyWallet::create([
            'user_id' => $admin->id,
            'name' => 'Test',
            'currency' => 'TRY',
            'opening_balance' => 0,
            'current_balance' => 0,
            'is_active' => true,
        ]);

        $entry = company_wallet()->recordTransaction(
            $wallet,
            'deposit',
            200,
            'Test giriş',
            now()->toDateString(),
        );

        $wallet->refresh();
        $this->assertEquals(200, (float) $wallet->current_balance);

        $this->delete(route('finance.wallet.transactions.destroy', $entry))->assertRedirect();

        $wallet->refresh();
        $this->assertEquals(0, (float) $wallet->current_balance);
        $this->assertSoftDeleted('wallet_transactions', ['id' => $entry->id]);
    }

    public function test_each_user_has_separate_wallet(): void
    {
        $admin = $this->actingAsAdmin();
        $walletAdmin = company_wallet()->ensureDefault($admin->id);

        $manager = User::factory()->create(['is_active' => true]);
        $manager->assignRole('manager');

        $this->actingAs($manager);
        $walletManager = company_wallet()->ensureDefault($manager->id);

        $this->assertNotEquals($walletAdmin->id, $walletManager->id);
        $this->assertEquals($admin->id, $walletAdmin->user_id);
        $this->assertEquals($manager->id, $walletManager->user_id);
    }

    public function test_user_cannot_post_to_another_users_wallet(): void
    {
        $admin = $this->actingAsAdmin();
        $adminWallet = company_wallet()->ensureDefault($admin->id);

        $manager = User::factory()->create(['is_active' => true]);
        $manager->assignRole('manager');

        $this->actingAs($manager);

        $response = $this->post(route('finance.wallet.transactions.store'), [
            'company_wallet_id' => $adminWallet->id,
            'type' => 'deposit',
            'description' => 'Yetkisiz giriş',
            'amount' => 100,
            'transaction_date' => now()->toDateString(),
        ]);

        $response->assertSessionHasErrors('company_wallet_id');
    }
}
