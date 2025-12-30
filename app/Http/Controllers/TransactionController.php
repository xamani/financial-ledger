<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\TransactionIndexRequest;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;

class TransactionController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/transactions",
     *     tags={"Transactions"},
     *     summary="List transactions (paginated)",
     *     @OA\Parameter(name="wallet_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="order_id", in="query", required=false, @OA\Schema(type="integer")),
     *     @OA\Parameter(name="type", in="query", required=false, @OA\Schema(type="string", example="platform_commission")),
     *     @OA\Parameter(name="flow", in="query", required=false, @OA\Schema(type="string", enum={"in","out"})),
     *     @OA\Parameter(name="start_date", in="query", required=false, @OA\Schema(type="string", format="date", example="2025-01-01")),
     *     @OA\Parameter(name="end_date", in="query", required=false, @OA\Schema(type="string", format="date", example="2025-01-31")),
     *     @OA\Parameter(name="per_page", in="query", required=false, @OA\Schema(type="integer", example=25)),
     *     @OA\Response(response=200, description="OK"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function index(TransactionIndexRequest $request): JsonResponse
    {
        $data = $request->validated();

        $query = Transaction::query()
            ->with(['wallet', 'order'])
            ->orderByDesc('id');

        if (isset($data['wallet_id'])) {
            $query->where('wallet_id', $data['wallet_id']);
        }

        if (isset($data['order_id'])) {
            $query->where('order_id', $data['order_id']);
        }

        if (isset($data['type'])) {
            $query->where('type', $data['type']);
        }

        if (isset($data['flow'])) {
            $query->where('flow', $data['flow']);
        }

        if (isset($data['start_date']) || isset($data['end_date'])) {
            $start = isset($data['start_date']) ? Carbon::parse($data['start_date'])->startOfDay() : Carbon::minValue();
            $end = isset($data['end_date']) ? Carbon::parse($data['end_date'])->endOfDay() : Carbon::maxValue();
            $query->whereBetween('created_at', [$start, $end]);
        }

        $perPage = (int) ($data['per_page'] ?? 25);
        $paginator = $query->paginate($perPage);

        return response()->json([
            'data' => $paginator->items(),
            'meta' => [
                'current_page' => $paginator->currentPage(),
                'per_page' => $paginator->perPage(),
                'total' => $paginator->total(),
                'last_page' => $paginator->lastPage(),
            ],
        ]);
    }
}
