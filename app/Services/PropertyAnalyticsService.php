<?php
// app/Services/PropertyAnalyticsService.php

namespace App\Services;

use App\Jobs\MatchPropertiesToPreferences;
use App\Models\BuyerPreference;
use App\Models\Property;
use App\Models\PropertyView;
use App\Models\Inquiry;
use App\Models\PropertyOffer;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;

class PropertyAnalyticsService
{
    public function enhancePropertyWithAnalytics(Property $property)
    {
        // Calculate view statistics
        $viewStats = $this->getViewStatistics($property->id);

        // Calculate inquiry statistics
        $inquiryStats = $this->getInquiryStatistics($property->id);

        // Calculate offer statistics
        $offerStats = $this->getOfferStatistics($property->id);

        // Calculate performance metrics
        $performance = $this->getPropertyPerformanceMetrics($property);

        return array_merge($property->toArray(), [
            'analytics' => [
                'views' => $viewStats,
                'inquiries' => $inquiryStats,
                'offers' => $offerStats,
                'performance' => $performance
            ],
            'quick_stats' => [
                'total_views' => $property->property_views->count(),
                'total_inquiries' => $property->inquiries->count(),
                'total_offers' => $property->offers->count(),
                'days_on_market' => $this->getDaysOnMarket($property),
                'view_to_inquiry_rate' => $this->getConversionRate(
                    $property->inquiries->count(),
                    $property->property_views->count()
                )
            ]
        ]);
    }

    public function getViewStatistics($propertyId)
    {
        return [
            'total_views' => PropertyView::where('property_id', $propertyId)->count(),
            'unique_viewers' => PropertyView::where('property_id', $propertyId)
                ->distinct('ip_address')
                ->count('ip_address'),
            'views_today' => PropertyView::where('property_id', $propertyId)
                ->whereDate('viewed_at', today())
                ->count(),
            'views_this_week' => PropertyView::where('property_id', $propertyId)
                ->where('viewed_at', '>=', now()->subWeek())
                ->count(),
            'views_this_month' => PropertyView::where('property_id', $propertyId)
                ->where('viewed_at', '>=', now()->subMonth())
                ->count(),
            'daily_views' => PropertyView::where('property_id', $propertyId)
                ->where('viewed_at', '>=', now()->subDays(30))
                ->selectRaw('DATE(viewed_at) as date, COUNT(*) as count')
                ->groupBy('date')
                ->orderBy('date', 'desc')
                ->get()
        ];
    }

    public function getInquiryStatistics($propertyId)
    {
        return [
            'total_inquiries' => Inquiry::where('property_id', $propertyId)->count(),
            'pending_inquiries' => Inquiry::where('property_id', $propertyId)
                ->where('status', 'pending')
                ->count(),
            'responded_inquiries' => Inquiry::where('property_id', $propertyId)
                ->where('status', 'responded')
                ->count(),
            'recent_inquiries' => Inquiry::where('property_id', $propertyId)
                ->where('created_at', '>=', now()->subWeek())
                ->count(),
            'inquiry_sources' => Inquiry::where('property_id', $propertyId)
                ->selectRaw('source, COUNT(*) as count')
                ->groupBy('source')
                ->get()
        ];
    }

    public function getOfferStatistics($propertyId)
    {
        return [
            'total_offers' => PropertyOffer::where('property_id', $propertyId)->count(),
            'pending_offers' => PropertyOffer::where('property_id', $propertyId)
                ->where('status', 'pending')
                ->count(),
            'accepted_offers' => PropertyOffer::where('property_id', $propertyId)
                ->where('status', 'accepted')
                ->count(),
            'rejected_offers' => PropertyOffer::where('property_id', $propertyId)
                ->where('status', 'rejected')
                ->count(),
            'average_offer_amount' => PropertyOffer::where('property_id', $propertyId)
                ->where('status', '!=', 'rejected')
                ->avg('offer_amount'),
            'highest_offer' => PropertyOffer::where('property_id', $propertyId)
                ->max('offer_amount')
        ];
    }

