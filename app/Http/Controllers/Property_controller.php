<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyImage;
use App\Models\PropertyOffer;
use App\Models\PropertyView;
use App\Models\UserActivity;
use App\Services\CloudinaryService;
use App\Services\PropertyAnalyticsService;
use App\Services\UserMetricsService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class Property_controller extends Controller
{
    protected PropertyAnalyticsService $propertyService;
    protected CloudinaryService $cloudinaryService;
    protected $userActivityService;
    public function __construct(
        PropertyAnalyticsService $propertyService,
        UserMetricsService $userActivityService,
        CloudinaryService $cloudinaryService
    )
    {
        $this->propertyService = $propertyService;
        $this->userActivityService = $userActivityService;
        $this->cloudinaryService = $cloudinaryService;
    }

    public function index()
    {
        return response()->json(['message' => 'Property controller']);
    }

    public function add_property(Request $request)
    {
        DB::beginTransaction();
        try {
            // add a property
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
                'has_parking' => 'integer|boolean',
                'has_security' => 'integer|boolean',
                'has_air_conditioning' => 'integer|boolean',
                'has_heating' => 'integer|boolean',
                'images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            ]);
            if (isset($validatedData['features']) && is_string($validatedData['features'])) {

                $validatedData['features'] = json_encode($this->convert_features_to_json($validatedData['features']) ?? []);
            }

            if(empty($validatedData['garage'])){
                $validatedData['garage'] = 0;
            }

            $property = Property::create([
                ...$validatedData,
                'agent_id' => auth()->id(),
                'featured' => $request->boolean('featured', false),
                'status' => 'for_sale'
            ]);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $uploadedImage = $this->cloudinaryService->uploadPropertyImage($image, $property->slug, $index);
                    PropertyImage::create([
                        'property_id' => $property->id,
                        'image_path' => $uploadedImage['secure_url'],
                        'cloudinary_secure_url' => $uploadedImage['secure_url'],
                        'cloudinary_public_id' => $uploadedImage['public_id'],
                        'is_primary' => $index === 0,
                        'caption' => $property->title,
                        'order_index' => $index,
                    ]);
                }
            }

            $this->create_user_activity($request, $property);

            DB::commit();

            // ✅ TRIGGER PROPERTY MATCHING AFTER PROPERTY IS CREATED
            // This will find all preferences that match this new property
            $this->propertyService->matchNewPropertyToPreferences($property);

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

    public function create_user_activity(Request $request, $property = null)
    {
        UserActivity::create([
            'user_id' => auth()->id(),
            'activity_type' => 'property_created',
            'property_id' => $property->id,
            'inquiry_id' => null,
            'metadata' => null,
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return response()->json([
            'message' => 'User activity recorded successfully',
            'success' => true
        ], 201);
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
            ->boostedFirst()
            ->orderByDesc('created_at')
            ->paginate(6);
        return response()->json($properties);
    }

    public function get_properties(Request $request)
    {
        $properties = Property::with(['agent', 'images'])
            ->active()
            ->withFilters($request->all())
            ->boostedFirst()
            ->orderByDesc('created_at')
            ->paginate(12);
        return response()->json($properties);
    }

    public function get_property_details($idOrSlug)
    {
        $property = Property::with(['agent', 'images'])
            ->where('id', $idOrSlug)
            ->orWhere('slug', $idOrSlug)
            ->first();

        if (!$property) {
            return response()->json(['message' => 'Property not found'], 404);
        }
        return response()->json($property);
    }

    public function get_all_properties(Request $request)
    {
        $user = auth()->user();
        $properties = Property::with(['agent', 'images', 'favorites'])
            ->withFilters($request->all(), $user)
            ->where('status', 'for_sale')
            ->boostedFirst()
            ->orderByDesc('created_at')
            ->paginate(12);
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
                'has_parking' => 'integer|boolean',
                'has_security' => 'integer|boolean',
                'has_air_conditioning' => 'integer|boolean',
                'has_heating' => 'integer|boolean',
                'status' => 'sometimes|in:available,sold,rented,for_sale,for_rent',
                'featured' => 'sometimes|boolean',
                'images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:10240',
            ]);

            if (isset($validatedData['features']) && is_string($validatedData['features'])) {
                $featuresString = $validatedData['features'];

                // Clean up the string - remove extra quotes and newlines
                $featuresString = trim($featuresString);
                $featuresString = str_replace(['"', "'", "\n", "\\"], '', $featuresString);

                $validatedData['features'] = substr($featuresString, 0, 255);
            }
            $property->update($validatedData);

            if ($request->hasFile('images')) {
                foreach ($request->file('images') as $index => $image) {
                    $uploadedImage = $this->cloudinaryService->uploadPropertyImage($image, $property->slug, $index);
                    PropertyImage::create([
                        'property_id' => $property->id,
                        'image_path' => $uploadedImage['secure_url'],
                        'cloudinary_secure_url' => $uploadedImage['secure_url'],
                        'cloudinary_public_id' => $uploadedImage['public_id'],
                        'is_primary' => $index === 0,
                        'caption' => $property->title,
                        'order_index' => $index,
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
            ->withFilters($request->all(), auth()->user())
            ->boostedFirst()
            ->orderByDesc('created_at')
            ->get();

        $metrics = $this->propertyService->getMetricsDetails();
        $charts = $this->propertyService->getChartData();
        $activity = $this->userActivityService->getRecentActivity();

        return response()->json([
            'properties' => $properties,
            'metrics' => $metrics,
            'charts' => $charts,
            'activity' => $activity
        ]);
    }

    public function trackView(Request $request, $property_id)
    {
        $property = Property::where('id', $property_id)->orWhere('slug', $property_id)->firstOrFail();
        $property_id = $property->id;

        $recentview = PropertyView::where('property_id', $property_id)
            ->where('session_id', session()->getid())
            ->where('viewed_at', '>=', now()->subMinutes(30))
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
            ->boostedFirst()
            ->orderByDesc('created_at')
            ->paginate(10);

        // Enhance each property with calculated analytics
        $enhancedProperties = $propertyList->getCollection()->map(function ($property) {
            return $this->propertyService->enhancePropertyWithAnalytics($property);
        });

        $agentClientStats = [];
        if (in_array($request->user()->role_id, [3])) {
            $agentClientStats = $this->propertyService->getAgentClientsStats(auth()->id());
        }

        $propertyList->setCollection($enhancedProperties);

        return response()->json([
            'status' => 1,
            'data' => $propertyList,
            'agentClientStats' => $agentClientStats
        ]);
    }

    /**
     * Get single property with detailed analytics
     */
    public function getPropertyWithAnalytics($id)
    {
        $property = Property::with(['property_views', 'images', 'inquiries', 'offers'])
            ->findOrFail($id);

        $enhancedProperty = $this->propertyService->enhancePropertyWithAnalytics($property);

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

        $summary = $this->propertyService->getDashboardSummary($properties);

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

        $pendingOffers = PropertyOffer::whereHas('property', function ($query) use ($sellerId) {
            $query->where('agent_id', $sellerId);
        })->where('status', 'pending')->count();

        // Recent activity (last 7 days views)
        $recentActivity = PropertyView::whereHas('property', function ($query) use ($sellerId) {
            $query->where('agent_id', $sellerId);
        })
            ->with('property')
            ->select('property_id', DB::raw('COUNT(*) as view_count'), DB::raw('MAX(viewed_at) as last_viewed'))
            ->where('viewed_at', '>=', now()->subDays(7))
            ->groupBy('property_id')
            ->orderBy('last_viewed', 'desc')
            ->limit(5)
            ->get()
            ->map(function ($view) {
                $carbon = new \Carbon\Carbon($view->last_viewed);
                return [
                    'property_title' => $view->property->title,
                    'view_count' => $view->view_count,
                    'last_viewed' => $carbon->diffForHumans(),
                ];
            });

        // Performance data for charts
        $performanceData = $this->propertyService->getPerformanceData($sellerId);

        return response()->json([
            'overview' => [
                'totalListings' => $totalListings,
                'activeListings' => $activeListings,
                'totalViews' => $totalViews,
                'pendingOffers' => $pendingOffers,
                'unreadMessages' => 0,
                'totalInquiries' => $properties->sum('inquiries_count'),
                'soldProperties' => $properties->where('status', 'sold')->count(),
            ],
            'recentActivity' => $recentActivity,
            'performance' => $performanceData,
            'topPerforming' => $this->propertyService->getTopPerformingProperties($sellerId),
            'recentInquiries' => $this->propertyService->getRecentInquiries($sellerId)
        ]);
    }

    public function convert_features_to_json($input_data)
    {
        $featuresString = trim($input_data);
        $featuresString = str_replace(['"', "'", "\n", "\\"], '', $featuresString);

        $featuresArray = array_map('trim', explode(',', $featuresString));

        $featuresArray = array_filter($featuresArray);
        return $featuresArray;
    }

    public function get_property_by_type(Request $request)
    {
        $user = auth()->user();
        $type = $request->type;

        $query = Property::with(['agent', 'images', 'favorites'])
            ->withFilters($request->all(), $user);

        // 🔥 FOR SALE
        if ($type === 'for-sale') {
            $query->where('status', 'for_sale');
        }

        // 🔥 FOR RENT
        elseif ($type === 'for-rent') {
            $query->where('status', 'for_rent');
        }

        // 🔥 NEW
        elseif ($type === 'new') {
            $query->where('status', 'for_sale')
                ->where('created_at', '>=', now()->subDays(7));
        }

        // 🔥 LUXURY (Top 10% AFTER filters)
        elseif ($type === 'luxury') {

            $baseQuery = (clone $query)->where('status', 'for_sale');

            $count = $baseQuery->count();

            if ($count > 0) {

                $limit = max(1, ceil($count * 0.1));

                $minLuxuryPrice = $baseQuery
                    ->orderByDesc('price')
                    ->skip($limit - 1)
                    ->value('price');

                $query->where('status', 'for_sale')
                    ->where('price', '>=', $minLuxuryPrice);
            }
        }

        return response()->json(
            $query
                ->boostedFirst()
                ->orderByDesc('created_at')
                ->paginate(12)
        );
    }

    public function get_boost_plans()
    {
        return response()->json([
            'plans' => $this->boostPlans(),
        ]);
    }

    public function boost_property(Request $request)
    {
        $validated = $request->validate([
            'property_id' => ['required', 'integer', 'exists:properties,id'],
            'boost_type' => ['required', Rule::in(['basic', 'premium', 'homepage'])],
        ]);

        $property = Property::findOrFail($validated['property_id']);
        $user = $request->user();

        $canManageProperty = (int) $property->agent_id === (int) $user->id || in_array((int) $user->role_id, [1, 2], true);
        if (!$canManageProperty) {
            return response()->json(['message' => 'You are not authorized to boost this property.'], 403);
        }

        $plan = collect($this->boostPlans())->firstWhere('type', $validated['boost_type']);
        $startsAt = now();
        $expiresAt = (clone $startsAt)->addDays($plan['duration_days']);

        $property->update([
            'is_boosted' => true,
            'boost_type' => $validated['boost_type'],
            'boost_starts_at' => $startsAt,
            'boost_expires_at' => $expiresAt,
        ]);

        return response()->json([
            'message' => 'Property boost activated successfully.',
            'property' => $property->fresh(['images', 'agent']),
        ]);
    }

    protected function boostPlans(): array
    {
        return [
            [
                'type' => 'basic',
                'name' => 'Basic',
                'duration_days' => 3,
                'price' => 19,
                'description' => 'Priority placement in listing results for 3 days.',
            ],
            [
                'type' => 'premium',
                'name' => 'Premium',
                'duration_days' => 7,
                'price' => 39,
                'description' => 'Stronger listing priority for 7 days.',
            ],
            [
                'type' => 'homepage',
                'name' => 'Homepage',
                'duration_days' => 7,
                'price' => 59,
                'description' => 'Top listing priority plus homepage highlight for 7 days.',
            ],
        ];
    }
}
