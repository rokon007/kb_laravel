<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdImpression extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'advertisement_id',
        'user_id',
        'ip_address',
        'user_agent',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // Relations

    /**
     * Impression এর advertisement
     */
    public function advertisement()
    {
        return $this->belongsTo(Advertisement::class);
    }

    /**
     * Impression দেখেছে যে user
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes

    /**
     * Impressions by date
     */
    public function scopeByDate($query, $date)
    {
        return $query->whereDate('created_at', $date);
    }

    /**
     * Today's impressions
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Unique impressions
     */
    public function scopeUnique($query)
    {
        return $query->distinct('user_id');
    }
}