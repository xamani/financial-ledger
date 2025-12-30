<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

class FinancialReportApiTest extends TestCase
{
    use RefreshDatabase;

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

    public function test_index_returns_aggregated_totals_and_by_type(): void
    {
        $user = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'total_amount' => '1000',
            'status' => 'completed',
            'completed_at' => now(),
        ]);
        $wallet = Wallet::query()->create([
            'user_id' => $user->id,
            'slug' => null,
            'name' => 'User Wallet',
            'balance' => '0',
        ]);

        $this->createTransaction([
            'wallet_id' => $wallet->id,
            'order_id' => $order->id,
            'type' => 'deposit',
            'amount' => '100',
            'flow' => 'in',
            'description' => null,
        ], '2025-12-15 10:00:00');

        $this->createTransaction([
            'wallet_id' => $wallet->id,
            'order_id' => $order->id,
            'type' => 'post_cost',
            'amount' => '60',
            'flow' => 'out',
            'description' => null,
        ], '2025-12-16 10:00:00');

        $this->createTransaction([
            'wallet_id' => $wallet->id,
            'order_id' => $order->id,
            'type' => 'deposit',
            'amount' => '999',
            'flow' => 'in',
            'description' => null,
        ], '2026-02-01 10:00:00');

        $response = $this->getJson('/api/financial-reports?start_date=2025-12-01&end_date=2026-01-01');

        $response->assertOk();
        $response->assertJsonPath('data.totals.volume', '160');
        $response->assertJsonPath('data.totals.inflow', '100');
        $response->assertJsonPath('data.totals.outflow', '60');

        $response->assertJsonPath('data.by_type.deposit.volume', '100');
        $response->assertJsonPath('data.by_type.deposit.net', '100');
        $response->assertJsonPath('data.by_type.post_cost.volume', '60');
        $response->assertJsonPath('data.by_type.post_cost.net', '-60');
    }

    public function test_chart_groups_by_day(): void
    {
        $user = User::factory()->create();
        $wallet = Wallet::query()->create([
            'user_id' => $user->id,
            'slug' => null,
            'name' => 'User Wallet',
            'balance' => '0',
        ]);

        $this->createTransaction([
            'wallet_id' => $wallet->id,
            'order_id' => null,
            'type' => 'deposit',
            'amount' => '100',
            'flow' => 'in',
            'description' => null,
        ], '2025-12-01 10:00:00');

        $this->createTransaction([
            'wallet_id' => $wallet->id,
            'order_id' => null,
            'type' => 'deposit',
            'amount' => '40',
            'flow' => 'out',
            'description' => null,
        ], '2025-12-01 12:00:00');

        $this->createTransaction([
            'wallet_id' => $wallet->id,
            'order_id' => null,
            'type' => 'deposit',
            'amount' => '10',
            'flow' => 'in',
            'description' => null,
        ], '2025-12-02 10:00:00');

        $response = $this->getJson('/api/financial-reports/chart?start_date=2025-12-01&end_date=2025-12-02&granularity=day');

        $response->assertOk();
        $response->assertJsonPath('data.series.0.period', '2025-12-01');
        $response->assertJsonPath('data.series.0.inflow', '100');
        $response->assertJsonPath('data.series.0.outflow', '40');
        $response->assertJsonPath('data.series.0.net', '60');

        $response->assertJsonPath('data.series.1.period', '2025-12-02');
        $response->assertJsonPath('data.series.1.inflow', '10');
        $response->assertJsonPath('data.series.1.outflow', '0');
        $response->assertJsonPath('data.series.1.net', '10');
    }
}
