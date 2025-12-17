<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Services\PaymentService;
use Illuminate\Http\Request;

class PaymentController extends Controller
{
    protected $paymentService;

    public function __construct(PaymentService $paymentService)
    {
        $this->paymentService = $paymentService;
    }

    /**
     * Initiate bKash payment
     */
    public function initiateBkash(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10',
        ]);

        $result = $this->paymentService->initiateBkashPayment(
            $request->user(),
            $request->amount
        );

        return response()->json($result);
    }

    /**
     * bKash payment callback
     */
    public function bkashCallback(Request $request)
    {
        $result = $this->paymentService->handleBkashCallback($request);

        if ($result['success']) {
            return redirect()->to('/payment/success?transaction_id=' . $result['transaction']->id);
        }

        return redirect()->to('/payment/failed');
    }

    /**
     * Initiate Nagad payment
     */
    public function initiateNagad(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:10',
        ]);

        $result = $this->paymentService->initiateNagadPayment(
            $request->user(),
            $request->amount
        );

        return response()->json($result);
    }

    /**
     * Nagad payment callback
     */
    public function nagadCallback(Request $request)
    {
        // Similar to bKash callback
        return response()->json([
            'success' => true,
            'message' => 'Payment callback received',
        ]);
    }
}