<?php

namespace App\Http\Controllers;

use App\Events\ResponseMessage;
use App\Models\Favorite;
use App\Models\Inquiry;
use App\Models\InquiryResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

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
                'status' => 'new'
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

        if ($userType == 6) {
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
                'is_read' => ($senderType === 'seller') // Mark as read if seller sends
            ]);

            // Update inquiry status based on your existing status enum
            $newStatus = 'contacted'; // Default status

            if ($senderType === 'seller') {
                $newStatus = 'responded';
                $inquiry->update([
                    'status' => $newStatus,
                    'responded_at' => now(),
                    'responded_by' => $user->id,
                    'response_notes' => 'Seller responded to inquiry'
                ]);
            } else {
                // Buyer is responding - update status accordingly
                if ($inquiry->status === 'responded') {
                    $newStatus = 'contacted'; // Continue conversation
                } else {
                    $newStatus = 'contacted'; // Initial buyer follow-up
                }
                $inquiry->update([
                    'status' => $newStatus
                ]);
            }
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
}
