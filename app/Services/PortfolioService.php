<?php

namespace App\Services;

use App\Models\User;

class PortfolioService
{
    public function aggregate(User $user): array
    {
        $items = $user->portfolioProperties()->with('property')->get();

        $totalInvested = 0;
        $totalCurrentValue = 0;
        $totalMonthlyRent = 0;
        $totalMonthlyOpex = 0;
        $byCity = [];
        $byType = [];
        $assets = [];

        foreach ($items as $item) {
            $invested = (float) $item->purchase_price + (float) $item->acquisition_costs;
            $currentValue = (float) ($item->current_value ?? $item->property->price ?? $item->purchase_price);
            $totalInvested += $invested;
            $totalCurrentValue += $currentValue;
            $totalMonthlyRent += (float) $item->monthly_rent;
            $totalMonthlyOpex += (float) $item->monthly_opex;

            $city = $item->property->city ?? 'Unknown';
            $type = $item->property->property_type ?? 'Unknown';
            $byCity[$city] = ($byCity[$city] ?? 0) + $currentValue;
            $byType[$type] = ($byType[$type] ?? 0) + $currentValue;

            $noi = ((float) $item->monthly_rent - (float) $item->monthly_opex) * 12;
            $assets[] = [
                'id' => $item->id,
                'property_id' => $item->property_id,
                'title' => $item->property->title ?? '',
                'city' => $city,
                'status' => $item->status,
                'acquired_at' => optional($item->acquired_at)->toDateString(),
                'purchase_price' => $invested,
                'current_value' => $currentValue,
                'noi_annual' => round($noi, 2),
                'cap_rate' => $currentValue > 0 ? round(($noi / $currentValue) * 100, 2) : 0,
                'gain_loss' => round($currentValue - $invested, 2),
                'gain_loss_pct' => $invested > 0
                    ? round((($currentValue - $invested) / $invested) * 100, 2) : 0,
            ];
        }

        $annualCashFlow = ($totalMonthlyRent - $totalMonthlyOpex) * 12;

        return [
            'summary' => [
                'total_invested' => round($totalInvested, 2),
                'portfolio_value' => round($totalCurrentValue, 2),
                'total_gain_loss' => round($totalCurrentValue - $totalInvested, 2),
                'total_gain_loss_pct' => $totalInvested > 0
                    ? round((($totalCurrentValue - $totalInvested) / $totalInvested) * 100, 2) : 0,
                'annual_cash_flow' => round($annualCashFlow, 2),
                'avg_yield' => $totalCurrentValue > 0
                    ? round(($annualCashFlow / $totalCurrentValue) * 100, 2) : 0,
                'assets_count' => $items->count(),
            ],
            'allocation_by_city' => $byCity,
            'allocation_by_type' => $byType,
            'assets' => $assets,
        ];
    }
}