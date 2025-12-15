<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SmsLog extends Model
{
    protected $fillable = [
        'phone',
        'message',
        'type',
        'purpose',
        'success',
        'response',
        'error',
        'sent_at',
    ];

    protected $casts = [
        'success' => 'boolean',
        'sent_at' => 'datetime',
    ];

    /**
     * Scope: Successful SMS
     */
    public function scopeSuccessful($query)
    {
        return $query->where('success', true);
    }

    /**
     * Scope: Failed SMS
     */
    public function scopeFailed($query)
    {
        return $query->where('success', false);
    }

    /**
     * Scope: By type
     */
    public function scopeOfType($query, string $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope: Today's SMS
     */
    public function scopeToday($query)
    {
        return $query->whereDate('sent_at', today());
    }
}