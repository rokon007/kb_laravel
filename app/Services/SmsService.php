<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class SmsService
{
    protected $apiKey;
    protected $senderId;
    protected $apiUrl;

    public function __construct()
    {
        $this->apiKey = config('bulksmsbd.api_key');
        $this->senderId = config('bulksmsbd.sender_id');
        $this->apiUrl = config('bulksmsbd.api_url');
    }

    /**
     * Send SMS using BulkSMSBD
     * 
     * @param string|array $numbers Phone number(s) - format: 8801XXXXXXXXX or array of numbers
     * @param string $message SMS message content
     * @param string $type SMS type (general, otp, notification)
     * @param string|null $purpose Purpose (registration, login, password_reset)
     * @return array Response with success status and message
     */
    public function send($numbers, string $message, string $type = 'general', string $purpose = null): array
    {
        try {
            // Validate configuration
            if (empty($this->apiKey) || empty($this->senderId)) {
                throw new \Exception('BulkSMSBD API credentials not configured');
            }

            // Format phone numbers
            $formattedNumbers = $this->formatPhoneNumbers($numbers);

            if (empty($formattedNumbers)) {
                throw new \Exception('Invalid phone number(s)');
            }

            // Prepare data
            $data = [
                'api_key' => $this->apiKey,
                'senderid' => $this->senderId,
                'number' => $formattedNumbers,
                'message' => $message,
            ];

            // Send request using cURL
            $response = $this->sendCurlRequest($data);

            // Parse response
            $result = $this->parseResponse($response);

            // Log successful SMS
            Log::info('SMS sent successfully', [
                'numbers' => $formattedNumbers,
                'message' => $message,
                'type' => $type,
                'purpose' => $purpose,
                'response' => $result,
            ]);

            // Log SMS to database
            $this->logSms($formattedNumbers, $message, true, $result, null, $type, $purpose);

            return [
                'success' => true,
                'message' => 'SMS sent successfully',
                'response' => $result,
            ];

        } catch (\Exception $e) {
            // Log error
            Log::error('SMS sending failed', [
                'numbers' => $numbers,
                'message' => $message,
                'type' => $type,
                'purpose' => $purpose,
                'error' => $e->getMessage(),
            ]);

            // Log error to database
            $this->logSms($numbers, $message, false, null, $e->getMessage(), $type, $purpose);

            return [
                'success' => false,
                'message' => 'Failed to send SMS: ' . $e->getMessage(),
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Send SMS using cURL
     */
    protected function sendCurlRequest(array $data): string
    {
        $ch = curl_init();
        
        curl_setopt($ch, CURLOPT_URL, $this->apiUrl);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $data);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        
        $response = curl_exec($ch);
        
        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception('cURL Error: ' . $error);
        }
        
        curl_close($ch);
        
        return $response;
    }

    /**
     * Format phone numbers to 88XXXXXXXXXXX format
     * 
     * @param string|array $numbers
     * @return string Comma-separated numbers
     */
    protected function formatPhoneNumbers($numbers): string
    {
        if (is_string($numbers)) {
            $numbers = [$numbers];
        }

        if (!is_array($numbers)) {
            return '';
        }

        $formatted = [];

        foreach ($numbers as $number) {
            // Remove spaces, dashes, and other characters
            $cleaned = preg_replace('/[^0-9]/', '', $number);

            // Add 88 prefix if not present
            if (!str_starts_with($cleaned, '88')) {
                // If starts with 0, replace with 880
                if (str_starts_with($cleaned, '0')) {
                    $cleaned = '88' . $cleaned;
                } else {
                    // If starts with 1, add 880
                    $cleaned = '880' . $cleaned;
                }
            }

            // Validate BD number (should be 88 + 11 digits)
            if (strlen($cleaned) === 13 && str_starts_with($cleaned, '88')) {
                $formatted[] = $cleaned;
            }
        }

        return implode(',', $formatted);
    }

    /**
     * Parse API response
     */
    protected function parseResponse(string $response): array
    {
        // Try to decode JSON response
        $decoded = json_decode($response, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $decoded;
        }

        // If not JSON, return as text
        return [
            'raw_response' => $response,
        ];
    }

    /**
     * Log SMS to database
     */
    protected function logSms(string $phone, string $message, bool $success, $response = null, $error = null, string $type = 'general', string $purpose = null)
    {
        try {
            \App\Models\SmsLog::create([
                'phone' => $phone,
                'message' => $message,
                'type' => $type,
                'purpose' => $purpose,
                'success' => $success,
                'response' => is_array($response) ? json_encode($response) : $response,
                'error' => $error,
                'sent_at' => now(),
            ]);
        } catch (\Exception $e) {
            // If SMS log fails, just log to Laravel log
            Log::error('Failed to log SMS: ' . $e->getMessage());
        }
    }

    /**
     * Send OTP SMS
     * 
     * @param string $phone Phone number
     * @param string $otp OTP code
     * @param string $purpose Purpose (registration, login, password_reset)
     * @return array
     */
    public function sendOtp(string $phone, string $otp, string $purpose = 'registration'): array
    {
        $message = "Your OTP code is: {$otp}. This code will expire in 10 minutes. Do not share this code with anyone.";
        
        return $this->send($phone, $message, 'otp', $purpose);
    }

    /**
     * Send welcome SMS
     */
    public function sendWelcomeSms(string $phone, string $name): array
    {
        $message = "Welcome to our platform, {$name}! Thank you for joining us.";
        
        return $this->send($phone, $message, 'notification', 'welcome');
    }

    /**
     * Send password reset OTP
     */
    public function sendPasswordResetOtp(string $phone, string $otp): array
    {
        $message = "Your password reset OTP is: {$otp}. Valid for 10 minutes. If you didn't request this, please ignore.";
        
        return $this->send($phone, $message, 'otp', 'password_reset');
    }

    /**
     * Check SMS balance (if API supports)
     */
    public function checkBalance(): array
    {
        // Implement if BulkSMSBD provides balance check endpoint
        return [
            'success' => false,
            'message' => 'Balance check not implemented',
        ];
    }
}