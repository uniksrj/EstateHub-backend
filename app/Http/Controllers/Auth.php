<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserPreference;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class Auth extends Controller
{
    public function __construct() {}

    public function index()
    {
        return response()->json(['message' => 'Auth controller']);
    }

    public function register(Request $request)
    {
        $validatedData = $request->validate([
            // Basic Info
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'phone' => 'required|string|max:20',

            // Preferences
            'user_type' => 'required|in:buyer,seller,investor,renter,agent,other',
            'preferred_location' => 'nullable|string|max:255',
            'min_budget' => 'nullable|numeric|min:0',
            'max_budget' => 'nullable|numeric|min:0',
            'property_type' => 'nullable|in:house,apartment,condo,townhouse,villa,commercial,land,any',
            'bedrooms' => 'nullable|integer|min:1|max:10',
            'bathrooms' => 'nullable|integer|min:1|max:10',
            'move_in_timeline' => 'nullable|in:immediately,1_month,3_months,6_months,1_year,flexible',
            'newsletter' => 'boolean',
            'terms' => 'required|accepted',
        ]);

        // Start database transaction
        DB::beginTransaction();

        try {
            // Create user
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']), // More secure than bcrypt()
                'phone' => $validatedData['phone'],
                'role' => $validatedData['user_type'],
                'is_active' => true,
            ]);

            // Create user preferences
            $user->preferences()->create([
                'preferred_location' => $validatedData['preferred_location'],
                'min_budget' => $validatedData['min_budget'],
                'max_budget' => $validatedData['max_budget'],
                'property_type' => $validatedData['property_type'],
                'bedrooms' => $validatedData['bedrooms'],
                'bathrooms' => $validatedData['bathrooms'],
                'move_in_timeline' => $validatedData['move_in_timeline'],
                'newsletter' => $validatedData['newsletter'] ?? false,
            ]);

            DB::commit();

            // Login user
            auth()->login($user);
            $token = $user->createToken(
                'auth-token',
                ['*'],
                now()->addWeek()
            )->plainTextToken;
            return response()->json([
                'message' => 'Registration successful',
                'user' => $user->load('preferences'),
                'success' => true,
                'token' => $token
            ], 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return response()->json([
                'message' => 'Registration failed',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json(['error' => $validator->errors()->first()], 422);
        }

        $user = User::where('email', $request->email)->first();

        if (!$user || !Hash::check($request->password, $user->password)) {
            return response()->json(['error' => 'Invalid credentials'], 401);
        }

        if (!$user->is_active) {
            FacadesAuth::logout();
            return response()->json(['error' => 'Your account has been deactivated.'], 401);
        }

        // Sanctum v4 token creation
        $token = $user->createToken('auth-token', ['*'])->plainTextToken;
        FacadesAuth::login($user, $request->remember ?? false);
        $request->session()->regenerate();
        $cookie = cookie(
            'auth_token',
            $token,
            60 * 24 * 1,
            '/',
            null,
            true,
            true,
            false,
            'Strict'
        );
        return response()->json([
            'user' => $user->load('preferences'),
            'message' => 'Login successful',
            'success' => true
        ])->header('Access-Control-Allow-Credentials', 'true')
          ->header('Access-Control-Allow-Origin', 'http://localhost:5173');
    }

    public function logout(Request $request)
    {
        // Sanctum v4: Revoke the current token based 
        // $request->user()->currentAccessToken()->delete();
        // Revoke all FacadesAuth::logout(); sessions for the user
        auth()->guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return response()->json([
            'message' => 'Successfully logged out',
            'success' => true
        ]);
    }

    public function user(Request $request)
    {
        return response()->json([
            'user' => $request->user()->load('preferences')
        ]);
    }

    public function change_password(Request $request)
    {
        $request->validate([
            'current_password' => 'required',
            'new_password' => ['required', 'confirmed', Rules\Password::defaults()],
        ]);

        $user = $request->user();

        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json(['error' => 'Current password is incorrect'], 401);
        }

        $user->password = Hash::make($request->new_password);
        $user->save();

        return response()->json([
            'message' => 'Password changed successfully',
            'success' => true
        ]);
    }
}
