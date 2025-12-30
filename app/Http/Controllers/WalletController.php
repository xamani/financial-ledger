<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\WalletWithdrawRequest;
use App\Models\Transaction;
use App\Models\Wallet;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\DB;

class WalletController extends Controller
{
    /**
     * @OA\Post(
     *     path="/api/wallets/withdraw",
     *     tags={"Wallets"},
     *     summary="Withdraw from a wallet (creates an OUT transaction and decrements balance)",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(
     *             required={"wallet_id","amount"},
     *             @OA\Property(property="wallet_id", type="integer", example=1),
     *             @OA\Property(property="amount", type="integer", example=50000),
     *             @OA\Property(property="description", type="string", example="User withdrawal")
     *         )
     *     ),
     *     @OA\Response(response=200, description="OK"),
     *     @OA\Response(response=422, description="Validation/insufficient balance")
     * )
     */
    public function withdraw(WalletWithdrawRequest $request): JsonResponse
    {
        $data = $request->validated();

        return DB::transaction(function () use ($data): JsonResponse {
            /** @var Wallet $wallet */
            $wallet = Wallet::query()->lockForUpdate()->findOrFail($data['wallet_id']);

            $amount = (string) $data['amount'];
            $currentBalance = (string) $wallet->balance;

            if ($this->compareMoney($currentBalance, $amount) < 0) {
                return response()->json([
                    'message' => 'Insufficient wallet balance.',
                ], 422);
            }

            $wallet->forceFill([
                'balance' => bcsub($currentBalance, $amount, 0),
            ])->save();

            Transaction::query()->create([
                'wallet_id' => $wallet->id,
                'order_id' => null,
                'type' => 'withdrawal',
                'amount' => $amount,
                'flow' => 'out',
                'description' => $data['description'] ?? null,
            ]);

            return response()->json([
                'message' => 'Withdrawal processed.',
                'data' => [
                    'wallet_id' => $wallet->id,
                    'balance' => (string) $wallet->balance,
                ],
            ]);
        });
    }

    private function compareMoney(string $left, string $right): int
    {
        return bccomp($left, $right, 0);
    }
}

