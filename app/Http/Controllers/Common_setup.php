<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use App\Models\Inquiry;
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
    
    public function store_buyer_inquiry(Request $request){
        echo "<pre>";
        print_r($request->all());
        echo "</pre>";

         DB::beginTransaction();
        try {
            // Logic to add a property
            $validatedData = $request->validate([
                'name' => 'required|string|max:50',
                'email' => 'required|string|max:50',
                'phone' => 'required|numeric|max:12',
                'message' => 'required|string',
                'timeline' => 'required|string',
                'budget_min' => 'required|numeric',
                'budget_max' => 'required|numeric',
                
            ]);

            $property = Inquiry::create([
                ...$validatedData,
                'user_id ' => auth()->id(),
                'source  ' => 'website',
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
}
