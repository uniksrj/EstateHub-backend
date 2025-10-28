<?php

namespace App\Http\Controllers;

use App\Models\Inquiry;
use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\PropertyOffer;
use App\Models\PropertyView;
use App\Services\PropertyAnalyticsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use function Pest\Laravel\get;

class Property_controller extends Controller
{
    protected PropertyAnalyticsService $proprtyService;
    public function __construct(PropertyAnalyticsService $proprtyService) {
        $this->proprtyService = $proprtyService;
    }

    public function index()
    {
        return response()->json(['message' => 'Property controller']);
    }

    public function add_property(Request $request)
    {
        DB::beginTransaction();
        try {
            // Logic to add a property
            $validatedData = $request->validate([
                'title' => 'required|string|max:255',
                'description' => 'required|string',
                'price' => 'required|numeric|min:0',
                'property_type' => 'required|in:house,apartment,condo,townhouse,villa,commercial',
                'address' => 'required|string|max:255',
                'city' => 'required|string|max:100',
                'state' => 'required|string|max:100',
                'zip_code' => 'required|string|max:20',
                'bedrooms' => 'required|integer|min:0',
                'bathrooms' => 'required|numeric|min:0',
                'sq_ft' => 'required|integer|min:0',
                'features' => 'nullable|string',
                'year_built' => 'nullable|integer|min:1800|max:' . date('Y'),
                'garage' => 'nullable|integer|min:0',
                'has_pool' => 'integer|boolean',
                'has_garden' => 'integer|boolean',
                'has_garage' => 'integer|boolean',
                'has_parking' => ' integer|boolean',
                'has_security' => 'integer|boolean',
                'has_air_conditioning' => 'integer|boolean',
                'has_heating' => 'integer|boolean',
                'images.*' => 'nullable|image|max:10240',
            ]);

            $property = Property::create([
                ...$validatedData,
                'agent_id' => auth()->id(),
                'featured' => $request->boolean('featured', false),
                'status' => 'for_sale'
            ]);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $path = $image->store('property_images', 'public');
                    PropertyImage::create([
                        'property_id' => $property->id,
                        'image_path' => $path,
                        'is_primary' => $index === 0,
                    ]);
                }
            }

            DB::commit();