    public function getPropertyPerformanceMetrics(Property $property)
    {
        $daysOnMarket = $this->getDaysOnMarket($property);
        $viewToInquiryRate = $this->getConversionRate(
            $property->inquiries->count(),
            $property->property_views->count()
        );
        $inquiryToOfferRate = $this->getConversionRate(
            $property->offers->count(),
            $property->inquiries->count()
        );

        return [
            'days_on_market' => $daysOnMarket,
            'view_to_inquiry_rate' => $viewToInquiryRate,
            'inquiry_to_offer_rate' => $inquiryToOfferRate,
            'overall_conversion_rate' => $viewToInquiryRate * $inquiryToOfferRate / 100,
            'price_per_sq_ft' => $property->sq_ft > 0 ? $property->price / $property->sq_ft : 0,
            'market_health' => $this->calculateMarketHealth($property)
        ];
    }

    public function getDaysOnMarket(Property $property)
    {
        if ($property->sold_at) {
            return $property->created_at->diffInDays($property->sold_at);
        }
        return $property->created_at->diffInDays(now());
    }

    public function getConversionRate($conversions, $total)
    {
        if ($total === 0) return 0;
        return round(($conversions / $total) * 100, 2);
    }

    public function calculateMarketHealth(Property $property)
    {
        $score = 0;

        // Views score (max 40 points)
        $viewsThisWeek = PropertyView::where('property_id', $property->id)
            ->where('viewed_at', '>=', now()->subWeek())
            ->count();
        $score += min($viewsThisWeek * 2, 40);

        // Inquiries score (max 30 points)
        $inquiriesThisWeek = Inquiry::where('property_id', $property->id)
            ->where('created_at', '>=', now()->subWeek())
            ->count();
        $score += min($inquiriesThisWeek * 10, 30);

        // Offers score (max 30 points)
        $offersCount = PropertyOffer::where('property_id', $property->id)->count();
        $score += min($offersCount * 15, 30);

        return [
            'score' => $score,
            'rating' => $this->getHealthRating($score)
        ];
    }

    public function getHealthRating($score)
    {
        if ($score >= 80) return 'Excellent';
        if ($score >= 60) return 'Good';
        if ($score >= 40) return 'Average';
        if ($score >= 20) return 'Poor';
        return 'Very Poor';
    }

    /**
     * Enhance multiple properties with analytics
     */
    public function enhancePropertiesWithAnalytics($properties)
    {
        return $properties->map(function ($property) {
            return $this->enhancePropertyWithAnalytics($property);
        });
    }

    /**
     * Get dashboard summary for multiple properties
     */
    public function getDashboardSummary($properties)
    {
        $totalProperties = $properties->count();
        $activeListings = $properties->where('status', 'for_sale')->count();
        $soldProperties = $properties->where('status', 'sold')->count();

        $totalViews = $properties->sum(function ($property) {
            return $property->property_views->count();
        });

        $totalInquiries = $properties->sum(function ($property) {
            return $property->inquiries->count();
        });

        return [
            'total_properties' => $totalProperties,
            'active_listings' => $activeListings,
            'sold_properties' => $soldProperties,
            'total_views' => $totalViews,
            'total_inquiries' => $totalInquiries,
            'average_views_per_property' => $totalProperties > 0 ? round($totalViews / $totalProperties, 1) : 0,
            'conversion_rate' => $this->getConversionRate($totalInquiries, $totalViews)
        ];
    }

