<?php

declare(strict_types=1);

namespace App\Http\Resources;

use App\Models\Transaction;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Transaction
 */
class TransactionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'wallet_id' => $this->wallet_id,
            'order_id' => $this->order_id,
            'type' => $this->type,
            'flow' => $this->flow,
            'amount' => (string) $this->amount,
            'description' => $this->description,
            'wallet' => TransactionWalletResource::make($this->whenLoaded('wallet')),
        ];
    }
}
