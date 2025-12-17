<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Advertisement;
use App\Services\AdService;
use Illuminate\Http\Request;

class AdvertisementController extends Controller
{
    protected $adService;

    public function __construct(AdService $adService)
    {
        $this->adService = $adService;
    }

    /**
     * Get user's advertisements
     */
    public function index(Request $request)
    {
        $ads = $request->user()
            ->advertisements()
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $ads,
        ]);
    }

    /**
     * Create new advertisement
     */
    public function store(Request $request)
    {
        $request->validate([
            'title' => 'required|string|max:255',
            'description' => 'nullable|string|max:500',
            'media_type' => 'required|in:image,video',
            'media_url' => 'required|string',
            'click_url' => 'required|url',
            'placement_type' => 'required|in:feed,reel,video_preroll,video_midroll,sponsored',
            'budget' => 'required|numeric|min:100',
            'target_age_min' => 'nullable|integer|min:13',
            'target_age_max' => 'nullable|integer|max:100',
            'target_gender' => 'nullable|in:all,male,female,other',
            'start_date' => 'nullable|date',
            'end_date' => 'nullable|date|after:start_date',
        ]);

        // Check if user has sufficient balance
        $wallet = $request->user()->advertiserWallet;
        if (!$wallet || $wallet->balance < $request->budget) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance in advertiser wallet',
            ], 422);
        }

        $ad = Advertisement::create([
            'user_id' => $request->user()->id,
            'title' => $request->title,
            'description' => $request->description,
            'media_type' => $request->media_type,
            'media_url' => $request->media_url,
            'click_url' => $request->click_url,
            'placement_type' => $request->placement_type,
            'budget' => $request->budget,
            'target_age_min' => $request->target_age_min,
            'target_age_max' => $request->target_age_max,
            'target_gender' => $request->target_gender ?? 'all',
            'start_date' => $request->start_date,
            'end_date' => $request->end_date,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Advertisement created and pending approval',
            'data' => $ad,
        ], 201);
    }

    /**
     * Get single advertisement
     */
    public function show(Advertisement $advertisement)
    {
        // Check ownership
        if ($advertisement->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        return response()->json([
            'success' => true,
            'data' => $advertisement,
        ]);
    }

    /**
     * Update advertisement
     */
    public function update(Request $request, Advertisement $advertisement)
    {
        // Check ownership
        if ($advertisement->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        // Can only edit pending ads
        if ($advertisement->status !== 'pending') {
            return response()->json([
                'success' => false,
                'message' => 'Can only edit pending advertisements',
            ], 422);
        }

        $request->validate([
            'title' => 'sometimes|string|max:255',
            'description' => 'nullable|string|max:500',
            'budget' => 'sometimes|numeric|min:100',
        ]);

        $advertisement->update($request->only(['title', 'description', 'budget']));

        return response()->json([
            'success' => true,
            'message' => 'Advertisement updated successfully',
            'data' => $advertisement,
        ]);
    }

    /**
     * Delete advertisement
     */
    public function destroy(Request $request, Advertisement $advertisement)
    {
        // Check ownership
        if ($advertisement->user_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $advertisement->delete();

        return response()->json([
            'success' => true,
            'message' => 'Advertisement deleted successfully',
        ]);
    }

    /**
     * Record ad impression
     */
    public function recordImpression(Request $request, Advertisement $advertisement)
    {
        $this->adService->recordImpression(
            $advertisement,
            $request->user(),
            $request->ip()
        );

        return response()->json([
            'success' => true,
            'message' => 'Impression recorded',
        ]);
    }

    
    public function recordClick(Request $request, Advertisement $advertisement)
    {
        $this->adService->recordClick(
            $advertisement,
            $request->user()
        );

        return response()->json([
            'success' => true,
            'message' => 'Click recorded',
        ]);
    }

    /**
     * Get ad analytics
     */
    public function analytics(Advertisement $advertisement)
    {
        // Check ownership
        if ($advertisement->user_id !== auth()->id()) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        $analytics = $this->adService->getAdAnalytics($advertisement);

        return response()->json([
            'success' => true,
            'data' => $analytics,
        ]);
    }
}