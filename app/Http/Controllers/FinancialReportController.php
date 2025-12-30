<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\FinancialReportChartRequest;
use App\Http\Requests\FinancialReportIndexRequest;
use App\Models\Transaction;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

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
     *         @OA\Schema(type="string", format="date", example="2025-12-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", format="date", example="2026-01-01")
     *     ),
     *     @OA\Response(response=200, description="OK"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function index(FinancialReportIndexRequest $request): JsonResponse
    {
        $data = $request->validated();

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

    /**
     * @OA\Get(
     *     path="/api/financial-reports/chart",
     *     tags={"Reports"},
     *     summary="Time-series chart data for income/expense in a date range",
     *     @OA\Parameter(
     *         name="start_date",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", format="date", example="2025-12-01")
     *     ),
     *     @OA\Parameter(
     *         name="end_date",
     *         in="query",
     *         required=true,
     *         @OA\Schema(type="string", format="date", example="2026-01-01")
     *     ),
     *     @OA\Parameter(
     *         name="granularity",
     *         in="query",
     *         required=false,
     *         @OA\Schema(type="string", enum={"day","month"}, example="day")
     *     ),
     *     @OA\Response(response=200, description="OK"),
     *     @OA\Response(response=422, description="Validation error")
     * )
     */
    public function chart(FinancialReportChartRequest $request): JsonResponse
    {
        $data = $request->validated();

        $start = Carbon::parse($data['start_date'])->startOfDay();
        $end = Carbon::parse($data['end_date'])->endOfDay();
        $granularity = (string) ($data['granularity'] ?? 'day');

        $driver = DB::connection()->getDriverName();

        $periodExpression = match ($granularity) {
            'month' => match ($driver) {
                'sqlite' => "strftime('%Y-%m-01', created_at)",
                'pgsql' => "to_char(date_trunc('month', created_at), 'YYYY-MM-01')",
                default => "DATE_FORMAT(created_at, '%Y-%m-01')",
            },
            default => match ($driver) {
                'pgsql' => 'CAST(created_at AS DATE)',
                default => 'DATE(created_at)',
            },
        };

        $rows = Transaction::query()
            ->whereBetween('created_at', [$start, $end])
            ->selectRaw("{$periodExpression} as period")
            ->selectRaw('COALESCE(SUM(amount), 0) as volume')
            ->selectRaw("COALESCE(SUM(CASE WHEN flow = 'in' THEN amount ELSE 0 END), 0) as inflow")
            ->selectRaw("COALESCE(SUM(CASE WHEN flow = 'out' THEN amount ELSE 0 END), 0) as outflow")
            ->selectRaw("COALESCE(SUM(CASE WHEN flow = 'in' THEN amount ELSE -amount END), 0) as net")
            ->groupBy('period')
            ->orderBy('period')
            ->get();

        $series = [];
        foreach ($rows as $row) {
            $series[] = [
                'period' => (string) $row->period,
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
                    'granularity' => $granularity,
                ],
                'series' => $series,
            ],
        ]);
    }
}
