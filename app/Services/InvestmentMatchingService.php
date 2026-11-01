<?php

namespace App\Services;

use App\Models\InvestmentProfile;
use App\Models\Property;
use App\Notifications\NewInvestmentOpportunity;

class InvestmentMatchingService
{
    public function matchNewProperty(Property $property): int
    {
        if (! $property->is_investment_friendly) return 0;

        $profiles = InvestmentProfile::with('user')->get();
        $matched = 0;

        foreach ($profiles as $profile) {
            if (! $this->meetsCriteria($property, $profile)) continue;

            $metric = $property->investmentMetric;
            if (! $metric) continue;

            if ($profile->target_cap_rate && $metric->cap_rate < $profile->target_cap_rate) {
                continue;
            }

            $profile->user->notify(new NewInvestmentOpportunity($property, $metric));
            $matched++;
        }

        return $matched;
    }

    private function meetsCriteria(Property $property, InvestmentProfile $profile): bool
    {
        $price = (float) $property->price;
        if ($profile->budget_min && $price < (float) $profile->budget_min) return false;
        if ($profile->budget_max && $price > (float) $profile->budget_max) return false;

        $cities = $profile->preferred_cities ?? [];
        if (! empty($cities) && ! in_array($property->city, $cities)) return false;

        $types = $profile->preferred_property_types ?? [];
        if (! empty($types) && ! in_array($property->property_type, $types)) return false;

        return true;
    }
}