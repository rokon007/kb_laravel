<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'wallet_id',
        'type',
        'amount',
        'balance_after',
        'description',
        'reference_id',
        'reference_type',
        'status',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'balance_after' => 'decimal:2',
    ];

    // Relations

    /**
     * Transaction এর user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Transaction এর wallet
     */
    public function wallet()
    {
        return $this->belongsTo(Wallet::class);
    }

    /**
     * Transaction এর reference (polymorphic)
     */
    public function reference()
    {
        return $this->morphTo();
    }

    // Scopes

    /**
     * Completed transactions
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Pending transactions
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Transactions by type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Deposits
     */
    public function scopeDeposits($query)
    {
        return $query->where('type', 'deposit');
    }

    /**
     * Withdrawals
     */
    public function scopeWithdrawals($query)
    {
        return $query->where('type', 'withdrawal');
    }

    /**
     * Earnings
     */
    public function scopeEarnings($query)
    {
        return $query->where('type', 'earning');
    }
}