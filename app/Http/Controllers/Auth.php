<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
use App\Models\UserPreference;
use App\Services\UserMetricsService;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth as FacadesAuth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Validation\Rules;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class Auth extends Controller
{
    private $preferenceFields = [
        'buyer' => ['preferred_location', 'min_budget', 'max_budget', 'property_type', 'bedrooms', 'bathrooms', 'move_in_timeline', 'newsletter'],
        'investor' => ['preferred_location', 'min_budget', 'max_budget', 'property_type', 'bedrooms', 'bathrooms', 'move_in_timeline', 'newsletter'],
        'renter' => ['preferred_location', 'min_budget', 'max_budget', 'property_type', 'bedrooms', 'bathrooms', 'move_in_timeline', 'newsletter'],
        'seller' => ['property_address', 'selling_timeline', 'newsletter'],
        'agent' => ['license_number', 'agency_name', 'newsletter'],
        'broker' => ['license_number', 'agency_name', 'newsletter'],
    ];
    public function __construct(Request $request)
    {
        if (!$request->user()) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }
    }

    public function index()
    {
        return response()->json(['message' => 'Auth controller']);
    }

    public function validate_fields($request)
    {
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => ['required', 'confirmed', Rules\Password::defaults()],
            'user_type' => 'required|in:buyer,seller,investor,renter,agent,broker',
            'newsletter' => 'boolean',
            'terms' => 'required|accepted',
            'phone' => 'required|string|max:20',
        ];

        // Add dynamic rules based on user_type
        if ($request->user_type === 'agent' || $request->user_type === 'broker') {
            $rules['license_number'] = 'required|string|max:50';
            $rules['agency_name'] = 'nullable|string|max:255';
        }

        if ($request->user_type === 'buyer' || $request->user_type === 'investor' || $request->user_type === 'renter') {
            $rules['min_budget'] = 'nullable|numeric|min:0';
            $rules['max_budget'] = 'nullable|numeric|min:0';
            $rules['preferred_location'] = 'nullable|string|max:255';
            $rules['min_budget'] = 'nullable|numeric|min:0';
            $rules['max_budget'] = 'nullable|numeric|min:0';
            $rules['property_type'] = 'nullable|in:house,apartment,condo,townhouse,villa,commercial,land,any';
            $rules['bedrooms'] = 'nullable|integer|min:1|max:10';
            $rules['bathrooms'] = 'nullable|integer|min:1|max:10';
            $rules['move_in_timeline'] = 'nullable|in:immediately,1_month,3_months,6_months,1_year,flexible';
        }

        if ($request->user_type === 'seller') {
            $rules['selling_timeline'] = 'nullable|in:immediately,1_month,3_months,6_months,1_year,flexible';
            $rules['property_address'] = 'nullable|string|max:255';
        }
        $validatedData = $request->validate($rules);

        return $validatedData;
    }

    public function register(Request $request)
    {

        DB::beginTransaction();

        try {
            $validatedData = $this->validate_fields($request);
            $role_id = Role::findByName($validatedData['user_type']);
            if (!$role_id) {
                throw ValidationException::withMessages(['user_type' => 'Invalid user type']);
            }

            // Create user
            $user = User::create([
                'name' => $validatedData['name'],
                'email' => $validatedData['email'],
                'password' => Hash::make($validatedData['password']),
                'phone' => $validatedData['phone'],
                'role_id' => $role_id,
                'is_active' => true,
                'last_login_at' => now(),
            ]);

            // Create user preferences
            $fieldsToSave = $this->preferenceFields[$validatedData['user_type']] ?? [];
            $preferencesData = array_filter(
                $validatedData,
                fn($key) => in_array($key, $fieldsToSave),
                ARRAY_FILTER_USE_KEY
            );
            $user->preferences()->create($preferencesData);

            DB::commit();

            $token = $user->createToken(
                'auth-token',
                ['*'],
                now()->addWeek()
            )->plainTextToken;
            auth()->login($user, $request->remember ?? false);
            // FacadesAuth::login();
            $request->session()->regenerate();
            return response()->json([
                'user' => $user->load('preferences'),
                'message' => 'Login successful',
                'success' => true
            ])->header('Access-Control-Allow-Credentials', 'true')
                ->header('Access-Control-Allow-Origin', 'http://localhost:5173');
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
        auth()->login($user, $request->remember ?? false);
        $request->session()->regenerate();
        User::where('id', $user->id)->update(['last_login_at' => now()]);
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
        return response()->json($request->user()->load('preferences'));
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

    public function user_metrics(Request $request)
    {
        if (!in_array($request->user()->role_id, [1, 2])) {
            return response()->json(['message' => 'Forbidden, You are not Authorized'], 403);
        }

        $metricsService = new UserMetricsService();

        return response()->json([
            'roles' => $metricsService->getUserRolesDistribution(),
            'metrics' => $metricsService->getAllMetrics(),
            'charts' => [
                'monthly_growth' => $metricsService->getMonthlyUserGrowth(12),
                'cumulative_growth' => $metricsService->getCummulativeMonthlyGrowth(12),
                'retention_rates' => $metricsService->getWeeklyRetentionData(),
            ],
            'user_list' => User::with('role')
                ->where('id', '!=', auth()->id())
                ->orderBy('created_at', 'desc')
                ->get()
            // ->paginate(5),
        ]);
    }

    public function total_users_metrics(Request $request)
    {
        if (!in_array($request->user()->role_id, [1, 2])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $metricsService = new UserMetricsService();
        return response()->json($metricsService->getTotalUsersWithChange());
    }

    public function conversion_metrics(Request $request)
    {
        if (!in_array($request->user()->role_id, [1, 2])) {
            return response()->json(['message' => 'Forbidden'], 403);
        }

        $metricsService = new UserMetricsService();
        return response()->json($metricsService->getConversionRateWithChange());
    }

    /**
     *Super Admin Functions
     */

    public function show(Request $request, $id)
    {
        if (!in_array($request->user()->role_id, [1, 2])) {
            return response()->json(['message' => 'Forbidden, You are not Authorized'], 403);
        }

        $user = User::with('role', 'preferences')->findOrFail($id);
        return response()->json($user);
    }

    public function update(Request $request, $id)
    {
        if (!in_array($request->user()->role_id, [1, 2])) {
            return response()->json(['message' => 'Forbidden, You are not Authorized'], 403);
        }

        $user = User::findOrFail($id);

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|digits_between:10,12|unique:users', 
            'role_id' => 'required',
            'is_active' => 'required'
        ];
        $validatedData =  $request->validate($rules);
        if (isset($validatedData['role_id'])) {
            $role_id = Role::findByUserType($validatedData['role_id']);
            if (!$role_id) {
                return response()->json(['message' => 'Invalid user type'], 422);
            }
            $user->role_id = $role_id;
        }
        if (isset($validatedData['name'])) {
            $user->name = $validatedData['name'];
        }
        if (isset($validatedData['email'])) {
            $user->email = $validatedData['email'];
        }
        if (isset($validatedData['phone'])) {
            $user->phone = $validatedData['phone'];
        }
        $user->save();        
        return response()->json([
            'message' => 'User updated successfully',
        ]);
    }

    public function store(Request $request)
    {
        if (!in_array($request->user()->role_id, [1, 2])) {
            return response()->json(['message' => 'Forbidden, You are not Authorized'], 403);
        }
        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'phone' => 'required|digits_between:10,12|unique:users', // Ensures only digits and length
            'role_id' => 'required',
            'is_active' => 'required'
        ];
        $validatedData =  $request->validate($rules);
        $role_id = Role::findByUserType($validatedData['role_id']);
        if (!$role_id) {
            return response()->json(['message' => 'Invalid user type'], 422);
        }

        // Create user
        $user = User::create([
            'name' => $validatedData['name'],
            'email' => $validatedData['email'],
            'password' => Hash::make(Str::random(16)),
            'phone' => $validatedData['phone'],
            'role_id' => $role_id,
            'is_active' => $validatedData['is_active'],
        ]);

        $status = Password::sendResetLink(['email' => $user->email]);
        if ($status === Password::RESET_LINK_SENT) {
            event(new Registered($user));
            return response()->json([
                'message' => 'User created successfully. A password reset link has been sent to their email.',
                'user' => $user->load('preferences', 'role')
            ], 201);
        }

        return response()->json([
            'message' => 'User created but failed to send password reset link.'
        ], 500);
    }

    public function userStatusChange(Request $request, $id)
    {

        if (!in_array($request->user()->role_id, [1, 2])) {
            return response()->json(['message' => 'Forbidden, You are not Authorized'], 403);
        }

        $user = User::findOrFail($id);

        $validated = $request->validate([
            'status' => 'required|in:0,1,3,4',
        ]);

        $user->update(['is_active' => $validated['status']]);

        return response()->json([
            'message' => $validated['status'] == 1 ? 'User activated successfully' : 'User deactivated successfully',
            'user' => $user->load('role')
        ]);
    }

    public function deleteUser(Request $request, $id)
    {
        if (!in_array($request->user()->role_id, [1, 2])) {
            return response()->json(['message' => 'Forbidden, You are not Authorized'], 403);
        }

        $user = User::findOrFail($id);

        if ($user->id === $request->user()->id) {
            return response()->json(['message' => 'You cannot delete your own account'], 400);
        }
        $user->delete();

        return response()->json(['message' => 'User deleted successfully']);
    }

    public function viewPAge(){
        return view('view.random_password');
    }
    /**
     *Super Admin Functions End
     */
}
