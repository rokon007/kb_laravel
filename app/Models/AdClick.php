<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdClick extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'advertisement_id',
        'user_id',
        'ip_address',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // Relations

    /**
     * Click এর advertisement
     */
    public function advertisement()
    {
        return $this->belongsTo(Advertisement::class);
    }

    /**
     * যে user click করেছে
     */
    public function user()
    {
        return $this->belongsTo(User::class);
    }

    // Scopes

    /**
     * Clicks by date
     */
    public function scopeByDate($query, $date)
    {
        return $query->whereDate('created_at', $date);
    }

    /**
     * Today's clicks
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    /**
     * Unique clicks
     */
    public function scopeUnique($query)
    {
        return $query->distinct('user_id');
    }
}