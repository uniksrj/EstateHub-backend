<?php

namespace App\Services;

use App\Models\Property;
use App\Models\PropertyInvestmentMetric;

class InvestmentAnalysisService
{
    /** Default assumptions (overridable per-call). */
    public const DEFAULT_ASSUMPTIONS = [
        'down_payment_pct' => 20.0,
        'interest_rate' => 8.5,
        'loan_tenure_years' => 20,
        'appreciation_rate' => 6.0,
        'rent_growth_rate' => 5.0,
        'vacancy_pct' => 5.0,
        'closing_costs_pct' => 2.0,
        'projection_years' => 10,
    ];

    /**
     * Compute full investment analysis for a property.
     */
    public function analyze(Property $property, array $overrides = []): array
    {
        $a = array_merge(self::DEFAULT_ASSUMPTIONS, $overrides);

        $purchasePrice     = (float) $property->price;
        $monthlyRent       = (float) ($property->expected_monthly_rent ?? 0);
        $occupancy         = (float) ($property->current_occupancy_pct ?? (100 - $a['vacancy_pct'])) / 100;
        $annualGrossRent   = $monthlyRent * 12 * $occupancy;

        $annualOpex = (float) ($property->annual_property_tax ?? 0)
            + (float) ($property->hoa_monthly ?? 0) * 12
            + (float) ($property->maintenance_annual ?? 0)
            + (float) ($property->insurance_annual ?? 0);

        $noi = max(0, $annualGrossRent - $annualOpex);

        // Gross & Net yield
        $grossYield = $purchasePrice > 0 ? ($monthlyRent * 12 / $purchasePrice) * 100 : 0;
        $closingCosts = $purchasePrice * ($a['closing_costs_pct'] / 100);
        $totalAcquisition = $purchasePrice + $closingCosts;
        $netYield = $totalAcquisition > 0 ? ($noi / $totalAcquisition) * 100 : 0;

        // Cap Rate
        $capRate = $purchasePrice > 0 ? ($noi / $purchasePrice) * 100 : 0;

        // Financing
        $downPayment = $purchasePrice * ($a['down_payment_pct'] / 100);
        $loanAmount  = $purchasePrice - $downPayment;
        $monthlyRate = ($a['interest_rate'] / 100) / 12;
        $nPayments   = $a['loan_tenure_years'] * 12;

        $monthlyPI = $loanAmount > 0 && $monthlyRate > 0
            ? $loanAmount * $monthlyRate / (1 - pow(1 + $monthlyRate, -$nPayments))
            : ($nPayments > 0 ? $loanAmount / $nPayments : 0);

        $annualDebtService = $monthlyPI * 12;

        // DSCR
        $dscr = $annualDebtService > 0 ? $noi / $annualDebtService : null;

        // Cash-on-Cash
        $cashInvested = $downPayment + $closingCosts;
        $annualPreTaxCashFlow = $noi - $annualDebtService;
        $cashOnCash = $cashInvested > 0 ? ($annualPreTaxCashFlow / $cashInvested) * 100 : 0;

        // Break-even occupancy
        $grossPotentialRent = $monthlyRent * 12;
        $breakEvenOccupancy = $grossPotentialRent > 0
            ? (($annualOpex + $annualDebtService) / $grossPotentialRent) * 100
            : 0;

        // Monthly breakdown
        $monthlyCashFlow = [
            'rent' => round($monthlyRent * $occupancy, 2),
            'opex' => round($annualOpex / 12, 2),
            'noi' => round($noi / 12, 2),
            'debt_service' => round($monthlyPI, 2),
            'net' => round($monthlyPI > 0 ? ($noi / 12 - $monthlyPI) : $noi / 12, 2),
        ];

        // 10-yr projection + IRR
        $projection = $this->buildProjection(
            $purchasePrice, $monthlyRent, $occupancy, $annualOpex,
            $annualDebtService, $monthlyPI, $loanAmount, $monthlyRate,
            $a['appreciation_rate'], $a['rent_growth_rate'], $a['projection_years']
        );

        $irr = $this->calculateIRR(
            array_merge([-$cashInvested], array_column($projection, 'net_cash_flow'))
        );

        // Sensitivity (±10% rent, ±5% vacancy, +100bps rate)
        $sensitivity = $this->sensitivity(
            $purchasePrice, $monthlyRent, $occupancy, $annualOpex, $monthlyPI, $a
        );

        // Investment score
        $score = $this->scoreInvestment($capRate, $cashOnCash, $dscr, $property);

        $result = [
            'property_id' => $property->id,
            'assumptions' => $a,
            'metrics' => [
                'gross_yield' => round($grossYield, 2),
                'net_yield' => round($netYield, 2),
                'cap_rate' => round($capRate, 2),
                'noi_annual' => round($noi, 2),
                'dscr' => $dscr !== null ? round($dscr, 2) : null,
                'cash_on_cash' => round($cashOnCash, 2),
                'occupancy_rate' => round($occupancy * 100, 2),
                'break_even_occupancy' => round($breakEvenOccupancy, 2),
                'irr_10yr' => $irr !== null ? round($irr * 100, 2) : null,
                'investment_score' => $score,
            ],
            'cash_flow_monthly' => $monthlyCashFlow,
            'financing' => [
                'purchase_price' => round($purchasePrice, 2),
                'down_payment' => round($downPayment, 2),
                'loan_amount' => round($loanAmount, 2),
                'monthly_pi' => round($monthlyPI, 2),
                'annual_debt_service' => round($annualDebtService, 2),
            ],
            'projection_10yr' => $projection,
            'amortization' => $this->amortization($loanAmount, $monthlyRate, $nPayments),
            'sensitivity' => $sensitivity,
        ];

        return $result;
    }

