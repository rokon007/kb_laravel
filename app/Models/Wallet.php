<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Wallet extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'type',
        'balance',
        'total_deposited',
        'total_withdrawn',
    ];

    protected $casts = [
        'balance' => 'decimal:2',
        'total_deposited' => 'decimal:2',
        'total_withdrawn' => 'decimal:2',
    ];

    // Relations

    /**
     * Wallet এর owner
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Wallet এর transactions
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    // Scopes

    /**
     * User wallets
     */
    public function scopeUserWallet($query)
    {
        return $query->where('type', 'user');
    }

    /**
     * Advertiser wallets
     */
    public function scopeAdvertiserWallet($query)
    {
        return $query->where('type', 'advertiser');
    }

    // Helper Methods

    /**
     * Add balance
     */
    public function addBalance(float $amount)
    {
        $this->increment('balance', $amount);
        $this->increment('total_deposited', $amount);
    }

    /**
     * Deduct balance
     */
    public function deductBalance(float $amount)
    {
        if ($this->balance < $amount) {
            throw new \Exception('Insufficient balance');
        }

        $this->decrement('balance', $amount);
    }

    /**
     * Withdraw balance
     */
    public function withdraw(float $amount)
    {
        $this->deductBalance($amount);
        $this->increment('total_withdrawn', $amount);
    }

    /**
     * Check if wallet has sufficient balance
     */
    public function hasSufficientBalance(float $amount): bool
    {
        return $this->balance >= $amount;
    }
}