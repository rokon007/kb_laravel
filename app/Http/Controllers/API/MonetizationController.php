<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\MonetizationService;
use Illuminate\Http\Request;

class MonetizationController extends Controller
{
    protected $monetizationService;

    public function __construct(MonetizationService $monetizationService)
    {
        $this->monetizationService = $monetizationService;
    }

    /**
     * Check creator eligibility
     */
    public function checkEligibility(Request $request)
    {
        $user = $request->user();
        $isEligible = $this->monetizationService->isCreatorEligible($user);

        $config = config('monetization.eligibility');

        return response()->json([
            'success' => true,
            'data' => [
                'is_eligible' => $isEligible,
                'is_creator' => $user->is_creator,
                'is_approved' => $user->creator_approved_at !== null,
                'requirements' => [
                    'min_followers' => $config['min_followers'],
                    'min_posts' => $config['min_posts'],
                    'min_total_views' => $config['min_total_views'],
                    'account_age_days' => $config['account_age_days'],
                ],
                'current_stats' => [
                    'followers' => $user->followers_count,
                    'posts' => $user->posts()->count(),
                    'total_views' => $user->posts()->sum('views_count'),
                    'account_age_days' => $user->created_at->diffInDays(now()),
                ],
            ],
        ]);
    }

    /**
     * Apply for creator program
     */
    public function apply(Request $request)
    {
        $user = $request->user();

        if ($user->is_creator) {
            return response()->json([
                'success' => false,
                'message' => 'Already applied for creator program',
            ], 422);
        }

        try {
            $this->monetizationService->applyForCreatorProgram($user);

            return response()->json([
                'success' => true,
                'message' => 'Creator application submitted successfully',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        }
    }

    /**
     * Get creator earnings
     */
    public function earnings(Request $request)
    {
        $user = $request->user();

        if (!$user->canMonetize()) {
            return response()->json([
                'success' => false,
                'message' => 'Not eligible for monetization',
            ], 403);
        }

        $startDate = $request->get('start_date');
        $endDate = $request->get('end_date');

        $summary = $this->monetizationService->getCreatorEarningSummary(
            $user,
            $startDate,
            $endDate
        );

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Get earning summary
     */
    public function summary(Request $request)
    {
        $user = $request->user();

        if (!$user->canMonetize()) {
            return response()->json([
                'success' => false,
                'message' => 'Not eligible for monetization',
            ], 403);
        }

        $summary = [
            'total_earned' => $user->total_earned,
            'current_balance' => $user->balance,
            'this_month' => $this->monetizationService->getCreatorEarningSummary(
                $user,
                now()->startOfMonth(),
                now()
            ),
            'last_month' => $this->monetizationService->getCreatorEarningSummary(
                $user,
                now()->subMonth()->startOfMonth(),
                now()->subMonth()->endOfMonth()
            ),
        ];

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }
}