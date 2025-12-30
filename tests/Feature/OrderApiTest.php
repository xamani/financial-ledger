<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Models\Order;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class OrderApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_show_returns_single_order(): void
    {
        $user = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'total_amount' => '1000',
            'status' => 'pending',
            'completed_at' => null,
        ]);

        $response = $this->getJson("/api/orders/{$order->id}");

        $response->assertOk();
        $response->assertJsonPath('data.id', $order->id);
        $response->assertJsonPath('data.user_id', $user->id);
        $response->assertJsonPath('data.total_amount', '1000');
        $response->assertJsonPath('data.status', 'pending');
    }

    public function test_store_creates_a_pending_order(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/orders', [
            'user_id' => $user->id,
            'total_amount' => 1000,
        ]);

        $response->assertCreated();
        $this->assertDatabaseHas('orders', [
            'user_id' => $user->id,
            'status' => 'pending',
            'total_amount' => '1000',
        ]);
    }

    public function test_pay_returns_a_mock_payment_url(): void
    {
        $user = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'total_amount' => '1000',
            'status' => 'pending',
        ]);

        $response = $this->postJson("/api/orders/{$order->id}/pay");

        $response->assertOk();
        $response->assertJsonPath('data.order_id', $order->id);
        $this->assertStringContainsString('https://mock-bank.test/pay', $response->json('data.payment_url'));
        $this->assertStringContainsString('order_id='.$order->id, $response->json('data.payment_url'));
    }

    public function test_pay_returns_conflict_when_order_is_completed(): void
    {
        $user = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'total_amount' => '1000',
            'status' => 'completed',
            'completed_at' => now(),
        ]);

        $this->postJson("/api/orders/{$order->id}/pay")->assertStatus(409);
    }

    public function test_callback_success_completes_order_creates_transactions_and_updates_balances(): void
    {
        $user = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'total_amount' => '1000',
            'status' => 'pending',
        ]);

        $response = $this->postJson('/api/orders/callback', [
            'order_id' => $order->id,
            'status' => 'success',
        ]);

        $response->assertOk();

        $order->refresh();
        $this->assertSame('completed', $order->status);
        $this->assertNotNull($order->completed_at);

        $this->assertDatabaseCount('transactions', 5);

        $this->assertDatabaseHas('transactions', [
            'order_id' => $order->id,
            'type' => 'platform_commission',
            'amount' => '150',
            'flow' => 'in',
        ]);
        $this->assertDatabaseHas('transactions', [
            'order_id' => $order->id,
            'type' => 'post_cost',
            'amount' => '300',
            'flow' => 'in',
        ]);
        $this->assertDatabaseHas('transactions', [
            'order_id' => $order->id,
            'type' => 'temp_wallet',
            'amount' => '50',
            'flow' => 'in',
        ]);
        $this->assertDatabaseHas('transactions', [
            'order_id' => $order->id,
            'type' => 'insurance',
            'amount' => '50',
            'flow' => 'in',
        ]);
        $this->assertDatabaseHas('transactions', [
            'order_id' => $order->id,
            'type' => 'driver_share',
            'amount' => '450',
            'flow' => 'in',
        ]);

        $commissionWallet = Wallet::query()->whereNull('user_id')->where('slug', 'commission')->firstOrFail();
        $this->assertSame('150', (string) $commissionWallet->balance);

        $postWallet = Wallet::query()->whereNull('user_id')->where('slug', 'post_cost')->firstOrFail();
        $this->assertSame('300', (string) $postWallet->balance);

        $tempWallet = Wallet::query()->whereNull('user_id')->where('slug', 'temp_wallet')->firstOrFail();
        $this->assertSame('50', (string) $tempWallet->balance);

        $insuranceWallet = Wallet::query()->whereNull('user_id')->where('slug', 'insurance')->firstOrFail();
        $this->assertSame('50', (string) $insuranceWallet->balance);

        $driverWallet = Wallet::query()->where('user_id', $user->id)->firstOrFail();
        $this->assertSame('450', (string) $driverWallet->balance);
    }

    public function test_callback_is_idempotent_for_completed_order(): void
    {
        $user = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'total_amount' => '1000',
            'status' => 'pending',
        ]);

        $this->postJson('/api/orders/callback', [
            'order_id' => $order->id,
            'status' => 'success',
        ])->assertOk();

        $this->assertDatabaseCount('transactions', 5);

        $this->postJson('/api/orders/callback', [
            'order_id' => $order->id,
            'status' => 'success',
        ])->assertOk()->assertJsonPath('message', 'Order already completed.');

        $this->assertDatabaseCount('transactions', 5);
    }

    public function test_callback_failed_payment_returns_422_and_does_not_mutate_order(): void
    {
        $user = User::factory()->create();
        $order = Order::query()->create([
            'user_id' => $user->id,
            'total_amount' => '1000',
            'status' => 'pending',
        ]);

        $this->postJson('/api/orders/callback', [
            'order_id' => $order->id,
            'status' => 'failed',
        ])->assertStatus(422);

        $order->refresh();
        $this->assertSame('pending', $order->status);
        $this->assertNull($order->completed_at);

        $this->assertSame(0, Transaction::query()->count());
    }
}
