<?php

namespace App\Helpers;

class PhoneHelper
{
    /**
     * Format Bangladesh phone number
     * 
     * Examples:
     * 01712345678 -> 8801712345678
     * 8801712345678 -> 8801712345678
     * +8801712345678 -> 8801712345678
     * 
     * @param string $phone
     * @return string|null
     */
    public static function formatBdPhone(string $phone): ?string
    {
        // Remove all non-numeric characters
        $cleaned = preg_replace('/[^0-9]/', '', $phone);

        // Add 88 prefix if not present
        if (!str_starts_with($cleaned, '88')) {
            if (str_starts_with($cleaned, '0')) {
                $cleaned = '88' . $cleaned;
            } else {
                $cleaned = '880' . $cleaned;
            }
        }

        // Validate: Should be 88 + 11 digits
        if (strlen($cleaned) === 13 && str_starts_with($cleaned, '88')) {
            return $cleaned;
        }

        return null;
    }

    /**
     * Validate Bangladesh phone number
     * 
     * @param string $phone
     * @return bool
     */
    public static function isValidBdPhone(string $phone): bool
    {
        $formatted = self::formatBdPhone($phone);
        
        if (!$formatted) {
            return false;
        }

        // Check if starts with valid BD operator codes
        $validPrefixes = [
            '88013', '88014', '88015', '88016', '88017', '88018', '88019'
        ];

        foreach ($validPrefixes as $prefix) {
            if (str_starts_with($formatted, $prefix)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get phone operator name
     * 
     * @param string $phone
     * @return string|null
     */
    public static function getOperator(string $phone): ?string
    {
        $formatted = self::formatBdPhone($phone);

        if (!$formatted) {
            return null;
        }

        $prefix = substr($formatted, 3, 3); // Get 3 digits after 880

        $operators = [
            '013' => 'Grameenphone',
            '014' => 'Banglalink',
            '015' => 'Teletalk',
            '016' => 'Airtel',
            '017' => 'Grameenphone',
            '018' => 'Robi',
            '019' => 'Banglalink',
        ];

        return $operators[$prefix] ?? 'Unknown';
    }

    /**
     * Mask phone number for display
     * Example: 8801712345678 -> 880171****678
     * 
     * @param string $phone
     * @return string
     */
    public static function maskPhone(string $phone): string
    {
        $formatted = self::formatBdPhone($phone);

        if (!$formatted || strlen($formatted) < 13) {
            return $phone;
        }

        return substr($formatted, 0, 6) . '****' . substr($formatted, -3);
    }
}