<?php

namespace App\Services;

use App\Models\OtpVerification;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    protected $smsService;

    public function __construct(SmsService $smsService)
    {
        $this->smsService = $smsService;
    }

    /**
     * Generate OTP
     */
    public function generateOtp(): string
    {
        return str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);
    }

    /**
     * Create OTP record
     */
    public function createOtp(
        string $identifier,
        string $type,
        string $purpose = 'registration'
    ): OtpVerification {
        // Delete previous OTPs for this identifier
        OtpVerification::where('identifier', $identifier)
            ->where('type', $type)
            ->where('purpose', $purpose)
            ->delete();

        // Generate new OTP
        $otp = $this->generateOtp();

        // Create OTP record
        return OtpVerification::create([
            'identifier' => $identifier,
            'otp' => $otp,
            'type' => $type,
            'purpose' => $purpose,
            'expires_at' => now()->addMinutes(10), // 10 minutes validity
        ]);
    }

    /**
     * Send OTP via Email
     */
    public function sendEmailOtp(string $email, string $purpose = 'registration'): bool
    {
        try {
            $otpRecord = $this->createOtp($email, 'email', $purpose);

            Mail::send('emails.otp', ['otp' => $otpRecord->otp, 'purpose' => $purpose], function ($message) use ($email) {
                $message->to($email)
                    ->subject('Your OTP Verification Code');
            });

            \Log::info('Email OTP sent', [
                'email' => $email,
                'purpose' => $purpose,
            ]);

            return true;
        } catch (\Exception $e) {
            \Log::error('Email OTP Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Send OTP via SMS (BulkSMSBD)
     */
    public function sendPhoneOtp(string $phone, string $purpose = 'registration'): bool
    {
        try {
            // Create OTP record
            $otpRecord = $this->createOtp($phone, 'phone', $purpose);

            // Send SMS using BulkSMSBD
            $result = $this->smsService->sendOtp($phone, $otpRecord->otp);

            if ($result['success']) {
                \Log::info('SMS OTP sent successfully', [
                    'phone' => $phone,
                    'purpose' => $purpose,
                    'response' => $result['response'] ?? null,
                ]);

                return true;
            } else {
                \Log::error('SMS OTP failed', [
                    'phone' => $phone,
                    'error' => $result['message'],
                ]);

                return false;
            }

        } catch (\Exception $e) {
            \Log::error('SMS OTP Error: ' . $e->getMessage(), [
                'phone' => $phone,
                'purpose' => $purpose,
            ]);

            return false;
        }
    }

    /**
     * Verify OTP
     */
    public function verifyOtp(
        string $identifier,
        string $otp,
        string $type,
        string $purpose = 'registration'
    ): bool {
        $otpRecord = OtpVerification::where('identifier', $identifier)
            ->where('otp', $otp)
            ->where('type', $type)
            ->where('purpose', $purpose)
            ->valid()
            ->first();

        if (!$otpRecord) {
            return false;
        }

        // Check max attempts (5 attempts allowed)
        if ($otpRecord->attempts >= 5) {
            \Log::warning('OTP max attempts exceeded', [
                'identifier' => $identifier,
                'type' => $type,
            ]);
            return false;
        }

        $otpRecord->incrementAttempts();

        if ($otpRecord->otp === $otp) {
            $verified = $otpRecord->verify();

            if ($verified) {
                \Log::info('OTP verified successfully', [
                    'identifier' => $identifier,
                    'type' => $type,
                    'purpose' => $purpose,
                ]);
            }

            return $verified;
        }

        return false;
    }

    /**
     * Check if OTP is verified
     */
    public function isVerified(
        string $identifier,
        string $type,
        string $purpose = 'registration'
    ): bool {
        return OtpVerification::where('identifier', $identifier)
            ->where('type', $type)
            ->where('purpose', $purpose)
            ->where('is_verified', true)
            ->where('verified_at', '>=', now()->subHours(24)) // Valid for 24 hours
            ->exists();
    }

    /**
     * Get remaining attempts
     */
    public function getRemainingAttempts(
        string $identifier,
        string $type,
        string $purpose = 'registration'
    ): int {
        $otpRecord = OtpVerification::where('identifier', $identifier)
            ->where('type', $type)
            ->where('purpose', $purpose)
            ->valid()
            ->first();

        if (!$otpRecord) {
            return 5; // Default max attempts
        }

        return max(0, 5 - $otpRecord->attempts);
    }

    /**
     * Send test SMS (for debugging)
     */
    public function sendTestSms(string $phone): array
    {
        return $this->smsService->send($phone, "Test SMS from Social Media Platform. If you received this, SMS service is working!");
    }
}