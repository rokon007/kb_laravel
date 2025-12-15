<?php

namespace App\Models;

// Laravel 12 এর ডিফল্ট imports 
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

// নতুন imports 
use Laravel\Sanctum\HasApiTokens;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use App\Traits\Likeable;
use App\Traits\Followable;
use App\Traits\HasWallet;

class User extends Authenticatable implements HasMedia, MustVerifyEmail
{
    /** @use HasFactory<\Database\Factories\UserFactory> */
    use HasFactory, Notifiable;
    
     //নতুন traits 
    use HasApiTokens;
    use InteractsWithMedia;
    use Likeable;
    use Followable;
    use HasWallet;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
       // Laravel 12 এর ডিফল্ট
        'name',
        'email',
        'password',
        
        // নতুন fields
        'username',
        'phone',
        'bio',
        'avatar',
        'cover_photo',
        'date_of_birth',
        'gender',
        'location',
        'is_verified',
        'is_creator',
        'creator_approved_at',
        'is_banned',
        'banned_reason',
        'balance',
        'total_earned',
        'followers_count',
        'following_count',
        'friends_count',
        'privacy_settings',
    ];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            // ✅ Laravel 12 এর ডিফল্ট
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            
            // ✅ আপনার নতুন casts
            'creator_approved_at' => 'datetime',
            'date_of_birth' => 'date',
            'is_verified' => 'boolean',
            'is_creator' => 'boolean',
            'is_banned' => 'boolean',
            'balance' => 'decimal:2',
            'total_earned' => 'decimal:2',
            'privacy_settings' => 'array',
        ];
    }

    // ✅ এখানে সব relationships যোগ করুন (আগের response থেকে)
    
    /**
     * User এর সব posts
     */
    public function posts()
    {
        return $this->hasMany(Post::class);
    }

    /**
     * User এর তৈরি pages
     */
    public function pages()
    {
        return $this->hasMany(Page::class);
    }

    /**
     * User এর comments
     */
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }

    /**
     * User কি কি like করেছে
     */
    public function likes()
    {
        return $this->hasMany(Like::class);
    }

    /**
     * User যাদের follow করেছে
     */
    public function following()
    {
        return $this->hasMany(Follow::class, 'follower_id');
    }

    /**
     * User কে কারা follow করেছে
     */
    public function followers()
    {
        return $this->morphMany(Follow::class, 'followable');
    }

    /**
     * User এর sent friend requests
     */
    public function sentFriendRequests()
    {
        return $this->hasMany(Friendship::class, 'user_id')
            ->where('status', 'pending');
    }

    /**
     * User এর received friend requests
     */
    public function receivedFriendRequests()
    {
        return $this->hasMany(Friendship::class, 'friend_id')
            ->where('status', 'pending');
    }

    /**
     * User এর friends
     */
    public function friends()
    {
        return $this->belongsToMany(User::class, 'friendships', 'user_id', 'friend_id')
            ->wherePivot('status', 'accepted')
            ->withPivot('status', 'created_at');
    }

    /**
     * User এর wallet
     */
    public function wallet()
    {
        return $this->hasOne(Wallet::class)->where('type', 'user');
    }

    /**
     * User এর advertiser wallet
     */
    public function advertiserWallet()
    {
        return $this->hasOne(Wallet::class)->where('type', 'advertiser');
    }

    /**
     * User এর transactions
     */
    public function transactions()
    {
        return $this->hasMany(Transaction::class);
    }

    /**
     * User এর withdrawal requests
     */
    public function withdrawalRequests()
    {
        return $this->hasMany(WithdrawalRequest::class);
    }

    /**
     * User এর creator earnings
     */
    public function earnings()
    {
        return $this->hasMany(CreatorEarning::class);
    }

    /**
     * User এর advertisements
     */
    public function advertisements()
    {
        return $this->hasMany(Advertisement::class);
    }

    /**
     * User এর notifications
     */
    public function notifications()
    {
        return $this->hasMany(Notification::class);
    }

    /**
     * User এর reports
     */
    public function reports()
    {
        return $this->hasMany(Report::class);
    }

    // ✅ Helper Methods যোগ করুন

    /**
     * Check if user is friend with another user
     */
    public function isFriendWith(User $user): bool
    {
        return $this->friends()->where('friend_id', $user->id)->exists() ||
               $user->friends()->where('friend_id', $this->id)->exists();
    }

    /**
     * Check if user is following another user/page
     */
    public function isFollowing($followable): bool
    {
        return $this->following()
            ->where('followable_type', get_class($followable))
            ->where('followable_id', $followable->id)
            ->exists();
    }

    /**
     * Check if user has liked a post/comment
     */
    public function hasLiked($likeable): bool
    {
        return $this->likes()
            ->where('likeable_type', get_class($likeable))
            ->where('likeable_id', $likeable->id)
            ->exists();
    }

    /**
     * Get total unread notifications
     */
    public function unreadNotificationsCount(): int
    {
        return $this->notifications()->where('is_read', false)->count();
    }

    /**
     * Check if user can monetize
     */
    public function canMonetize(): bool
    {
        return $this->is_creator && $this->creator_approved_at !== null;
    }

    /**
     * User's social accounts
     */
    public function socialAccounts()
    {
        return $this->hasMany(SocialAccount::class);
    }

    /**
     * Check if user has social account
     */
    public function hasSocialAccount(string $provider): bool
    {
        return $this->socialAccounts()->where('provider', $provider)->exists();
    }
}