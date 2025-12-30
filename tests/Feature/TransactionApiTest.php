<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TransactionApiTest extends TestCase
{
    use RefreshDatabase;

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

        Transaction::query()->create([
            'wallet_id' => $walletA->id,
            'order_id' => null,
            'type' => 'deposit',
            'amount' => '10',
            'flow' => 'in',
            'description' => null,
            'created_at' => '2025-12-01 10:00:00',
            'updated_at' => '2025-12-01 10:00:00',
        ]);

        Transaction::query()->create([
            'wallet_id' => $walletB->id,
            'order_id' => null,
            'type' => 'platform_commission',
            'amount' => '2',
            'flow' => 'in',
            'description' => null,
            'created_at' => '2025-12-01 11:00:00',
            'updated_at' => '2025-12-01 11:00:00',
        ]);

        $response = $this->getJson('/api/transactions?per_page=1');
        $response->assertOk();
        $response->assertJsonPath('meta.per_page', 1);
        $response->assertJsonPath('meta.total', 2);

        $filtered = $this->getJson('/api/transactions?wallet_id='.$walletB->id);
        $filtered->assertOk();
        $this->assertCount(1, $filtered->json('data'));
        $filtered->assertJsonPath('data.0.wallet_id', $walletB->id);
        $filtered->assertJsonPath('data.0.type', 'platform_commission');
    }
}

