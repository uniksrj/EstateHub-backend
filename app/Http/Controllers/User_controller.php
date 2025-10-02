<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Http\Request;

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
}
