<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class TransactionApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_returns_single_transaction_with_wallet(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::query()->create([
            'user_id' => $user->id,
            'slug' => null,
            'name' => 'User Wallet',
            'balance' => '123',
        ]);

        $transaction = Transaction::query()->create([
            'wallet_id' => $wallet->id,
            'order_id' => null,
            'type' => 'deposit',
            'amount' => '10',
            'flow' => 'in',
            'description' => 'test',
        ]);

        $response = $this->getJson("/api/transactions/{$transaction->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', $transaction->id);
        $response->assertJsonPath('data.wallet_id', $wallet->id);
        $response->assertJsonPath('data.type', 'deposit');
        $response->assertJsonPath('data.wallet.name', 'User Wallet');
        $response->assertJsonPath('data.wallet.balance', '123');
    }

    private function createTransaction(array $attributes, string $createdAt): Transaction
    {
        $transaction = Transaction::query()->create($attributes);

        $timestamp = Carbon::parse($createdAt);
        $transaction->forceFill([
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ])->saveQuietly();

        return $transaction;
    }

    public function test_index_is_paginated_and_filterable(): void
    {
        $user = User::factory()->create();
        $walletA = Wallet::query()->create([
            'user_id' => $user->id,
            'slug' => null,
            'name' => 'User Wallet',
            'balance' => '0',
        ]);
        $walletB = Wallet::query()->create([
            'user_id' => null,
            'slug' => 'commission',
            'name' => 'Commission Wallet',
            'balance' => '0',
        ]);

        $this->createTransaction([
            'wallet_id' => $walletA->id,
            'order_id' => null,
            'type' => 'deposit',
            'amount' => '10',
            'flow' => 'in',
            'description' => null,
        ], '2025-12-01 10:00:00');

        $this->createTransaction([
            'wallet_id' => $walletB->id,
            'order_id' => null,
            'type' => 'platform_commission',
            'amount' => '2',
            'flow' => 'in',
            'description' => null,
        ], '2025-12-01 11:00:00');

        $response = $this->getJson('/api/transactions?per_page=1');
        $response->assertOk();
        $response->assertJsonPath('meta.per_page', 1);
        $response->assertJsonPath('meta.total', 2);

        $filtered = $this->getJson('/api/transactions?wallet_id='.$walletB->id);
        $filtered->assertOk();
        $this->assertCount(1, $filtered->json('data'));
        $filtered->assertJsonPath('data.0.wallet_id', $walletB->id);
        $filtered->assertJsonPath('data.0.type', 'platform_commission');
        $filtered->assertJsonPath('data.0.wallet.name', 'Commission Wallet');
        $filtered->assertJsonPath('data.0.wallet.balance', '0');
        $filtered->assertJsonMissingPath('data.0.wallet.id');
        $filtered->assertJsonMissingPath('data.0.wallet.user_id');
        $filtered->assertJsonMissingPath('data.0.wallet.slug');
        $filtered->assertJsonMissingPath('data.0.wallet.is_system');

        $filtered->assertJsonMissingPath('data.0.order');
    }
}
