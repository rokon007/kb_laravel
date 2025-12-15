<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AdminActivityLog extends Model
{
    use HasFactory;

    public $timestamps = false;

    protected $fillable = [
        'admin_id',
        'action',
        'description',
        'model_type',
        'model_id',
        'ip_address',
    ];

    protected $casts = [
        'created_at' => 'datetime',
    ];

    // Relations

    /**
     * যে admin action নিয়েছে
     */
    public function admin()
    {
        return $this->belongsTo(User::class, 'admin_id');
    }

    // Scopes

    /**
     * Logs by admin
     */
    public function scopeByAdmin($query, int $adminId)
    {
        return $query->where('admin_id', $adminId);
    }

    /**
     * Logs by action
     */
    public function scopeByAction($query, string $action)
    {
        return $query->where('action', $action);
    }

    /**
     * Recent logs
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Today's logs
     */
    public function scopeToday($query)
    {
        return $query->whereDate('created_at', today());
    }

    // Helper Methods

    /**
     * Log admin activity
     */
    public static function logActivity(
        string $action,
        string $description,
        string $modelType = null,
        int $modelId = null
    ) {
        return self::create([
            'admin_id' => auth()->id(),
            'action' => $action,
            'description' => $description,
            'model_type' => $modelType,
            'model_id' => $modelId,
            'ip_address' => request()->ip(),
        ]);
    }
}