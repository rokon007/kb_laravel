<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class WithdrawalRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'amount',
        'method',
        'account_number',
        'account_name',
        'status',
        'admin_note',
        'processed_at',
        'processed_by',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    // Relations

    /**
     * Withdrawal request এর user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * যে admin process করেছে
     */
    public function processedBy()
    {
        return $this->belongsTo(User::class, 'processed_by');
    }

    // Scopes

    /**
     * Pending requests
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Approved requests
     */
    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    /**
     * Rejected requests
     */
    public function scopeRejected($query)
    {
        return $query->where('status', 'rejected');
    }

    /**
     * Processed requests
     */
    public function scopeProcessed($query)
    {
        return $query->where('status', 'processed');
    }

    /**
     * Requests by method
     */
    public function scopeByMethod($query, string $method)
    {
        return $query->where('method', $method);
    }

    // Helper Methods

    /**
     * Approve withdrawal request
     */
    public function approve()
    {
        $this->update([
            'status' => 'approved',
            'processed_by' => auth()->id(),
        ]);
    }

    /**
     * Reject withdrawal request
     */
    public function reject(string $reason)
    {
        // Refund amount to user
        $this->user->increment('balance', $this->amount);

        $this->update([
            'status' => 'rejected',
            'admin_note' => $reason,
            'processed_by' => auth()->id(),
        ]);
    }

    /**
     * Mark as processed
     */
    public function markAsProcessed()
    {
        $this->update([
            'status' => 'processed',
            'processed_at' => now(),
        ]);
    }
}