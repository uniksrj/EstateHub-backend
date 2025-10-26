<?php

use App\Models\Inquiry;
use Illuminate\Support\Facades\Broadcast;

Broadcast::routes([
    'middleware' => ['auth:sanctum'] 
]);
Broadcast::channel('inquiry.{inquiryId}', function ($user, $inquiryId) {  
    $inquiry = Inquiry::with('property')->find($inquiryId);

    // If No inquiry find
    if (! $inquiry) {
        return false;
    }

    // Allow the buyer who made the inquiry
    if ($inquiry->user_id === $user->id) {
        return true;
    }

    // Allow the seller (agent) who owns the property
    if ($inquiry->property && $inquiry->property->agent_id === $user->id) {
        return true;
    }

    return false;
});

