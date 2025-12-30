<?php

namespace App\Services;

use InvalidArgumentException;

class FinancialCalculatorService
{
    public function calculateBreakdown(float $totalAmount): array
    {
        $total = $this->normalizeMinorAmount($totalAmount);

        $platformCommission = $this->percentOf($total, $this->getPercent('platform_commission_percent'));
        $postCost = $this->percentOf($total, $this->getPercent('post_cost_percent'));
        $temporaryWallet = $this->percentOf($total, $this->getPercent('temporary_wallet_percent'));
        $insurance = $this->percentOf($total, $this->getPercent('insurance_percent'));

        $allocated = $this->addMany([$platformCommission, $postCost, $temporaryWallet, $insurance]);
        $driverShare = $this->sub($total, $allocated);

        return [
            'platform_commission' => $platformCommission,
            'post_cost' => $postCost,
            'temporary_wallet' => $temporaryWallet,
            'insurance' => $insurance,
            'driver_share' => $driverShare,
        ];
    }

    private function getPercent(string $key): string
    {
        $value = (string) config("financial.breakdown.{$key}");

        if ($value === '' || ! ctype_digit($value)) {
            throw new InvalidArgumentException("Invalid financial breakdown percent configured for '{$key}'.");
        }

        $percent = (int) $value;

        if ($percent < 0 || $percent > 100) {
            throw new InvalidArgumentException("Financial breakdown percent '{$key}' must be between 0 and 100.");
        }

        return $value;
    }

    private function normalizeMinorAmount(float $totalAmount): string
    {
        if (! is_finite($totalAmount)) {
            throw new InvalidArgumentException('Total amount must be a finite number.');
        }

        $normalized = (int) round($totalAmount, 0, PHP_ROUND_HALF_UP);

        if ($normalized < 0) {
            throw new InvalidArgumentException('Total amount must be greater than or equal to zero.');
        }

        return (string) $normalized;
    }

    private function percentOf(string $total, string $percent): string
    {
        if (extension_loaded('bcmath')) {
            return bcdiv(bcmul($total, $percent, 0), '100', 0);
        }

        $totalInt = (int) $total;
        $percentInt = (int) $percent;

        return (string) intdiv($totalInt * $percentInt, 100);
    }

    private function addMany(array $values): string
    {
        if (extension_loaded('bcmath')) {
            $sum = '0';
            foreach ($values as $value) {
                $sum = bcadd($sum, $value, 0);
            }

            return $sum;
        }

        $sum = 0;
        foreach ($values as $value) {
            $sum += (int) $value;
        }

        return (string) $sum;
    }

    private function sub(string $left, string $right): string
    {
        if (extension_loaded('bcmath')) {
            return bcsub($left, $right, 0);
        }

        return (string) ((int) $left - (int) $right);
    }
}
