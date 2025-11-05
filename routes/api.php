<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth;
use App\Http\Controllers\Common_setup;
use App\Http\Controllers\Property_controller;
use App\Http\Controllers\User_controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

/** Authentication Routes */
Route::post('/auth/register', [Auth::class, 'register']);
Route::post('/auth/login', [Auth::class, 'login']);
/** Authentication Routes */

/** User Handle Auth Routes */
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [Auth::class, 'logout']);
    Route::get('/auth/user', [Auth::class, 'user']);
    Route::get('/auth/user_metrics', [Auth::class, 'user_metrics']);
});
/** User Handle Auth Routes */

/** Login User Routes */
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [User_controller::class, 'get_user_profile_details']);
    Route::post('/property/add', [Property_controller::class, 'add_property']);
    Route::post('/properties/get-user-properties', [Property_controller::class, 'get_user_properties']);
    Route::get('/properties/get-favorite-properties', [User_controller::class, 'getUserFavorites']);
    Route::post('/user/profile', [User_controller::class, 'update_user_profile_details']);
    Route::put('properties/{id}', [Property_controller::class, 'update_property']);
    Route::delete('properties/{id}', [Property_controller::class, 'delete_property']);
    Route::post('/properties/toggle-favorite', [Common_setup::class, 'toggleFavorite']);
    Route::get('/propertiesList', [Property_controller::class, 'get_property_list_by_userID']);
    Route::get('/seller/dashboard', [Property_controller::class, 'getDashboardData']);
    Route::post('/user/store-inquiry', [Common_setup::class, 'store_buyer_inquiry']);
    Route::get('/inquiries', [Common_setup::class, 'list_inquiries_by_user']);
    Route::get('/inquiries/{inquiryId}', [Common_setup::class, 'getInquiry']);
    Route::post('/inquiries/{inquiryId}/respond', [Common_setup::class, 'addResponse']);
    Route::post('/inquiries/{inquiryId}/mark-read', [Common_setup::class, 'markAsRead']);
    Route::post('/store-schedule', [User_controller::class, 'store_schedule']);
    Route::get('/get-schedule', [User_controller::class, 'get_schedule']);
    Route::put('/update-schedule-status', [User_controller::class, 'update_schedule_status']);
});
/** Login User Routes */

/** Super Admin Routes */
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {

    // User Management Routes
    Route::prefix('users')->group(function () {
        // Get all users with pagination/filters
        Route::get('/', [Auth::class, 'index']);

        // Get specific user by ID
        Route::get('/{id}', [Auth::class, 'show']);

        // Create new user
        Route::post('/', [Auth::class, 'store']);

        // Update user
        Route::put('/{id}', [Auth::class, 'update']);

        // Delete user
        Route::delete('/{id}', [Auth::class, 'deleteUser']);

        // Change user status (activate/deactivate)
        Route::patch('/{id}/status', [Auth::class, 'userStatusChange']);
    });

});
/** Super Admin Routes */

/**  Admin Routes */
Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    Route::get('/properties', [Property_controller::class, 'get_s_admin_property_details']);
});
/**  Admin Routes */

/* Forget Password Routes */
Route::post('/forgot-password', function (Request $request) {
    $request->validate(['email' => 'required|email']);

    $status = Password::sendResetLink(
        $request->only('email')
    );

    return $status === Password::RESET_LINK_SENT
        ? response()->json(['message' => __($status)], 200)
        : response()->json(['message' => __($status)], 400);
});
/* Forget Password Routes */

/* Reset Password Routes */
Route::post('/reset-password', function (Request $request) {
    $request->validate([
        'token' => 'required',
        'email' => 'required|email',
        'password' => 'required|min:8|confirmed',
    ]);

    $status = Password::reset(
        $request->only('email', 'password', 'password_confirmation', 'token'),
        function ($user, $password) {
            $user->forceFill([
                'password' => Hash::make($password)
            ])->save();
        }
    );

    return $status === Password::PASSWORD_RESET
        ? response()->json(['message' => 'Password reset successfully'], 200)
        : response()->json(['message' => __($status)], 400);
})->name('password.update');
/* Reset Password Routes */

/* General Property details api routes */
Route::post('/properties/{id}', [Property_controller::class, 'trackView']);
Route::get('/properties/{id}', [Property_controller::class, 'get_property_details']);
Route::get('/properties', [Property_controller::class, 'get_all_properties']);
/* General Property details api routes */


/**  Agent Route */
Route::middleware(['auth:sanctum', 'agent'])->prefix('agent')->group(function () {    
    Route::get('/pipeline', [Common_setup::class, 'get_agent_pipeline_data']);
    Route::get('/deal-losses', [Common_setup::class, 'get_agent_deal_losses']); 
    Route::post('/deal-losses', [Common_setup::class, 'store_agent_deal_loss']);
    Route::get('/buyers', [Common_setup::class, 'getAgentBuyers']);
});
/**  Agent Route */