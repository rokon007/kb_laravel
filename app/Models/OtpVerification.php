<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class OtpVerification extends Model
{
    use HasFactory;

    protected $fillable = [
        'identifier',
        'otp',
        'type',
        'purpose',
        'is_verified',
        'expires_at',
        'verified_at',
        'attempts',
    ];

    protected $casts = [
        'is_verified' => 'boolean',
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    /**
     * Check if OTP is expired
     */
    public function isExpired(): bool
    {
        return $this->expires_at->isPast();
    }

    /**
     * Check if OTP is valid
     */
    public function isValid(): bool
    {
        return !$this->is_verified && !$this->isExpired();
    }

    /**
     * Verify OTP
     */
    public function verify(): bool
    {
        if ($this->isValid()) {
            $this->update([
                'is_verified' => true,
                'verified_at' => now(),
            ]);
            return true;
        }
        return false;
    }

    /**
     * Increment attempts
     */
    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }

    /**
     * Scope: Not verified
     */
    public function scopeNotVerified($query)
    {
        return $query->where('is_verified', false);
    }

    /**
     * Scope: Not expired
     */
    public function scopeNotExpired($query)
    {
        return $query->where('expires_at', '>', now());
    }

    /**
     * Scope: Valid OTPs
     */
    public function scopeValid($query)
    {
        return $query->notVerified()->notExpired();
    }
}