<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Auth;
use App\Http\Controllers\Common_setup;
use App\Http\Controllers\Property_controller;
use App\Http\Controllers\User_controller;
use App\Models\Property;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;

Route::post('/auth/register', [Auth::class, 'register']);
Route::post('/auth/login', [Auth::class, 'login']);
Route::middleware('auth:sanctum')->group(function () {
    Route::post('/auth/logout', [Auth::class, 'logout']);
    Route::get('/auth/user', [Auth::class, 'user']);
    Route::get('/auth/user_metrics', [Auth::class, 'user_metrics']);
});

Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', [User_controller::class, 'get_user_profile_details']);
    Route::post('/property/add', [Property_controller::class, 'add_property']);
    Route::post('/properties/get-user-properties', [Property_controller::class, 'get_user_properties']);
    Route::get('/properties/get-favorite-properties', [User_controller::class, 'getUserFavorites']);
    Route::post('/user/profile', [User_controller::class, 'update_user_profile_details']);
    Route::put('properties/{id}', [Property_controller::class, 'update_property']);
    Route::delete('properties/{id}', [Property_controller::class, 'delete_property']);
    Route::post('/properties/toggle-favorite', [Common_setup::class, 'toggleFavorite']);
});

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

Route::middleware(['auth:sanctum', 'admin'])->prefix('admin')->group(function () {
    // Get Properties Details
    Route::get('/properties', [Property_controller::class, 'get_s_admin_property_details']);
});

Route::post('/forgot-password', function (Request $request) {
    $request->validate(['email' => 'required|email']);

    $status = Password::sendResetLink(
        $request->only('email')
    );

    return $status === Password::RESET_LINK_SENT
        ? response()->json(['message' => __($status)], 200)
        : response()->json(['message' => __($status)], 400);
});

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

Route::get('/properties/{id}', [Property_controller::class, 'get_property_details']);
Route::get('/properties', [Property_controller::class, 'get_all_properties']);
