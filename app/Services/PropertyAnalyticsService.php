<?php
// app/Services/PropertyAnalyticsService.php

namespace App\Services;

use App\Models\Property;
use App\Models\PropertyView;
use App\Models\Inquiry;
use App\Models\PropertyOffer;
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
        $viewsData = PropertyView::whereHas('property', function($query) use ($sellerId) {
            $query->where('agent_id', $sellerId);
        })
        ->where('viewed_at', '>=', now()->subDays(7))
        ->selectRaw('DAYNAME(viewed_at) as day, COUNT(*) as views')
        ->groupBy('day')
        ->orderBy(DB::raw('MIN(viewed_at)'))
        ->get();

        // Monthly performance
        $monthlyData = PropertyView::whereHas('property', function($query) use ($sellerId) {
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
            ->map(function($property) {
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
        
        return Inquiry::whereHas('property', function($query) use ($sellerId) {
            $query->where('agent_id', $sellerId);
        })
        ->with(['property', 'user'])
        ->orderBy('created_at', 'desc')
        ->limit(5)
        ->get()
        ->map(function($inquiry) {
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
        $totalViews = PropertyView::whereHas('property', function($query) use ($sellerId) {
            $query->where('agent_id', $sellerId);
        })->count();

        $totalInquiries = Inquiry::whereHas('property', function($query) use ($sellerId) {
            $query->where('agent_id', $sellerId);
        })->count();

        return $totalViews > 0 ? round(($totalInquiries / $totalViews) * 100, 2) : 0;
    }
}