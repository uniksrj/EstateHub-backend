<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Http\Request;

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
}
