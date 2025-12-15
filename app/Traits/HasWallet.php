<?php

namespace App\Traits;

use App\Models\Wallet;
use App\Models\Transaction;

trait HasWallet
{
    /**
     * Get or create user wallet
     */
    public function getOrCreateWallet(string $type = 'user'): Wallet
    {
        return $this->wallets()->firstOrCreate(
            ['type' => $type],
            ['balance' => 0]
        );
    }

    /**
     * Add balance to wallet
     */
    public function addBalance(float $amount, string $type = 'user', string $description = null)
    {
        $wallet = $this->getOrCreateWallet($type);
        $wallet->addBalance($amount);

        // Create transaction
        return Transaction::create([
            'user_id' => $this->id,
            'wallet_id' => $wallet->id,
            'type' => 'deposit',
            'amount' => $amount,
            'balance_after' => $wallet->balance,
            'description' => $description ?? 'Balance added',
            'status' => 'completed',
        ]);
    }

    /**
     * Deduct balance from wallet
     */
    public function deductBalance(float $amount, string $type = 'user', string $description = null)
    {
        $wallet = $this->getOrCreateWallet($type);
        $wallet->deductBalance($amount);

        return Transaction::create([
            'user_id' => $this->id,
            'wallet_id' => $wallet->id,
            'type' => 'withdrawal',
            'amount' => -$amount,
            'balance_after' => $wallet->balance,
            'description' => $description ?? 'Balance deducted',
            'status' => 'completed',
        ]);
    }

    /**
     * Check if has sufficient balance
     */
    public function hasSufficientBalance(float $amount, string $type = 'user'): bool
    {
        $wallet = $this->getOrCreateWallet($type);
        return $wallet->hasSufficientBalance($amount);
    }
}