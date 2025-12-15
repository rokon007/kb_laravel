<?php

namespace App\Services;

use App\Models\User;
use App\Models\SocialAccount;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;

class SocialAuthService
{
    /**
     * Redirect to provider
     */
    public function redirectToProvider(string $provider)
    {
        return Socialite::driver($provider)->redirect();
    }

    /**
     * Handle provider callback
     */
    public function handleProviderCallback(string $provider)
    {
        try {
            $socialUser = Socialite::driver($provider)->user();

            // Check if social account exists
            $socialAccount = SocialAccount::where('provider', $provider)
                ->where('provider_id', $socialUser->getId())
                ->first();

            if ($socialAccount) {
                // Update token
                $socialAccount->update([
                    'provider_token' => $socialUser->token,
                    'provider_refresh_token' => $socialUser->refreshToken ?? null,
                ]);

                return $socialAccount->user;
            }

            // Check if user exists with this email
            $user = User::where('email', $socialUser->getEmail())->first();

            if (!$user) {
                // Create new user
                $user = User::create([
                    'name' => $socialUser->getName(),
                    'email' => $socialUser->getEmail(),
                    'username' => $this->generateUniqueUsername($socialUser->getName()),
                    'avatar' => $socialUser->getAvatar(),
                    'email_verified_at' => now(), // Auto verify for social login
                    'password' => bcrypt(Str::random(32)), // Random password
                ]);
            }

            // Create social account
            SocialAccount::create([
                'user_id' => $user->id,
                'provider' => $provider,
                'provider_id' => $socialUser->getId(),
                'provider_token' => $socialUser->token,
                'provider_refresh_token' => $socialUser->refreshToken ?? null,
            ]);

            return $user;

        } catch (\Exception $e) {
            \Log::error("Social Auth Error [{$provider}]: " . $e->getMessage());
            throw new \Exception('Unable to authenticate with ' . ucfirst($provider));
        }
    }

    /**
     * Generate unique username
     */
    protected function generateUniqueUsername(string $name): string
    {
        $username = Str::slug($name);
        $originalUsername = $username;
        $counter = 1;

        while (User::where('username', $username)->exists()) {
            $username = $originalUsername . $counter;
            $counter++;
        }

        return $username;
    }
}