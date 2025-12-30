<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WalletWithdrawalApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_withdraw_decrements_balance_and_creates_out_transaction(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::query()->create([
            'user_id' => $user->id,
            'slug' => null,
            'name' => 'User Wallet',
            'balance' => '100',
        ]);

        $response = $this->postJson('/api/wallets/withdraw', [
            'wallet_id' => $wallet->id,
            'amount' => 30,
            'description' => 'Withdraw test',
        ]);

        $response->assertOk();
        $response->assertJsonPath('data.wallet_id', $wallet->id);
        $response->assertJsonPath('data.balance', '70');

        $wallet->refresh();
        $this->assertSame('70', (string) $wallet->balance);

        $this->assertDatabaseHas('transactions', [
            'wallet_id' => $wallet->id,
            'order_id' => null,
            'type' => 'withdrawal',
            'amount' => '30',
            'flow' => 'out',
        ]);
    }

    public function test_withdraw_fails_when_insufficient_balance(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::query()->create([
            'user_id' => $user->id,
            'slug' => null,
            'name' => 'User Wallet',
            'balance' => '10',
        ]);

        $this->postJson('/api/wallets/withdraw', [
            'wallet_id' => $wallet->id,
            'amount' => 11,
        ])->assertStatus(422);

        $wallet->refresh();
        $this->assertSame('10', (string) $wallet->balance);
        $this->assertSame(0, Transaction::query()->count());
    }
}

