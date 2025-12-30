<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Order;
use App\Models\Transaction;
use App\Models\Wallet;
use App\Services\FinancialCalculatorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class OrderController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/orders",
     *     tags={"Orders"},
     *     summary="Create a pending order",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"user_id","total_amount"},
     *             @OA\Property(property="user_id", type="integer", example=1),
     *             @OA\Property(property="total_amount", type="integer", example=100000)
     *         )
     *     ),
     *     @OA\Response(
     *         response=201,
     *         description="Created"
     *     ),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function store(Request $request): JsonResponse
    {
        $data = $request->validate([
            'user_id' => ['required', 'integer', 'exists:users,id'],
            'total_amount' => ['required', 'integer', 'min:1'],
        ]);

        $order = Order::query()->create([
            'user_id' => $data['user_id'],
            'total_amount' => (string) $data['total_amount'],
            'status' => 'pending',
        ]);

        return response()->json([
            'data' => $order,
        ], 201);
    }

    /**
     * @OA\Post(
     *     path="/api/orders/{order}/pay",
     *     tags={"Orders"},
     *     summary="Get a mock payment URL for an order",
     *     @OA\Parameter(
     *         name="order",
     *         in="path",
     *         required=true,
     *         @OA\Schema(type="integer")
     *     ),
     *     @OA\Response(response=200, description="OK"),
     *     @OA\Response(response=409, description="Order not payable")
     * )
     */
    public function pay(Order $order): JsonResponse
    {
        if ($order->status === 'completed') {
            return response()->json(['message' => 'Order already completed.'], 409);
        }

        if ($order->status === 'cancelled') {
            return response()->json(['message' => 'Order is cancelled.'], 409);
        }

        $callbackUrl = route('orders.callback');
        $paymentUrl = "https://mock-bank.test/pay?order_id={$order->id}&callback_url=".urlencode($callbackUrl);

        return response()->json([
            'data' => [
                'order_id' => $order->id,
                'payment_url' => $paymentUrl,
            ],
        ]);
    }

    /**
     * @OA\Post(
     *     path="/api/orders/callback",
     *     tags={"Orders"},
     *     summary="Mock bank callback to finalize payment",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"order_id","status"},
     *             @OA\Property(property="order_id", type="integer", example=1),
     *             @OA\Property(property="status", type="string", enum={"success","failed"}, example="success")
     *         )
     *     ),
     *     @OA\Response(response=200, description="Processed"),
     *     @OA\Response(response=409, description="Order already completed/cancelled"),
     *     @OA\Response(response=422, description="Payment failed or validation error")
     * )
     */
    public function callback(Request $request, FinancialCalculatorService $calculator): JsonResponse
    {
        $data = $request->validate([
            'order_id' => ['required', 'integer', 'exists:orders,id'],
            'status' => ['required', Rule::in(['success', 'failed'])],
        ]);

        return DB::transaction(function () use ($data, $calculator): JsonResponse {
            /** @var Order $order */
            $order = Order::query()
                ->whereKey($data['order_id'])
                ->lockForUpdate()
                ->firstOrFail();

            if ($order->status === 'completed') {
                return response()->json([
                    'message' => 'Order already completed.',
                    'data' => $order,
                ]);
            }

            if ($order->status === 'cancelled') {
                return response()->json(['message' => 'Order is cancelled.'], 409);
            }

            if ($data['status'] !== 'success') {
                return response()->json(['message' => 'Payment failed.'], 422);
            }

            $order->forceFill([
                'status' => 'completed',
                'completed_at' => now(),
            ])->save();

            $breakdown = $calculator->calculateBreakdown((float) $order->total_amount);

            $this->processShare($order, 'commission', $breakdown['platform_commission'], 'platform_commission');
            $this->processShare($order, 'post_cost', $breakdown['post_cost'], 'post_cost');
            $this->processShare($order, 'temp_wallet', $breakdown['temporary_wallet'], 'temp_wallet');
            $this->processShare($order, 'insurance', $breakdown['insurance'], 'insurance');
            $this->processDriverShare($order, $breakdown['driver_share'], 'driver_share');

            return response()->json([
                'message' => 'Payment processed.',
                'data' => $order->fresh(),
            ]);
        });
    }

    private function processShare(Order $order, string $walletSlug, string $amount, string $type): void
    {
        if ($this->isZero($amount)) {
            return;
        }

        $wallet = Wallet::query()
            ->whereNull('user_id')
            ->where('slug', $walletSlug)
            ->lockForUpdate()
            ->first();

        if (! $wallet) {
            $wallet = Wallet::query()->create([
                'user_id' => null,
                'slug' => $walletSlug,
                'name' => str($walletSlug)->replace('_', ' ')->title().' Wallet',
                'balance' => '0',
            ]);
        }

        $wallet->forceFill([
            'balance' => $this->addMoney((string) $wallet->balance, $amount),
        ])->save();

        Transaction::query()->create([
            'wallet_id' => $wallet->id,
            'order_id' => $order->id,
            'type' => $type,
            'amount' => $amount,
            'flow' => 'in',
            'description' => "Order #{$order->id} allocation: {$type}",
        ]);
    }

    private function processDriverShare(Order $order, string $amount, string $type): void
    {
        if ($this->isZero($amount)) {
            return;
        }

        $wallet = Wallet::query()
            ->where('user_id', $order->user_id)
            ->lockForUpdate()
            ->first();

        if (! $wallet) {
            $wallet = Wallet::query()->create([
                'user_id' => $order->user_id,
                'slug' => null,
                'name' => 'User Wallet',
                'balance' => '0',
            ]);
        }

        $wallet->forceFill([
            'balance' => $this->addMoney((string) $wallet->balance, $amount),
        ])->save();

        Transaction::query()->create([
            'wallet_id' => $wallet->id,
            'order_id' => $order->id,
            'type' => $type,
            'amount' => $amount,
            'flow' => 'in',
            'description' => "Order #{$order->id} allocation: {$type}",
        ]);
    }

    private function isZero(string $amount): bool
    {
        return ltrim($amount, '0') === '';
    }

    private function addMoney(string $left, string $right): string
    {
        return bcadd($left, $right, 0);
    }
}
