<?php

namespace App\Http\Controllers;

use App\Events\ResponseMessage;
use App\Http\Requests\StoreOfferRequest;
use App\Models\Deal;
use App\Models\DealLoss;
use App\Models\DealPipeline;
use App\Models\Favorite;
use App\Models\Inquiry;
use App\Models\InquiryResponse;
use App\Models\Property;
use App\Models\PropertyOffer;
use App\Models\User;
use App\Services\ActivityService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Common_setup extends Controller
{
    public function index()
    {
        return view('common_setup');
    }

    public function show($id)
    {
        // Logic to show a specific resource
    }

    public function toggleFavorite(Request $request)
    {
        $propertyId = $request->input('property_id');
        $user = $request->user();
        $favorite = Favorite::where([
            'user_id' => $user->id,
            'property_id' => $propertyId
        ])->first();
        if ($favorite) {
            $favorite =  $favorite->toArray();
            Favorite::where('id', $favorite['id'])->delete();
            $isFavorite = false;
        } else {
            Favorite::create([
                'user_id' => $user->id,
                'property_id' => $propertyId
            ]);
            $isFavorite = true;
        }
        return response()->json(['is_favorite' => $isFavorite]);
    }

    /* Store Inquiry*/

    public function store_buyer_inquiry(Request $request)
    {

        DB::beginTransaction();
        try {
            $validatedData = $request->validate([
                'name' => 'required|string|max:50',
                'email' => 'required|string|max:50',
                'phone' => 'required|numeric|digits_between:10,14',
                'message' => 'required|string',
                'timeline' => 'required|string',
                'budget_min' => 'required|numeric',
                'budget_max' => 'required|numeric',
                'property_id' => 'required|numeric',
            ]);

            $property = Inquiry::create([
                ...$validatedData,
                'user_id' => auth()->id(),
                'source' => 'website',
                'featured' => $request->boolean('featured', false),
                'status' => 0
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Inquiry created successfully',
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

    public function list_inquiries_by_user(Request $request)
    {
        $userId = auth()->id();
        $user = auth()->user();
        $userType = $user->role_id;
        $query = Inquiry::with([
            'property',
            'user',
            'responses' => function ($query) {
                $query->orderBy('created_at', 'asc');
            },
            'responses.sender'
        ]);

        if ($userType == 6 || $userType == 3) {
            $query->whereHas('property', function ($q) use ($userId) {
                $q->where('agent_id', $userId);
            });
        } elseif ($userType == 5) {
            $query->where('user_id', $userId);
        }

        $inquiries = $query->orderBy('created_at', 'desc')->get();

        // Transform data for frontend
        $transformedInquiries = $inquiries->map(function ($inquiry) {
            return [
                'id' => $inquiry->id,
                'buyerName' => $inquiry->user ? $inquiry->user->name : $inquiry->name,
                'buyerEmail' => $inquiry->user ? $inquiry->user->email : $inquiry->email,
                'buyerPhone' => $inquiry->user ? $inquiry->user->phone : $inquiry->phone,
                'propertyTitle' => $inquiry->property->title ?? 'Unknown Property',
                'propertyId' => $inquiry->property_id,
                'propertyPrice' => $inquiry->property->price ?? 0,
                'propertyType' => $inquiry->property->property_type ?? '',
                'message' => $inquiry->message,
                'timeline' => $inquiry->timeline,
                'budget_min' => $inquiry->budget_min,
                'budget_max' => $inquiry->budget_max,
                'status' => $inquiry->status,
                'important' => in_array($inquiry->priority, ['high', 'urgent']),
                'createdAt' => $inquiry->created_at,
                'responses' => $inquiry->responses->map(function ($response) {
                    return [
                        'id' => $response->id,
                        'message' => $response->message,
                        'sender' => $response->sender_type,
                        'sender_id' => $response->sender_id,
                        'sender_name' => $response->sender ? $response->sender->name : 'Unknown',
                        'timestamp' => $response->created_at,
                        'is_read' => $response->is_read
                    ];
                })
            ];
        });

        return response()->json($transformedInquiries);
    }

    /**
     * Add response to inquiry (both seller and buyer)
     */
    public function addResponse(Request $request, $inquiryId)
    {
        $request->validate([
            'message' => 'required|string|min:1'
        ]);
        $user = auth()->user();
        $inquiry = Inquiry::findOrFail($inquiryId);

        // Check if user has permission to respond to this inquiry
        if ($user->role_id == 6) { // Seller
            // Verify seller owns the property
            if ($inquiry->property->agent_id != $user->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        } elseif ($user->role_id == 5) { // Buyer
            // Verify buyer made this inquiry
            if ($inquiry->user_id != $user->id) {
                return response()->json(['error' => 'Unauthorized'], 403);
            }
        }

        $return_response = DB::transaction(function () use ($request, $inquiryId, $user, $inquiry) {
            // Determine sender type based on user role
            $senderType = ($user->role_id == 6) ? 'seller' : 'buyer';

            // Create response
            $response = InquiryResponse::create([
                'inquiry_id' => $inquiryId,
                'sender_type' => $senderType,
                'sender_id' => $user->id,
                'message' => $request->message,
                'is_read' => ($senderType === 'seller')
            ]);

            if ($senderType === 'seller') {
                $newStatus = 1;
                $inquiry->update([
                    'status' => $newStatus,
                    'responded_at' => now(),
                    'responded_by' => $user->id,
                    'response_notes' => 'Seller responded to inquiry'
                ]);
            } else {
                // Buyer is responding - update status accordingly
                if ($inquiry->status === 1) {
                    $newStatus = 2;
                } else {
                    $newStatus = 2;
                }
                $inquiry->update([
                    'status' => $newStatus
                ]);
            }

            $response->sender_name = $response->sender ? $response->sender->name : 'Unknown';
            $response->timestamp = $response->created_at?->toIso8601String();

            // 🔥 Broadcast new response event to others (except sender)
            broadcast(new ResponseMessage($response))->toOthers();
            // broadcast(new ResponseMessage($response));
            return $response;
        });


        return response()->json([
            'success' => true,
            'message' => 'Response sent successfully',
            'data' => $return_response
        ]);
    }

    /**
     * Get specific inquiry with full conversation
     */
    public function getInquiry($inquiryId)
    {
        $user = auth()->user();
        $userType = $user->role_id;

        $inquiry = Inquiry::with([
            'property',
            'user',
            'responses' => function ($query) {
                $query->orderBy('created_at', 'asc');
            },
            'responses.sender',
            'respondedBy'
        ])->findOrFail($inquiryId);

        // Authorization check
        if ($userType == 6 && $inquiry->property->user_id != $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        if ($userType == 5 && $inquiry->user_id != $user->id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        return response()->json([
            'id' => $inquiry->id,
            'buyerName' => $inquiry->user ? $inquiry->user->name : $inquiry->name,
            'buyerEmail' => $inquiry->user ? $inquiry->user->email : $inquiry->email,
            'buyerPhone' => $inquiry->user ? $inquiry->user->phone : $inquiry->phone,
            'propertyTitle' => $inquiry->property->title,
            'propertyId' => $inquiry->property_id,
            'propertyPrice' => $inquiry->property->price,
            'propertyCity' => $inquiry->property->city,
            'propertyState' => $inquiry->property->state,
            'propertyBedrooms' => $inquiry->property->bedrooms,
            'propertyBathrooms' => $inquiry->property->bathrooms,
            'propertySqft' => $inquiry->property->sq_ft,
            'propertyType' => $inquiry->property->property_type,
            'propertyImage' => $inquiry->property->images ?
                (is_array($inquiry->property->images) ? $inquiry->property->images[0] : json_decode($inquiry->property->images)[0])
                : null,
            'message' => $inquiry->message,
            'timeline' => $inquiry->timeline,
            'budget_min' => $inquiry->budget_min,
            'budget_max' => $inquiry->budget_max,
            'status' => $inquiry->status,
            'priority' => $inquiry->priority,
            'important' => in_array($inquiry->priority, ['high', 'urgent']),
            'createdAt' => $inquiry->created_at,
            'respondedAt' => $inquiry->responded_at,
            'responses' => $inquiry->responses->map(function ($response) {
                return [
                    'id' => $response->id,
                    'message' => $response->message,
                    'sender' => $response->sender_type,
                    'sender_id' => $response->sender_id,
                    'sender_name' => $response->sender ? $response->sender->name : 'Unknown',
                    'timestamp' => $response->created_at,
                    'is_read' => $response->is_read
                ];
            })
        ]);
    }

    /**
     * Mark responses as read
     */
    public function markAsRead(Request $request, $inquiryId)
    {
        $user = auth()->user();

        InquiryResponse::where('inquiry_id', $inquiryId)
            ->where('is_read', false)
            ->where('sender_type', '!=', ($user->role_id == 6 ? 'seller' : 'buyer'))
            ->update(['is_read' => true]);

        return response()->json(['success' => true]);
    }

    public function closeInquiry($inquiryId, Request $request)
    {
        $user = auth()->user();
        $request->validate([
            'status' => 'required|numeric',
            'close_reason' => 'required|string'
        ]);

        try {
            DB::transaction(function () use ($user, $inquiryId, $request) {

                // 🔹 Mark all unread messages as read
                InquiryResponse::where('inquiry_id', $inquiryId)
                    ->where('is_read', false)
                    ->where('sender_type', '!=', ($user->role_id == 6 ? 'seller' : 'buyer'))
                    ->update(['is_read' => true]);

                // 🔹 Update inquiry status
                Inquiry::where('id', $inquiryId)->update([
                    'status' => $request->status,
                    'closed_by' => $user->id,
                    'closed_at' => now(),
                    'close_reason' => $request->close_reason ?? null,
                ]);
            });

            return response()->json([
                'success' => true,
                'message' => 'Inquiry closed successfully.',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to close inquiry.',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    public function store_agent_deal_loss(Request $request)
    {
        if (auth()->user()->role_id != 3) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $validated = $request->validate([
            'property_id' => 'required|exists:properties,id',
            'reason' => 'required|string|max:255',
            'notes' => 'required|string|max:1000',
            'buyer_id' => 'nullable|exists:users,id',
        ]);

        $property = Property::where('id', $validated['property_id'])
            ->where('agent_id', auth()->id())
            ->firstOrFail();

        DealLoss::create([
            'agent_id' => auth()->id(),
            'property_id' => $validated['property_id'],
            'buyer_id' => $validated['buyer_id'] ?? null,
            'reason' => $validated['reason'],
            'details' => $validated['notes'],
            'lost_at' => now(),
        ]);

        return response()->json(['success' => true]);
    }

    public function getAgentBuyers($type = "")
    {
        if (auth()->user()->role_id != 3) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }
        $agent = auth()->user();

        $buyers = User::where('role_id', 5)
            ->whereHas('inquiries.property', function ($query) use ($agent) {
                $query->where('agent_id', $agent->id);
            })
            ->withCount([
                'inquiries as inquiries_count' => fn($q) =>
                $q->whereHas('property', fn($p) => $p->where('agent_id', $agent->id)),
                'purchases as deals_count' => fn($q) =>
                $q->whereHas('property', fn($p) => $p->where('agent_id', $agent->id))
            ])
            ->select('id', 'name', 'email', 'phone', 'created_at', 'is_active', 'role_id')
            ->get()
            ->map(function ($buyer) {
                $buyer->inquiries_count = $buyer->inquiries_count ?? 0;
                $buyer->deals_count = $buyer->deals_count ?? 0;
                return $buyer;
            });;

        if ($type === "dropdown") {
            $buyers = $buyers->map(function ($buyer) {
                return [
                    'value' => $buyer->id,
                    'label' => $buyer->name . ' (' . $buyer->email . ')'
                ];
            });
            return response()->json(['buyers' => $buyers]);
        }
        if ($type === "array") {
            return $buyers->toArray();
        }
        return response()->json(['buyers' => $buyers]);
    }

    public function get_agent_deal_losses(Request $request)
    {
        if (auth()->user()->role_id != 3) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $dealLosses = DealLoss::with(['property', 'buyer'])
            ->where('agent_id', auth()->id())
            ->orderBy('lost_at', 'desc')
            ->get();
        $agent_buyer = $this->getAgentBuyers("array");
        $agent_pipeline = $this->get_agent_pipeline_data($request, auth()->id(), "array");
        return response()->json(['deal_losses' => $dealLosses, 'agent_buyer' => $agent_buyer, 'agent_pipeline' => $agent_pipeline]);
    }

    public function get_agent_pipeline_data(Request $request, $agentId = null, $type = "")
    {
        if (auth()->user()->role_id != 3) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        // Get all deals for this agent
        $deals = DealPipeline::where('agent_id', $agentId)->get();

        // Group by stage (status)
        $stages = ['prospecting', 'contacted', 'showing', 'negotiation', 'under_review', 'closed_won', 'closed_lost'];

        $pipeline = collect($stages)->map(function ($stage) use ($deals) {
            $filtered = $deals->where('status', $stage);
            return [
                'stage' => ucfirst(str_replace('_', ' ', $stage)),
                'deals' => $filtered->count(),
                'value' => round($filtered->sum('offer_price') / 1000000, 2),
            ];
        });

        $totalDeals = $deals->count();
        $closedWon = $deals->where('status', 'closed_won')->count();
        $closedLost = $deals->where('status', 'closed_lost')->count();

        if ($type === "array") {
            return [
                'pipeline' => $pipeline->toArray(),
                'summary' => [
                    'totalDeals' => $totalDeals,
                    'activeDeals' => $deals->whereNotIn('status', ['closed_won', 'closed_lost'])->count(),
                    'closedWon' => $closedWon,
                    'closedLost' => $closedLost,
                    'pipelineValue' => $deals->sum('offer_price')
                ]
            ];
        }

        return response()->json([
            'pipeline' => $pipeline,
            'summary' => [
                'totalDeals' => $totalDeals,
                'activeDeals' => $deals->whereNotIn('status', ['closed_won', 'closed_lost'])->count(),
                'closedWon' => $closedWon,
                'closedLost' => $closedLost,
                'pipelineValue' => $deals->sum('offer_price')
            ]
        ]);
    }

    public function get_offers()
    {
        if (!in_array(auth()->user()->role_id, [5, 3, 6])) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $user = auth()->user();
        $userId = $user->id;
        $userRole = $user->role_id;

        $offers = PropertyOffer::with([
            'agent',
            'property.agent:id,name,email,phone,avatar,bio',
            'property.images',
            'buyer:id,name,email,phone',
            'loanApplications:id,offer_id,loan_amount,loan_term,interest_rate,status,created_at'
        ]);

        if ($userRole === 5) {
            $offers->where('buyer_id', $userId);
        } elseif (in_array($userRole, [3, 6])) {
            $offers->whereHas('property', function ($q) use ($userId) {
                $q->where('agent_id', $userId);
            });
        }

        $offers = $offers->orderBy('created_at', 'desc')->get();

        return response()->json(['offers' => $offers]);
    }

    public function store_offer_details(StoreOfferRequest $request)
    {
        if (auth()->user()->role_id != 5) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        try {
            $validated = $request->validated();
            $offer = PropertyOffer::create($validated);

            return response()->json([
                'message' => 'Offer submitted successfully!',
                'offer' => $offer,
                'offer_id' => $offer->id
            ], 201);
        } catch (\Exception $e) {
            Log::error('Failed to store offer: ' . $e->getMessage());

            return response()->json([
                'message' => 'Failed to submit offer. Please try again.',
                'error' => config('app.debug') ? $e->getMessage() : 'Internal server error'
            ], 500);
        }
    }

    public function updateBuyerOfferStatus($id, Request $request)
    {
        $offer = PropertyOffer::findOrFail($id);

        if (auth()->id() !== $offer->buyer_id) {
            return response()->json(['error' => 'Unauthorized'], 403);
        }

        $request->validate([
            'status' => 'required|in:cancelled,accepted,rejected,pending'
        ]);

        $validTransitions = [
            'pending' => ['cancelled'],
            'counter_offer' => ['accepted', 'rejected'],
            'expired' => ['pending'],
        ];

        if (!in_array($request->status, $validTransitions[$offer->status] ?? [])) {
            return response()->json([
                'error' => 'Invalid status transition',
                'current_status' => $offer->status,
                'requested_status' => $request->status,
                'valid_transitions' => $validTransitions[$offer->status] ?? []
            ], 422);
        }
        $updateData = [
            'status' => $request->status,
            'updated_at' => now(),
        ];

        if ($request->status === 'accepted') {
            $updateData['accepted_at'] = now();
            $updateData['rejected_at'] = null;
        } elseif ($request->status === 'rejected') {
            $updateData['rejected_at'] = now();
            $updateData['accepted_at'] = null;
        }

        $offer->update($updateData);

        if ($request->status === 'accepted') {
            $property = Property::find($offer->property_id);
            $buyerAgent = auth()->user();

            $deal = Deal::create([
                'offer_id' => $offer->id,
                'property_id' => $offer->property_id,
                'buyer_id' => $offer->buyer_id,
                'seller_id' => $property->agent_id,
                'agent_id' => $property->agent_id,
                'final_price' => $offer->counter_offer_amount ?? $offer->offer_amount,
                'status' => 'under_contract',
                'current_step' => 'contract_generation',
                'progress_percentage' => 0,
                'accepted_date' => now(),
                'expected_closing_date' => now()->addDays(45),
                'inspection_deadline' => now()->addDays(10),
                'mortgage_deadline' => now()->addDays(30),
                'appraisal_deadline' => now()->addDays(20)
            ]);

            ActivityService::log(
                $deal,
                'offer_accepted',
                'accepted',
                'Purchase Offer',
                'offer_acceptance',
                'completed',
                $buyerAgent,
                null,
                [
                    'offer_amount' => $deal->final_price,
                    'property_address' => $property->address,
                    'acceptance_date' => now()->toDateString()
                ]
            );
            // Send notifications
            // Notification::send(...)
        }

        return response()->json([
            'message' => 'Offer status updated successfully',
            'offer' => $offer
        ]);
    }

    public function updateAgentOfferStatus($id, Request $request)
    {
        $user = auth()->user();
        $offer = PropertyOffer::findOrFail($id);

        if ($user->role_id === 3 && $offer->property->agent_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized for this property'], 403);
        }

        if ($user->role_id === 6 && $offer->property->user_id !== $user->id) {
            return response()->json(['error' => 'Unauthorized for this property'], 403);
        }

        $request->validate([
            'status' => 'required|in:accepted,rejected,counter_offer',
            'counter_offer_amount' => 'nullable|numeric|min:1',
            'counter_offer_message' => 'nullable|string|max:1000'
        ]);

        $validTransitions = [
            'pending' => ['accepted', 'rejected', 'counter_offer'],
            'counter_offer' => ['accepted', 'rejected'],
        ];

        if (!in_array($request->status, $validTransitions[$offer->status] ?? [])) {
            return response()->json([
                'error' => 'Invalid status transition',
                'current_status' => $offer->status,
                'requested_status' => $request->status,
                'valid_transitions' => $validTransitions[$offer->status] ?? []
            ], 422);
        }
        $updateData = [
            'status' => $request->status,
            'counter_offer_amount' => $request->counter_offer_amount,
            'counter_offer_message' => $request->counter_offer_message,
            'updated_at' => now(),
        ];

        if ($request->status === 'accepted') {
            $updateData['accepted_at'] = now();
            $updateData['rejected_at'] = null;
        } elseif ($request->status === 'rejected') {
            $updateData['rejected_at'] = now();
            $updateData['accepted_at'] = null;
        }

        $offer->update($updateData);

        if ($request->status === 'accepted') {
            $property = Property::find($offer->property_id);
            $buyerAgent = auth()->user();

            $deal = Deal::create([
                'offer_id' => $offer->id,
                'property_id' => $offer->property_id,
                'buyer_id' => $offer->buyer_id,
                'seller_id' => $property->agent_id,
                'agent_id' => $property->agent_id,
                'final_price' => $offer->counter_offer_amount ?? $offer->offer_amount,
                'status' => 'under_contract',
                'current_step' => 'contract_generation',
                'progress_percentage' => 0,
                'accepted_date' => now(),
                'expected_closing_date' => now()->addDays(45),
                'inspection_deadline' => now()->addDays(10),
                'mortgage_deadline' => now()->addDays(30),
                'appraisal_deadline' => now()->addDays(20)
            ]);

            ActivityService::log(
                $deal,
                'offer_accepted',
                'accepted',
                'Purchase Offer',
                'offer_acceptance',
                'completed',
                $buyerAgent,
                null,
                [
                    'offer_amount' => $deal->final_price,
                    'property_address' => $property->address,
                    'acceptance_date' => now()->toDateString()
                ]
            );

            // Send notifications
            // Notification::send(...)
        }

        return response()->json([
            'message' => 'Offer status updated successfully',
            'offer' => $offer
        ]);
    }

    public function deleteOffer($id)
    {
        DB::beginTransaction();
        try {
            $offer = PropertyOffer::find($id);
            if (!$offer) {
                return response()->json(['message' => 'Property Offer not found'], 404);
            }
            if ($offer->buyer_id !== auth()->id()) {
                return response()->json(['message' => 'Unauthorized'], 403);
            }

            $offer->delete();

            DB::commit();
            return response()->json(['message' => 'Property offer deleted successfully']);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to delete property',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}
