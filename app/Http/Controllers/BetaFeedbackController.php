<?php

namespace App\Http\Controllers;

use App\Models\BetaFeedback;
use App\Services\CloudinaryService;
use Illuminate\Http\Request;

class BetaFeedbackController extends Controller
{
    protected CloudinaryService $cloudinaryService;

    public function __construct(CloudinaryService $cloudinaryService)
    {
        $this->cloudinaryService = $cloudinaryService;
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'sometimes|nullable|string|max:255',
            'email' => 'sometimes|nullable|email|max:255',
            'environment' => 'required|string|in:production,staging,local,mobile,other',
            'page_url' => 'sometimes|nullable|string|max:500',
            'issue_link' => 'sometimes|nullable|string|max:500',
            'message' => 'required|string|max:5000',
            'screenshot' => 'sometimes|nullable|image|max:5120',
        ]);

        if ($request->hasFile('screenshot')) {
            $uploadedScreenshot = $this->cloudinaryService->uploadFeedbackScreenshot($request->file('screenshot'));
            $validated['screenshot_url'] = $uploadedScreenshot['secure_url'];
            $validated['screenshot_public_id'] = $uploadedScreenshot['public_id'];
        }

        unset($validated['screenshot']);

        $feedback = BetaFeedback::create([
            ...$validated,
            'user_id' => $request->user()?->id,
            'user_agent' => $request->userAgent(),
            'ip_address' => $request->ip(),
        ]);

        return response()->json([
            'message' => 'Thanks for the feedback. Our team will review it.',
            'feedback' => $feedback,
            'success' => true,
        ], 201);
    }

    public function index(Request $request)
    {
        if (!in_array($request->user()->role_id, [1, 2])) {
            return response()->json(['message' => 'Forbidden, You are not Authorized'], 403);
        }

        $feedback = BetaFeedback::with('user:id,name,email')
            ->when($request->filled('status') && $request->status !== 'all', function ($query) use ($request) {
                $query->where('status', $request->status);
            })
            ->when($request->filled('environment') && $request->environment !== 'all', function ($query) use ($request) {
                $query->where('environment', $request->environment);
            })
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 20));

        return response()->json($feedback);
    }

    public function updateStatus(Request $request, BetaFeedback $feedback)
    {
        if (!in_array($request->user()->role_id, [1, 2])) {
            return response()->json(['message' => 'Forbidden, You are not Authorized'], 403);
        }

        $validated = $request->validate([
            'status' => 'required|string|in:new,reviewing,fixed,closed',
        ]);

        $feedback->update($validated);

        return response()->json([
            'message' => 'Feedback status updated successfully',
            'feedback' => $feedback->fresh('user:id,name,email'),
        ]);
    }
}
