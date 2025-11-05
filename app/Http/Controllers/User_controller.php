<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Property;
use App\Models\PropertyTours;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class User_controller extends Controller
{
    public function __construct() {}

    public function get_user_profile_details(Request $request) {}

    public function update_user_profile_details(Request $request)
    {

        $user = $request->user();

        $validated = $request->validate([
            'name' => 'sometimes|string|max:255',
            'phone' => 'sometimes|nullable|string|max:20',
            'bio' => 'sometimes|nullable|string',
            'avatar' => 'sometimes|nullable|image|max:2048',
        ]);

        if ($request->hasFile('avatar')) {
            $path = $request->file('avatar')->store('avatars', 'public');
            $validated['avatar'] = $path;
        }

        $user->update($validated);

        return response()->json([
            'message' => 'Profile updated successfully',
            'user' => $user->fresh()
        ]);
    }

    public function getUserFavorites(Request $request)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json([
                'message' => 'User not authenticated'
            ], 401);
        }

        $favorites = Favorite::with([
            'properties.images',
            'properties.agent'
        ])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get()
            ->map(function ($favorite) {
                return [
                    'favorite_id' => $favorite->id,
                    'favorited_at' => $favorite->created_at,
                    'property' => $favorite->properties
                ];
            });

        return response()->json([
            'favorites' => $favorites,
            'count' => $favorites->count()
        ]);
    }

    public function store_schedule(Request $request)
    {
        $validated = $request->validate([
            'property_id' => 'required|integer|exists:properties,id',
            'date' => 'required|date',
            'agent_id' => 'required|integer',
            'time' => 'required|string',
            'meeting_type' => 'required|string|in:in-person,virtual',
            'notes' => 'sometimes|nullable|string',
        ]);

        // Check for existing active schedule
        $hasActiveSchedule = PropertyTours::where('buyer_id', auth()->id())
            ->where('property_id', $validated['property_id'])
            ->whereIn('status', ['pending', 'approved'])
            ->exists();

        if ($hasActiveSchedule) {
            return response()->json([
                'message' => 'You already have an active schedule for this property. Please complete or cancel it before booking again.',
            ], 409);
        }

        // Prevent same-time double booking
        $conflict = PropertyTours::where('agent_id', $validated['agent_id'])
            ->where('scheduled_at', "{$validated['date']} {$validated['time']}")
            ->exists();

        if ($conflict) {
            return response()->json([
                'message' => 'The selected time slot is not available. Please choose another time.',
            ], 409);
        }
        // Here you would typically store the schedule in the database.
        DB::beginTransaction();
        try {

            $schedule = PropertyTours::create([
                'buyer_id' =>  auth()->id(),
                'property_id' => $validated['property_id'],
                'agent_id' => $validated['agent_id'],
                'scheduled_at' => "{$validated['date']} {$validated['time']}",
                'status' => 'pending',
                'meeting_type' => $validated['meeting_type'],
                'is_virtual' => $validated['meeting_type'] === 'virtual' ? 1 : 0,
                'notes' => $validated['notes'] ?? null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Failed to create schedule',
                'error' => $e->getMessage()
            ], 500);
        }

        return response()->json([
            'message' => 'Schedule created successfully',
            'schedule' => $validated
        ], 201);
    }

    public function get_schedule(Request $request)
    {
        $user = auth()->user();

        $query = PropertyTours::with(['property', 'agent', 'buyer'])
            ->orderBy('scheduled_at', 'desc');

        // Role-based filtering
        if ($user->role_id == 5) {
            // Buyer
            $query->where('buyer_id', $user->id);
        } elseif ($user->role_id == 3) {
            // Agent
            $query->where('agent_id', $user->id);
        } elseif ($user->role_id == 1 || $user->role_id == 2) {
            // Admin: no filter (see all)
        } else {
            return response()->json([
                'message' => 'Unauthorized role.'
            ], 403);
        }

        $schedules = $query->get()->map(function ($schedule) {
            return [
                'id' => $schedule->id,
                'property' => [
                    'id' => $schedule->property->id ?? null,
                    'title' => $schedule->property->title ?? 'N/A',
                    'city' => $schedule->property->city ?? 'N/A',
                ],
                'agent' => [
                    'id' => $schedule->agent->id ?? null,
                    'name' => $schedule->agent->name ?? 'N/A',
                    'email' => $schedule->agent->email ?? 'N/A',
                ],
                'buyer' => [
                    'id' => $schedule->buyer->id ?? null,
                    'name' => $schedule->buyer->name ?? 'N/A',
                    'email' => $schedule->buyer->email ?? 'N/A',
                ],
                'scheduled_at' => $schedule->scheduled_at,
                'meeting_type' => $schedule->meeting_type,
                'status' => $schedule->status,
                'notes' => $schedule->notes,
            ];
        });

        return response()->json([
            'schedules' => $schedules
        ]);
    }

    public function update_schedule_status(Request $request)
    {
        $validated = $request->validate([
            'schedule_id' => 'required|integer|exists:property_tours,id',
            'status' => 'required|string|in:pending,approved,completed,cancelled',
        ]);

        $schedule = PropertyTours::find($validated['schedule_id']);

        // Role-based authorization
        $user = auth()->user();
        if ($user->role_id == 5 && $schedule->buyer_id != $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }
        if ($user->role_id == 3 && $schedule->agent_id != $user->id) {
            return response()->json(['message' => 'Unauthorized'], 403);
        }

        $schedule->status = $validated['status'];
        $schedule->updated_at = now();
        $schedule->save();

        return response()->json([
            'message' => 'Schedule status updated successfully',
            'schedule' => $schedule
        ]);
    }
}
