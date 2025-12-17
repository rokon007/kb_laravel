<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\WithdrawalRequest;
use Illuminate\Http\Request;

class WalletController extends Controller
{
    /**
     * Get wallet details
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $wallet = $user->wallet;
        $advertiserWallet = $user->advertiserWallet;

        return response()->json([
            'success' => true,
            'data' => [
                'user_wallet' => $wallet,
                'advertiser_wallet' => $advertiserWallet,
            ],
        ]);
    }

    /**
     * Get transactions
     */
    public function transactions(Request $request)
    {
        $transactions = $request->user()
            ->transactions()
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $transactions,
        ]);
    }

    /**
     * Request withdrawal
     */
    public function requestWithdrawal(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:' . config('monetization.min_withdrawal', 500),
            'method' => 'required|in:bkash,nagad,bank',
            'account_number' => 'required|string',
            'account_name' => 'required|string',
        ]);

        $user = $request->user();

        // Check balance
        if ($user->balance < $request->amount) {
            return response()->json([
                'success' => false,
                'message' => 'Insufficient balance',
            ], 422);
        }

        // Check pending requests
        $pendingRequests = WithdrawalRequest::where('user_id', $user->id)
            ->where('status', 'pending')
            ->count();

        if ($pendingRequests > 0) {
            return response()->json([
                'success' => false,
                'message' => 'You have pending withdrawal requests',
            ], 422);
        }

        // Deduct balance immediately
        $user->decrement('balance', $request->amount);

        $withdrawal = WithdrawalRequest::create([
            'user_id' => $user->id,
            'amount' => $request->amount,
            'method' => $request->method,
            'account_number' => $request->account_number,
            'account_name' => $request->account_name,
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Withdrawal request submitted successfully',
            'data' => $withdrawal,
        ], 201);
    }

    /**
     * Get withdrawal requests
     */
    public function withdrawalRequests(Request $request)
    {
        $requests = $request->user()
            ->withdrawalRequests()
            ->latest()
            ->paginate(20);

        return response()->json([
            'success' => true,
            'data' => $requests,
        ]);
    }
}