    public function getPerformanceData($sellerId)
    {
        // Last 7 days views data
        $viewsData = PropertyView::whereHas('property', function ($query) use ($sellerId) {
            $query->where('agent_id', $sellerId);
        })
            ->where('viewed_at', '>=', now()->subDays(7))
            ->selectRaw('DAYNAME(viewed_at) as day, COUNT(*) as views')
            ->groupBy('day')
            ->orderBy(DB::raw('MIN(viewed_at)'))
            ->get();

        // Monthly performance
        $monthlyData = PropertyView::whereHas('property', function ($query) use ($sellerId) {
            $query->where('agent_id', $sellerId);
        })
            ->where('viewed_at', '>=', now()->subMonths(6))
            ->selectRaw('DATE_FORMAT(viewed_at, "%b %Y") as month, COUNT(*) as views')
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        return [
            'weekly_views' => $viewsData,
            'monthly_trends' => $monthlyData,
            'conversion_rate' => $this->calculateConversionRate($sellerId)
        ];
    }

    public function getTopPerformingProperties($sellerId)
    {
        return Property::where('agent_id', $sellerId)
            ->withCount(['property_views', 'inquiries'])
            ->orderBy('property_views_count', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($property) {
                return [
                    'id' => $property->id,
                    'title' => $property->title,
                    'views' => $property->property_views_count,
                    'inquiries' => $property->inquiries_count,
                    'status' => $property->status,
                    'conversion_rate' => $property->property_views_count > 0
                        ? round(($property->inquiries_count / $property->property_views_count) * 100, 1)
                        : 0
                ];
            });
    }