    /* --------------------------------------------------------------------- */
    /*  Helpers                                                              */
    /* --------------------------------------------------------------------- */

    private function buildProjection(
        float $price, float $rent, float $occ, float $opex,
        float $annualDebt, float $monthlyPI, float $loan, float $rate,
        float $appreciation, float $rentGrowth, int $years
    ): array {
        $rows = [];
        $value = $price;
        $currentRent = $rent;
        $balance = $loan;

        for ($y = 1; $y <= $years; $y++) {
            $value *= (1 + $appreciation / 100);
            $currentRent *= (1 + $rentGrowth / 100);
            $annualRent = $currentRent * 12 * $occ;
            $annualOpex = $opex * pow(1 + 0.04, $y); // 4% opex inflation
            $noi = max(0, $annualRent - $annualOpex);
            $netCF = $noi - $annualDebt;

            // Amortize the loan for this year
            for ($m = 0; $m < 12; $m++) {
                $interest = $balance * $rate;
                $principal = $monthlyPI - $interest;
                $balance = max(0, $balance - $principal);
            }

            $equity = $value - $balance;

            $rows[] = [
                'year' => $y,
                'rent' => round($annualRent, 2),
                'opex' => round($annualOpex, 2),
                'noi' => round($noi, 2),
                'debt_service' => round($annualDebt, 2),
                'net_cash_flow' => round($netCF, 2),
                'cumulative_cash_flow' => round(($rows[count($rows) - 1]['cumulative_cash_flow'] ?? 0) + $netCF, 2),
                'property_value' => round($value, 2),
                'loan_balance' => round($balance, 2),
                'equity' => round($equity, 2),
            ];
        }

        return $rows;
    }

    private function amortization(float $loan, float $rate, int $months): array
    {
        if ($loan <= 0 || $months <= 0) return [];

        $monthlyPI = $loan * $rate / (1 - pow(1 + $rate, -$months));
        $balance = $loan;
        $rows = [];

        for ($m = 1; $m <= $months; $m++) {
            $interest = $balance * $rate;
            $principal = $monthlyPI - $interest;
            $balance = max(0, $balance - $principal);
            $rows[] = [
                'month' => $m,
                'payment' => round($monthlyPI, 2),
                'interest' => round($interest, 2),
                'principal' => round($principal, 2),
                'balance' => round($balance, 2),
            ];
        }

        return $rows;
    }

    private function calculateIRR(array $cashFlows): ?float
    {
        if (count($cashFlows) < 2) return null;

        $rate = 0.10;
        for ($i = 0; $i < 100; $i++) {
            $npv = 0;
            $deriv = 0;
            foreach ($cashFlows as $t => $cf) {
                $npv   += $cf / pow(1 + $rate, $t);
                $deriv -= $t * $cf / pow(1 + $rate, $t + 1);
            }
            if (abs($npv) < 0.01) return $rate;
            if ($deriv == 0) return null;
            $newRate = $rate - $npv / $deriv;
            if (abs($newRate - $rate) < 1e-6) return $newRate;
            $rate = $newRate;
            if ($rate < -0.99 || $rate > 10) return null;
        }
        return null;
    }

    private function sensitivity(
        float $price, float $rent, float $occ, float $opex,
        float $monthlyPI, array $a
    ): array {
        $baseline = $this->capRateFor($price, $rent, $occ, $opex);

        return [
            'rent_minus_10' => round($this->capRateFor($price, $rent * 0.9, $occ, $opex), 2),
            'rent_plus_10' => round($this->capRateFor($price, $rent * 1.1, $occ, $opex), 2),
            'vacancy_plus_5' => round($this->capRateFor($price, $rent, $occ - 0.05, $opex), 2),
            'vacancy_minus_5' => round($this->capRateFor($price, $rent, $occ + 0.05, $opex), 2),
            'opex_plus_20' => round($this->capRateFor($price, $rent, $occ, $opex * 1.2), 2),
            'baseline' => round($baseline, 2),
        ];
    }

    private function capRateFor(float $price, float $rent, float $occ, float $opex): float
    {
        $noi = max(0, $rent * 12 * $occ - $opex);
        return $price > 0 ? ($noi / $price) * 100 : 0;
    }

    private function scoreInvestment(float $capRate, float $coc, ?float $dscr, Property $p): int
    {
        $capScore  = min(100, max(0, ($capRate / 8) * 100));
        $cocScore  = min(100, max(0, ($coc / 10) * 100));
        $dscrScore = $dscr !== null ? min(100, max(0, ($dscr / 1.5) * 100)) : 50;
        $appreci   = (float) ($p->appreciation_rate_pct ?? 6);
        $appScore  = min(100, max(0, ($appreci / 10) * 100));

        return (int) round(
            $capScore  * 0.35 +
            $cocScore  * 0.25 +
            $appScore  * 0.20 +
            $dscrScore * 0.20
        );
    }

    /** Persist computed metrics as a cache row. */
    public function refresh(Property $property): PropertyInvestmentMetric
    {
        $analysis = $this->analyze($property);

        return PropertyInvestmentMetric::updateOrCreate(
            ['property_id' => $property->id],
            [
                'gross_yield' => $analysis['metrics']['gross_yield'],
                'net_yield' => $analysis['metrics']['net_yield'],
                'cap_rate' => $analysis['metrics']['cap_rate'],
                'noi_annual' => $analysis['metrics']['noi_annual'],
                'dscr' => $analysis['metrics']['dscr'],
                'cash_on_cash' => $analysis['metrics']['cash_on_cash'],
                'occupancy_rate' => $analysis['metrics']['occupancy_rate'],
                'investment_score' => $analysis['metrics']['investment_score'],
                'breakdown' => $analysis,
                'calculated_at' => now(),
            ]
        );
    }
}