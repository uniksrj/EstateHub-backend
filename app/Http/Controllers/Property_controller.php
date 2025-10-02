<?php

namespace App\Http\Controllers;

use App\Models\Property;
use App\Models\PropertyImage;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class Property_controller extends Controller
{
    public function __construct() {}

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
        $properties = Property::with(['agent', 'images'])
            ->withFilters($request->all())
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

    public function delete_property($id)
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
}
