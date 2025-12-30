<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class FinancialReportController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/financial-reports",
     *     tags={"Reports"},
     *     summary="Aggregate financial ledger report for a date range",
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", format="date", example="2025-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", format="date", example="2025-01-31")
     *     ),
     *     @OA\Response(response=200, description="OK"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
        ]);

        $start = Carbon::parse($data['start_date'])->startOfDay();
        $end = Carbon::parse($data['end_date'])->endOfDay();

        $totals = Transaction::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('COALESCE(SUM(amount), 0) as total_volume')
            ->selectRaw("COALESCE(SUM(CASE WHEN flow = 'in' THEN amount ELSE 0 END), 0) as total_inflow")
            ->selectRaw("COALESCE(SUM(CASE WHEN flow = 'out' THEN amount ELSE 0 END), 0) as total_outflow")
            ->first();

        $byTypeRows = Transaction::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw('type')
            ->selectRaw('COALESCE(SUM(amount), 0) as volume')
            ->selectRaw("COALESCE(SUM(CASE WHEN flow = 'in' THEN amount ELSE 0 END), 0) as inflow")
            ->selectRaw("COALESCE(SUM(CASE WHEN flow = 'out' THEN amount ELSE 0 END), 0) as outflow")
            ->selectRaw("COALESCE(SUM(CASE WHEN flow = 'in' THEN amount ELSE -amount END), 0) as net")
            ->groupBy('type')
            ->orderBy('type')
            ->get();

        $byType = [];
        foreach ($byTypeRows as $row) {
            $byType[$row->type] = [
                'volume' => (string) $row->volume,
                'inflow' => (string) $row->inflow,
                'outflow' => (string) $row->outflow,
                'net' => (string) $row->net,
            ];
        }

        return response()->json([
            'data' => [
                'period' => [
                    'start_date' => $start->toDateTimeString(),
                    'end_date' => $end->toDateTimeString(),
                ],
                'totals' => [
                    'volume' => (string) ($totals->total_volume ?? '0'),
                    'inflow' => (string) ($totals->total_inflow ?? '0'),
                    'outflow' => (string) ($totals->total_outflow ?? '0'),
                ],
                'by_type' => $byType,
            ],
        ]);
    }
}
