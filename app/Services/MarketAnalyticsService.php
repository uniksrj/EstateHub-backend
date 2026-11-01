<?php

namespace App\Services;

use App\Models\Property;
use App\Models\MarketSnapshot;
use Illuminate\Support\Facades\DB;

class MarketAnalyticsService
{
    public function forCity(string $city, ?string $locality = null): array
    {
        $query = Property::query()->where('city', $city);
        if ($locality) $query->where('locality', $locality);

        $stats = $query->selectRaw('
            COUNT(*) as active_listings,
            AVG(price) as median_price,
            AVG(area_sqft) as median_area,
            AVG(DATEDIFF(NOW(), created_at)) as avg_days
        ')->first();

        $snapshots = MarketSnapshot::where('city', $city)
            ->when($locality, fn($q) => $q->where('locality', $locality))
            ->orderBy('snapshot_date')
            ->limit(24)
            ->get(['snapshot_date', 'median_price_per_sqft', 'avg_gross_yield']);

        return [
            'city' => $city,
            'locality' => $locality,
            'active_listings' => (int) ($stats->active_listings ?? 0),
            'median_price' => round((float) ($stats->median_price ?? 0), 2),
            'avg_days_on_market' => round((float) ($stats->avg_days ?? 0), 0),
            'history' => $snapshots,
        ];
    }

    public function topCities(int $limit = 8): array
    {
        return Property::query()
            ->select('city', DB::raw('COUNT(*) as listings'), DB::raw('AVG(price) as avg_price'))
            ->whereNotNull('city')
            ->groupBy('city')
            ->orderByDesc('listings')
            ->limit($limit)
            ->get()
            ->toArray();
    }

    public function buildSnapshot(): int
    {
        $rows = Property::query()
            ->select('city', 'locality',
                DB::raw('AVG(price / NULLIF(area_sqft,0)) as median_price_per_sqft'),
                DB::raw('COUNT(*) as active_listings'),
                DB::raw('AVG(DATEDIFF(NOW(), created_at)) as avg_days'))
            ->whereNotNull('city')
            ->groupBy('city', 'locality')
            ->get();

        $today = now()->toDateString();
        $count = 0;

        foreach ($rows as $row) {
            MarketSnapshot::updateOrCreate(
                ['city' => $row->city, 'locality' => $row->locality, 'snapshot_date' => $today],
                [
                    'median_price_per_sqft' => round((float) $row->median_price_per_sqft, 2),
                    'active_listings' => (int) $row->active_listings,
                    'avg_days_on_market' => (int) round((float) $row->avg_days),
                ]
            );
            $count++;
        }

        return $count;
    }
}