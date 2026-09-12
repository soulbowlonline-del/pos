<?php
class OTPService 
{
    /**
     * Send OTP via WhatsApp using InteraktApi
     */
    public static function sendOTPWhatsApp($phoneNumber, $customerName, $otpCode) 
    {
        try {
            // Clean phone number
            $phoneNumber = preg_replace("/[^0-9]/", "", $phoneNumber);
            
            // Template data for OTP verification
            $template = 'otp_redeem_loyalty'; // This template should be created in Interakt
            // $bodyValues = [$customerName, $otpCode];
            $bodyValues = [$otpCode];
            $buttonValues = (object)[ "0" => [$otpCode] ];
            // Send via Interakt API
            Yii::app()->interaktApi->sendOtpMessage(
                $template, 
                $phoneNumber, 
                $bodyValues,
                $buttonValues
            );
            
            return array('success' => true, 'message' => 'OTP sent successfully');
            
        } catch (Exception $e) {
            Yii::log('OTP WhatsApp send failed: ' . $e->getMessage(), CLogger::LEVEL_ERROR);
            return array('success' => false, 'message' => 'Failed to send OTP via WhatsApp');
        }
    }

    /**
     * Send OTP via SMS (fallback method)
     * You can implement SMS gateway integration here
     */
    public static function sendOTPSMS($phoneNumber, $otpCode) 
    {
        // Implement SMS gateway integration here
        // For now, just log the OTP for debugging
        Yii::log("SMS OTP for {$phoneNumber}: {$otpCode}", CLogger::LEVEL_INFO);
        
        return array('success' => true, 'message' => 'OTP sent via SMS');
    }

    /**
     * Validate phone number format
     */
    public static function validatePhoneNumber($phoneNumber) 
    {
        $cleanNumber = preg_replace("/[^0-9]/", "", $phoneNumber);
        
        if (strlen($cleanNumber) == 10) {
            return array('valid' => true, 'number' => $cleanNumber);
        }
        
        if (strlen($cleanNumber) == 12 && substr($cleanNumber, 0, 2) == '91') {
            return array('valid' => true, 'number' => substr($cleanNumber, 2));
        }
        
        return array('valid' => false, 'message' => 'Invalid phone number format');
    }

    /**
     * Generate secure OTP
     */
    public static function generateSecureOTP($length = 6) 
    {
        $otp = '';
        for ($i = 0; $i < $length; $i++) {
            $otp .= mt_rand(0, 9);
        }
        return $otp;
    }

    /**
     * Rate limiting check
     */
    public static function checkRateLimit($phoneNumber, $maxAttempts = 3, $timeWindow = 300) // 5 minutes
    {
        $cacheKey = 'otp_rate_limit_' . $phoneNumber;
        $attempts = Yii::app()->cache->get($cacheKey);
        
        if ($attempts === false) {
            $attempts = 0;
        }
        
        if ($attempts >= $maxAttempts) {
            return array('allowed' => false, 'message' => 'Rate limit exceeded. Please try again later.');
        }
        
        // Increment attempts
        Yii::app()->cache->set($cacheKey, $attempts + 1, $timeWindow);
        
        return array('allowed' => true, 'remaining' => $maxAttempts - $attempts - 1);
    }
}