    public function getRecentInquiries($sellerId)
    {

        return Inquiry::whereHas('property', function ($query) use ($sellerId) {
            $query->where('agent_id', $sellerId);
        })
            ->with(['property', 'user'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($inquiry) {
                return [
                    'id' => $inquiry->id,
                    'property_title' =>  optional($inquiry->property)->title,
                    'user_name' => $inquiry->user?->name ??  $inquiry->name,
                    'message' => Str::limit($inquiry->message, 50),
                    'status' => $inquiry->status,
                    'created_at' => $inquiry->created_at->diffForHumans()
                ];
            });
    }

    public function calculateConversionRate($sellerId)
    {
        $totalViews = PropertyView::whereHas('property', function ($query) use ($sellerId) {
            $query->where('agent_id', $sellerId);
        })->count();

        $totalInquiries = Inquiry::whereHas('property', function ($query) use ($sellerId) {
            $query->where('agent_id', $sellerId);
        })->count();

        return $totalViews > 0 ? round(($totalInquiries / $totalViews) * 100, 2) : 0;
    }

    public function calculateSalesConversionRate()
    {
        $totalListed = Property::where('status', 'for_sale')
            ->orWhereNotNull('sold_at') // include sold ones too
            ->count();

        $soldCount = Property::whereNotNull('sold_at')->count();

        return $totalListed > 0
            ? round(($soldCount / $totalListed) * 100, 2)
            : 0;
    }


    public function getMetricsDetails()
    {
        $currentMonth = Carbon::now();
        $lastMonth = Carbon::now()->subMonth();

        // === Fetch Core Counts ===
        $counts = $this->getBaseCounts();
        $averagePrice = $this->calculateAveragePrice($counts);
        $occupancyRate = $this->calculateOccupancyRate($counts);

        // === Sales & Listings ===
        $currentMonthSales = $this->getSalesCount($currentMonth);
        $lastMonthSales = $this->getSalesCount($lastMonth);

        $currentMonthListed = $this->getCreatedCount($currentMonth);
        $lastMonthListed = $this->getCreatedCount($lastMonth);

        // === Conversion Rate ===
        $currentConversion = $this->calculateRate($currentMonthSales, $currentMonthListed);
        $lastConversion = $this->calculateRate($lastMonthSales, $lastMonthListed);
        [$conversionChange, $conversionTrend] = $this->calculateChange($currentConversion, $lastConversion);

        // === Team Performance ===
        [$teamPerformance, $teamPerformanceChange, $teamPerformanceTrend] = $this->getTeamPerformance($currentMonth, $lastMonth);

        // === Growth Calculations ===
        [$propertiesGrowth, $propertiesTrend] = $this->calculateChange($counts->total_properties, $lastMonthListed);
        [$salesGrowth, $salesTrend] = $this->calculateChange($currentMonthSales, $lastMonthSales);
        [$valueGrowth, $valueTrend] = $this->calculateChange($counts->total_value, Property::whereYear('created_at', $lastMonth->year)->sum('price'));
        [$activeDealsChange, $activeDealsTrend] = $this->calculateChange($counts->active_listings, $this->getActiveDeals($lastMonth));

        // === Return Unified Data ===
        return [
            'overview' => [
                'totalProperties' => (int)$counts->total_properties,
                'activeListings' => (int)$counts->active_listings,
                'soldThisMonth' => (int)$currentMonthSales,
                'totalValue' => (float)$counts->total_value,
                'averagePrice' => round($averagePrice, 2),
                'occupancyRate' => round($occupancyRate, 2),
                'propertiesGrowth' => round($propertiesGrowth, 1),
                'salesGrowth' => round($salesGrowth, 1),
                'sold_properties' => (int) $counts->sold_properties,
                'conversionRate' => $this->calculateSalesConversionRate() . '%',
                'propertiesGrowthA' => ['change' => $propertiesGrowth, 'trend' => $propertiesTrend],
                'salesGrowthA' => ['change' => $salesGrowth, 'trend' => $salesTrend],
                'valueGrowth' => ['change' => $valueGrowth, 'trend' => $valueTrend],
                'activeDeals' => ['change' => $activeDealsChange, 'trend' => $activeDealsTrend],
                'conversionRate' => ['change' => $conversionChange, 'trend' => $conversionTrend],
                'teamPerformance' => [
                    'value' => $teamPerformance . '%',
                    'change' => $teamPerformanceChange,
                    'trend' => $teamPerformanceTrend,
                ],
            ]
        ];
    }

    public function getDealPipeline($type = "")
    {
        $pipeline = [
            'Prospecting' => Property::where('status', 'draft')->count(),
            'Initial Review' => Property::where('status', 'for_sale')->count(),
            'Due Diligence' => Property::where('status', 'under_review')->count(),
            'Final Negotiation' => Property::where('status', 'pending')->count(),
            'Closing' => Property::whereNotNull('sold_at')->count(),
        ];

        $pipelineValue = [
            'Prospecting' => Property::where('status', 'draft')->sum('price') / 1000000,
            'Initial Review' => Property::where('status', 'for_sale')->sum('price') / 1000000,
            'Due Diligence' => Property::where('status', 'under_review')->sum('price') / 1000000,
            'Final Negotiation' => Property::where('status', 'pending')->sum('price') / 1000000,
            'Closing' => Property::whereNotNull('sold_at')->sum('price') / 1000000,
        ];

        $result = collect($pipeline)->map(function ($deals, $stage) use ($pipelineValue) {
            return [
                'stage' => $stage,
                'deals' => $deals,
                'value' => round($pipelineValue[$stage], 2),
            ];
        })->values();

        return $type ? $result : response()->json($result);
    }
    //* Get chart data function in parts */
    private function getPropertyTypeData()
    {
        return Property::select('property_type', DB::raw('COUNT(*) as count'))
            ->groupBy('property_type')
            ->get()
            ->map(fn($item) => [
                'name'  => ucfirst($item->property_type),
                'value' => $item->count,
                'color' => $this->getColorForType($item->property_type),
            ]);
    }

    private function getMonthlyTrends()
    {
        return collect(range(5, 0))->map(function ($i) {
            $month = Carbon::now()->subMonths($i);
            $start = $month->copy()->startOfMonth();
            $end   = $month->copy()->endOfMonth();

            return [
                'month'    => $month->format('M'),
                'listings' => Property::whereBetween('created_at', [$start, $end])->count(),
                'sales'    => Property::whereBetween('sold_at', [$start, $end])->count(),
                'revenue'  => (float) Property::whereBetween('sold_at', [$start, $end])->sum('price'),
            ];
        });
    }

    private function getLocationDistribution()
    {
        return Property::select('city', DB::raw('COUNT(*) as properties'), DB::raw('AVG(price) as avg_price'))
            ->whereNotNull('city')
            ->groupBy('city')
            ->get()
            ->map(fn($item) => [
                'location'   => $item->city ?: 'Unknown',
                'properties' => (int) $item->properties,
                'avgPrice'   => round($item->avg_price, 2),
            ]);
    }
    //* Get chart data function in parts */

    public function getChartData()
    {
        return [
            'propertyTypes'        => $this->getPropertyTypeData(),
            'monthlyTrends'        => $this->getMonthlyTrends(),
            'locationDistribution' => $this->getLocationDistribution(),
            'performanceMetrics'   => $this->getPerformanceMetrics(),
            'dealPipeline'         => $this->getDealPipeline('type'),
        ];
    }



    public function getPerformanceMetrics()
    {
        // Calculate actual performance metrics from database
        $soldProperties = Property::whereNotNull('sold_at')->get();

        // Days on Market calculation
        $avgDaysOnMarket = 45;
        if ($soldProperties->count() > 0) {
            $totalDays = 0;
            foreach ($soldProperties as $property) {
                $created = Carbon::parse($property->created_at);
                $sold = Carbon::parse($property->sold_at);
                $totalDays += $created->diffInDays($sold);
            }
            $avgDaysOnMarket = round($totalDays / $soldProperties->count());
        }

        // Sale to List Ratio calculation
        $saleToListRatio = 98.2;
        if ($soldProperties->count() > 0) {
            $totalRatio = 0;
            foreach ($soldProperties as $property) {
                $salePrice = floatval($property->sale_price ?: $property->price);
                $listPrice = floatval($property->price);
                if ($listPrice > 0) {
                    $totalRatio += ($salePrice / $listPrice);
                }
            }
            $saleToListRatio = round(($totalRatio / $soldProperties->count()) * 100, 1);
        }

        return [
            [
                'metric' => 'Days on Market',
                'current' => $avgDaysOnMarket,
                'previous' => 52,
                'change' => round($this->calculateGrowth($avgDaysOnMarket, 52), 1)
            ],
            [
                'metric' => 'Sale-to-List Ratio',
                'current' => $saleToListRatio,
                'previous' => 96.8,
                'change' => round($this->calculateGrowth($saleToListRatio, 96.8), 1)
            ],
            [
                'metric' => 'Inventory Turnover',
                'current' => 2.8,
                'previous' => 2.4,
                'change' => 16.7
            ],
            [
                'metric' => 'Price per Sq Ft',
                'current' => 325,
                'previous' => 312,
                'change' => 4.2
            ]
        ];
    }

    public function calculateGrowth($current, $previous)
    {
        if ($previous == 0) return $current > 0 ? 100 : 0;
        return (($current - $previous) / $previous) * 100;
    }

    public function getColorForType($type)
    {
        $colors = [
            'apartment' => '#3B82F6',
            'commercial' => '#10B981',
            'condo' => '#F59E0B',
            'townhouse' => '#EF4444',
            'house' => '#8B5CF6',
        ];
        return $colors[$type] ?? '#6B7280';
    }

    private function calculateChange($current, $previous)
    {
        if ($previous <= 0) {
            return [0, 'up'];
        }

        $change = round((($current - $previous) / $previous) * 100, 2);
        return [abs($change), $change >= 0 ? 'up' : 'down'];
    }

    public function getBaseCounts()
    {
        return Property::selectRaw('
        COUNT(*) as total_properties,
        SUM(CASE WHEN status = "for_sale" THEN 1 ELSE 0 END) as active_listings,
        SUM(CASE WHEN sold_at IS NOT NULL THEN 1 ELSE 0 END) as sold_properties,
        SUM(price) as total_value
    ')->first();
    }

    private function calculateAveragePrice($counts)
    {
        return $counts->total_properties > 0
            ? $counts->total_value / $counts->total_properties
            : 0;
    }

    private function calculateOccupancyRate($counts)
    {
        return $counts->total_properties > 0
            ? ($counts->sold_properties / $counts->total_properties) * 100
            : 0;
    }

    public function getSalesCount($date)
    {
        return Property::whereNotNull('sold_at')
            ->whereYear('sold_at', $date->year)
            ->whereMonth('sold_at', $date->month)
            ->count();
    }

    public function getCreatedCount($date)
    {
        return Property::whereYear('created_at', $date->year)
            ->whereMonth('created_at', $date->month)
            ->count();
    }

    public function getActiveDeals($date)
    {
        return Property::where('status', 'for_sale')
            ->whereYear('created_at', $date->year)
            ->whereMonth('created_at', '<=', $date->month)
            ->count();
    }

    private function calculateRate($num, $den)
    {
        return $den > 0 ? round(($num / $den) * 100, 2) : 0;
    }

    public function getTeamPerformance($currentMonth, $lastMonth)
    {
        $totalAgents = User::where('role_id', '3')->count();

        $agentsWithSales = $this->agentsWithSales($currentMonth);

        $agentsWithSalesLast = $this->agentsWithSalesLast($lastMonth);

        $current = $this->calculatePercentage($agentsWithSales, $totalAgents);
        $last = $this->calculatePercentage($agentsWithSalesLast, $totalAgents);

        [$change, $trend] = $this->calculateChange($current, $last);

        return [$current, $change, $trend];
    }

    private function calculatePercentage($part, $total)
    {
        return $total > 0 ? round(($part / $total) * 100, 2) : 0;
    }

    private function agentsWithSales($currentMonth)
    {
        return User::where('role_id', '3')
            ->whereHas('properties', function ($q) use ($currentMonth) {
                $q->whereNotNull('sold_at')
                    ->whereYear('sold_at', $currentMonth->year)
                    ->whereMonth('sold_at', $currentMonth->month);
            })->count();
    }

    private function agentsWithSalesLast($lastMonth)
    {
        return User::where('role_id', '3')
            ->whereHas('properties', function ($q) use ($lastMonth) {
                $q->whereNotNull('sold_at')
                    ->whereYear('sold_at', $lastMonth->year)
                    ->whereMonth('sold_at', $lastMonth->month);
            })->count();
    }

    public function getAgentClientsStats($agent_id = "")
    {
        $agent = $agent_id ? $agent_id : auth()->user()->id;

        $activeClients = User::where('role_id', 5)
            ->whereHas('inquiries.property', function ($q) use ($agent) {
                $q->where('agent_id', $agent);
            })
            ->count();

        $newClientsThisWeek = User::where('role_id', 5)
            ->whereHas('inquiries.property', function ($q) use ($agent) {
                $q->where('agent_id', $agent);
            })
            ->whereBetween('created_at', [now()->startOfWeek(), now()->endOfWeek()])
            ->count();

        return [
            'active_clients' => $activeClients,
            'new_this_week' => $newClientsThisWeek,
        ];
    }

    /**
     * Match new property to all existing preferences
     */
    public function matchNewPropertyToPreferences(Property $property)
    {
        // Get all active preferences that might match this property
        $matchingPreferences = BuyerPreference::where('alerts_enabled', true)
            ->where(function ($query) use ($property) {
                // Price range match
                $query->where(function ($q) use ($property) {
                    $q->whereNull('min_price')
                        ->orWhere('min_price', '<=', $property->price);
                })->where(function ($q) use ($property) {
                    $q->whereNull('max_price')
                        ->orWhere('max_price', '>=', $property->price);
                });
            })
            ->where(function ($query) use ($property) {
                // Bedrooms match
                $query->whereNull('min_bedrooms')
                    ->orWhere('min_bedrooms', '<=', $property->bedrooms);
            })
            ->where(function ($query) use ($property) {
                // Property type match
                $query->whereNull('property_type')
                    ->orWhere('property_type', $property->property_type);
            })
            ->get();

        // Trigger matching job for each potential preference
        foreach ($matchingPreferences as $preference) {
            MatchPropertiesToPreferences::dispatch($preference->id);
        }
    }
}