            return response()->json([
                'message' => 'Property created successfully',
                'property' => $property->load('images'),
                'success' => true
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create property',
                'error_type' => class_basename($e),
                'error_message' => $e->getMessage(),
            ], 500);
        }
    }

    public function get_user_properties(Request $request)
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $properties = Property::where('agent_id', $request->user()->id)
            ->with(['agent', 'images'])
            // ->active()
            ->withFilters($request->all())
            ->orderBy('created_at', 'desc')
            ->paginate(6);
        return response()->json($properties);
    }

    public function get_properties(Request $request)
    {
        $properties = Property::with(['agent', 'images'])
            ->active()
            ->withFilters($request->all())
            ->orderBy('created_at', 'desc')
            ->paginate(12);
        return response()->json($properties);
    }

    public function get_property_details($id)
    {
        $property = Property::with(['agent', 'images'])->find($id);
        if (!$property) {
            return response()->json(['message' => 'Property not found'], 404);
        }
        return response()->json($property);
    }

    public function get_all_properties(Request $request)
    {
         $user = auth()->user();
        $properties = Property::with(['agent', 'images','favorites'])
            ->withFilters($request->all(), $user)
            ->orderBy('created_at', 'desc')
            ->paginate(6);
        return response()->json($properties);
    }

    public function update_property(Request $request, $id)
    {
        DB::beginTransaction();
        try {
            $property = Property::find($id);
            if (!$property) {
                return response()->json(['message' => 'Property not found'], 404);
            }
            if ($property->agent_id !== auth()->id()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $validatedData = $request->validate([
                'title' => 'sometimes|string|max:255',
                'description' => 'sometimes|string',
                'price' => 'sometimes|numeric|min:0',
                'property_type' => 'sometimes|in:house,apartment,condo,townhouse,villa,commercial',
                'address' => 'sometimes|string|max:255',
                'city' => 'sometimes|string|max:100',
                'state' => 'sometimes|string|max:100',
                'country' => 'sometimes|string|max:100',
                'zip_code' => 'sometimes|string|max:20',
                'bedrooms' => 'sometimes|integer|min:0',
                'bathrooms' => 'sometimes|numeric|min:0',
                'sq_ft' => 'sometimes|integer|min:0',
                'features' => 'nullable|string',
                'year_built' => 'nullable|integer|min:1800|max:' . date('Y'),
                'garage' => 'nullable|integer|min:0',
                'has_pool' => 'integer|boolean',
                'has_garden' => 'integer|boolean',
                'has_garage' => 'integer|boolean',
                'has_parking' => ' integer|boolean',
                'has_security' => 'integer|boolean',
                'has_air_conditioning' => 'integer|boolean',
                'has_heating' => 'integer|boolean',
                'status' => 'sometimes|in:available,sold,rented,for_sale,for_rent',
                'featured' => 'sometimes|boolean',
                'images.*' => 'nullable|image|max:10240',
            ]);

            $property->update($validatedData);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $path = $image->store('property_images', 'public');
                    PropertyImage::create([
                        'property_id' => $property->id,
                        'image_path' => $path,
                        'is_primary' => $index === 0,
                    ]);
                }
            }

            DB::commit();
            return response()->json([
                'message' => 'Property updated successfully',
                'property' => $property->load('images'),
                'success' => true
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to update property',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function delete_property(Request $request, $id)
    {
         if (!in_array($request->user()->role_id, [1, 2, 6])) {
            return response()->json(['message' => 'Forbidden, You are not Authorized'], 403);
        }

        DB::beginTransaction();
        try {
            $property = Property::find($id);
            if (!$property) {
                return response()->json(['message' => 'Property not found'], 404);
            }
            if ($property->agent_id !== auth()->id()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            // Delete associated images
            foreach ($property->images as $image) {
                $image->delete();
            }

            $property->delete();
            DB::commit();
            return response()->json(['message' => 'Property deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to delete property',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function get_s_admin_property_details(Request $request)
    {
        $properties = Property::with(['agent', 'images'])
            ->withFilters($request->all())
            ->orderBy('created_at', 'desc')
            ->get();

        $metrics = $this->getMetricsDetails();
        $charts = $this->getChartData();

        return response()->json([
            'properties' => $properties,
            'metrics' => $metrics,
            'charts' => $charts
        ]);
    }

    private function getMetricsDetails()
    {
        $counts = Property::selectRaw('
            COUNT(*) as total_properties,
            SUM(CASE WHEN status = "for_sale" THEN 1 ELSE 0 END) as active_listings,
            SUM(CASE WHEN sold_at IS NOT NULL THEN 1 ELSE 0 END) as sold_properties,
            SUM(price) as total_value
        ')->first();

        // Current month sales
        $currentMonth = Carbon::now();
        $currentMonthSales = Property::whereNotNull('sold_at')
            ->whereYear('sold_at', $currentMonth->year)
            ->whereMonth('sold_at', $currentMonth->month)
            ->count();

        // Last month sales for growth calculation
        $lastMonth = Carbon::now()->subMonth();
        $lastMonthSales = Property::whereNotNull('sold_at')
            ->whereYear('sold_at', $lastMonth->year)
            ->whereMonth('sold_at', $lastMonth->month)
            ->count();

        // Properties created last month for growth calculation
        $propertiesLastMonth = Property::whereYear('created_at', $lastMonth->year)
            ->whereMonth('created_at', $lastMonth->month)
            ->count();

        // Calculations
        $averagePrice = $counts->total_properties > 0 ? $counts->total_value / $counts->total_properties : 0;
        $occupancyRate = $counts->total_properties > 0 ? ($counts->sold_properties / $counts->total_properties) * 100 : 0;

        // Growth calculations
        $propertiesGrowth = $this->calculateGrowth($counts->total_properties, $propertiesLastMonth);
        $salesGrowth = $this->calculateGrowth($currentMonthSales, $lastMonthSales);

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
            ]
        ];
    }

    private function getChartData()
    {
        $propertyTypes = Property::select('property_type', DB::raw('COUNT(*) as count'))
            ->groupBy('property_type')
            ->get()
            ->map(function ($item) {
                return [
                    'name' => ucfirst($item->property_type),
                    'value' => $item->count,
                    'color' => $this->getColorForType($item->property_type)
                ];
            });

        // Monthly Trends (Last 6 months)
        $monthlyTrends = [];
        for ($i = 5; $i >= 0; $i--) {
            $month = Carbon::now()->subMonths($i);
            $monthStart = $month->copy()->startOfMonth();
            $monthEnd = $month->copy()->endOfMonth();

            // Listings created in this month
            $listings = Property::whereBetween('created_at', [$monthStart, $monthEnd])->count();

            // Sales in this month
            $sales = Property::whereBetween('sold_at', [$monthStart, $monthEnd])->count();

            // Revenue in this month
            $revenue = Property::whereBetween('sold_at', [$monthStart, $monthEnd])->sum('price');

            $monthlyTrends[] = [
                'month' => $month->format('M'),
                'listings' => $listings,
                'sales' => $sales,
                'revenue' => (float)$revenue
            ];
        }

        // Location Distribution
        $locationDistribution = Property::select('city', DB::raw('COUNT(*) as properties'), DB::raw('AVG(price) as avg_price'))
            ->whereNotNull('city')
            ->groupBy('city')
            ->get()
            ->map(function ($item) {
                return [
                    'location' => $item->city ?: 'Unknown',
                    'properties' => (int)$item->properties,
                    'avgPrice' => round($item->avg_price, 2)
                ];
            });

        // Performance Metrics
        $performanceMetrics = $this->getPerformanceMetrics();

        return [
            'propertyTypes' => $propertyTypes,
            'monthlyTrends' => $monthlyTrends,
            'locationDistribution' => $locationDistribution,
            'performanceMetrics' => $performanceMetrics
        ];
    }

    private function getPerformanceMetrics()
    {
        // Calculate actual performance metrics from database
        $soldProperties = Property::whereNotNull('sold_at')->get();

        // Days on Market calculation
        $avgDaysOnMarket = 45; // Default
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
        $saleToListRatio = 98.2; // Default
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

    private function calculateGrowth($current, $previous)
    {
        if ($previous == 0) return $current > 0 ? 100 : 0;
        return (($current - $previous) / $previous) * 100;
    }

    private function getColorForType($type)
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

    public function trackView(Request $request, $property_id)
    {
        $property = Property::findOrFail($property_id);

        $recentview = PropertyView::where('property_id', $property_id)
            ->where('session_id', session()->getid())
            ->where('viewed_at','>=', now()->subMinutes(30))
            ->exists();

        if (!$recentview) {
            PropertyView::create([
                'property_id' => $property_id,
                'user_id' => auth()->id(),
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
                'session_id' => session()->getId(),
                'view_source' => $request->input('view_source', 'direct'),
            ]);
            // $property->increment('view_count');
        }
        return response()->json(['success' => true]);
    }

    public function getPropertyViews($propertyId)
    {
        $views = PropertyView::where('property_id', $propertyId)
            ->selectRaw('COUNT(*) as total_views')
            ->selectRaw('COUNT(DISTINCT user_id) as unique_users')
            ->selectRaw('COUNT(DISTINCT ip_address) as unique_ips')
            ->first();

        $dailyViews = PropertyView::where('property_id', $propertyId)
            ->where('created_at', '>=', now()->subDays(30))
            ->selectRaw('DATE(created_at) as date, COUNT(*) as views')
            ->groupBy('date')
            ->orderBy('date', 'desc')
            ->get();

        return response()->json([
            'total_views' => $views->total_views,
            'unique_users' => $views->unique_users,
            'unique_ips' => $views->unique_ips,
            'daily_views' => $dailyViews
        ]);
    }

    public function get_property_list_by_userID(Request $request)
    {
        if (!in_array($request->user()->role_id, [1, 2, 3, 6])) {
            return response()->json(['message' => 'Forbidden, You are not Authorized'], 403);
        }

        // Get properties with all related data and analytics
        $propertyList = Property::with([
            'property_views',
            'images',
            'inquiries',
            'offers'
        ])
            ->where('agent_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(10);

        // Enhance each property with calculated analytics
        $enhancedProperties = $propertyList->getCollection()->map(function ($property) {
            return $this->proprtyService->enhancePropertyWithAnalytics($property);
        });

        // Replace the collection with enhanced data
        $propertyList->setCollection($enhancedProperties);

        return response()->json([
            'status' => 1,
            'data' => $propertyList
        ]);
    }

     /**
     * Get single property with detailed analytics
     */
    public function getPropertyWithAnalytics($id)
    {
        $property = Property::with(['property_views', 'images', 'inquiries', 'offers'])
            ->findOrFail($id);

        $enhancedProperty = $this->proprtyService->enhancePropertyWithAnalytics($property);

        return response()->json([
            'status' => 1,
            'data' => $enhancedProperty
        ]);
    }

    /**
     * Get dashboard overview
     */
    public function getDashboardOverview(Request $request)
    {
        $properties = Property::with(['property_views', 'inquiries', 'offers'])
            ->where('agent_id', auth()->id())
            ->get();

        $summary = $this->proprtyService->getDashboardSummary($properties);

        return response()->json([
            'status' => 1,
            'data' => $summary
        ]);
    }

    public function getDashboardData(Request $request)
    {
        $sellerId = auth()->id();
        
        // Get seller's properties with counts
        $properties = Property::where('agent_id', $sellerId)
            ->withCount(['property_views', 'inquiries', 'offers'])
            ->get();

        // Calculate overview metrics
        $totalListings = $properties->count();
        $activeListings = $properties->where('status', 'for_sale')->count();
        $totalViews = $properties->sum('property_views_count');
        
        $pendingOffers = PropertyOffer::whereHas('property', function($query) use ($sellerId) {
            $query->where('agent_id', $sellerId);
        })->where('status', 'pending')->count();

        // Recent activity (last 7 days views)
        $recentActivity = PropertyView::whereHas('property', function($query) use ($sellerId) {
            $query->where('agent_id', $sellerId);
        })
        ->with('property')
        ->select('property_id', DB::raw('COUNT(*) as view_count'), DB::raw('MAX(viewed_at) as last_viewed'))
        ->where('viewed_at', '>=', now()->subDays(7))
        ->groupBy('property_id')
        ->orderBy('last_viewed', 'desc')
        ->limit(5)
        ->get()
        ->map(function($view) {
             $carbon = new \Carbon\Carbon($view->last_viewed);
            return [
                'property_title' => $view->property->title,
                'view_count' => $view->view_count,
                'last_viewed' => $carbon->diffForHumans(),
            ];
        });

        // Performance data for charts
        $performanceData = $this->proprtyService->getPerformanceData($sellerId);

        return response()->json([
            'overview' => [
                'totalListings' => $totalListings,
                'activeListings' => $activeListings,
                'totalViews' => $totalViews,
                'pendingOffers' => $pendingOffers,
                'unreadMessages' => 0, // Implement if you have messaging
                'totalInquiries' => $properties->sum('inquiries_count'),
                'soldProperties' => $properties->where('status', 'sold')->count(),
            ],
            'recentActivity' => $recentActivity,
            'performance' => $performanceData,
            'topPerforming' => $this->proprtyService->getTopPerformingProperties($sellerId),
            'recentInquiries' => $this->proprtyService->getRecentInquiries($sellerId)
        ]);
    }   
   
}
