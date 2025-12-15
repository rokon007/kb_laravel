<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Report extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'reportable_id',
        'reportable_type',
        'reason',
        'description',
        'status',
        'admin_note',
        'reviewed_by',
        'reviewed_at',
    ];

    protected $casts = [
        'reviewed_at' => 'datetime',
    ];

    // Relations

    /**
     * যে user report করেছে
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    /**
     * যে item report করা হয়েছে (polymorphic)
     */
    public function reportable()
    {
        return $this->morphTo();
    }

    /**
     * যে admin review করেছে
     */
    public function reviewer()
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    // Scopes

    /**
     * Pending reports
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Reviewed reports
     */
    public function scopeReviewed($query)
    {
        return $query->where('status', 'reviewed');
    }

    /**
     * Reports with action taken
     */
    public function scopeActionTaken($query)
    {
        return $query->where('status', 'action_taken');
    }

    /**
     * Dismissed reports
     */
    public function scopeDismissed($query)
    {
        return $query->where('status', 'dismissed');
    }

    /**
     * Reports by type
     */
    public function scopeForType($query, string $type)
    {
        return $query->where('reportable_type', $type);
    }

    // Helper Methods

    /**
     * Mark as reviewed
     */
    public function markAsReviewed(string $note = null)
    {
        $this->update([
            'status' => 'reviewed',
            'admin_note' => $note,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Take action
     */
    public function takeAction(string $note = null)
    {
        $this->update([
            'status' => 'action_taken',
            'admin_note' => $note,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);
    }

    /**
     * Dismiss report
     */
    public function dismiss(string $note = null)
    {
        $this->update([
            'status' => 'dismissed',
            'admin_note' => $note,
            'reviewed_by' => auth()->id(),
            'reviewed_at' => now(),
        ]);
    }
}