<?php

namespace App\Services;

use App\Models\User;
use App\Models\Transaction;
use App\Models\WithdrawalRequest;
use Shetabit\Multipay\Invoice;
use Shetabit\Multipay\Exceptions\InvalidPaymentException;

class PaymentService
{
    /**
     * Initiate Bkash payment
     */
    public function initiateBkashPayment(User $user, float $amount)
    {
        $invoice = new Invoice;
        $invoice->amount($amount);
        $invoice->detail([
            'user_id' => $user->id,
            'type' => 'ad_deposit',
        ]);
        
        // Generate payment
        $payment = \Shetabit\Multipay\Payment::via('bkash');
        
        try {
            $paymentUrl = $payment->purchase($invoice, function($driver, $transactionId) use ($user, $amount) {
                // Store transaction ID
                Transaction::create([
                    'user_id' => $user->id,
                    'wallet_id' => $user->advertiserWallet->id,
                    'type' => 'deposit',
                    'amount' => $amount,
                    'balance_after' => $user->advertiserWallet->balance,
                    'reference_id' => $transactionId,
                    'status' => 'pending',
                ]);
            })->pay()->getAction();
            
            return [
                'success' => true,
                'payment_url' => $paymentUrl,
            ];
            
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }
    
    /**
     * Handle Bkash payment callback
     */
    public function handleBkashCallback($request)
    {
        try {
            $payment = \Shetabit\Multipay\Payment::via('bkash');
            $receipt = $payment->verify();
            
            // Find pending transaction
            $transaction = Transaction::where('reference_id', $receipt->getReferenceId())
                ->where('status', 'pending')
                ->first();
            
            if (!$transaction) {
                throw new \Exception('Transaction not found');
            }
            
            // Update transaction status
            $transaction->update(['status' => 'completed']);
            
            // Add balance to wallet
            $wallet = $transaction->wallet;
            $wallet->increment('balance', $transaction->amount);
            $wallet->increment('total_deposited', $transaction->amount);
            
            // Update balance_after
            $transaction->update(['balance_after' => $wallet->balance]);
            
            return [
                'success' => true,
                'transaction' => $transaction,
            ];
            
        } catch (InvalidPaymentException $e) {
            return [
                'success' => false,
                'message' => 'Payment verification failed',
            ];
        }
    }
    
    /**
     * Initiate Nagad payment
     */
    public function initiateNagadPayment(User $user, float $amount)
    {
        // Similar to Bkash implementation
        // ... Nagad specific code
    }
    
    /**
     * Process withdrawal request
     */
    public function processWithdrawal(WithdrawalRequest $withdrawalRequest)
    {
        $user = $withdrawalRequest->user;
        
        // Check if user has sufficient balance
        if ($user->balance < $withdrawalRequest->amount) {
            throw new \Exception('Insufficient balance');
        }
        
        // Calculate fee
        $feePercentage = config('monetization.withdrawal_fee_percentage', 2);
        $fee = ($withdrawalRequest->amount * $feePercentage) / 100;
        $netAmount = $withdrawalRequest->amount - $fee;
        
        // Deduct from user balance
        $user->decrement('balance', $withdrawalRequest->amount);
        
        // Create transaction record
        Transaction::create([
            'user_id' => $user->id,
            'wallet_id' => $user->wallet->id,
            'type' => 'withdrawal',
            'amount' => -$withdrawalRequest->amount,
            'balance_after' => $user->balance,
            'description' => "Withdrawal via {$withdrawalRequest->method} - Fee: {$fee} BDT",
            'reference_id' => $withdrawalRequest->id,
            'reference_type' => WithdrawalRequest::class,
            'status' => 'completed',
        ]);
        
        // Update withdrawal request
        $withdrawalRequest->update([
            'status' => 'processed',
            'processed_at' => now(),
        ]);
        
        // Here you would integrate with actual payment gateway
        // to send money to user's Bkash/Nagad account
        // For now, mark as processed
        
        return [
            'success' => true,
            'net_amount' => $netAmount,
            'fee' => $fee,
        ];
    }
}