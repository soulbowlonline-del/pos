<?php
namespace app\components;

use Throwable;
use Yii;

/** Yii 2 port of protected/components/OTPService.php, OTP-over-WhatsApp path. */
class OTPService
{
    public static function sendOTPWhatsApp($phoneNumber, $customerName, $otpCode)
    {
        try {
            $phoneNumber = preg_replace('/[^0-9]/', '', $phoneNumber);

            $template = 'otp_redeem_loyalty';
            // The Yii 1 version passes only the code, with the customer name
            // commented out; buttonValues repeats it for the copy button.
            $bodyValues = [$otpCode];
            $buttonValues = (object)['0' => [$otpCode]];

            $api = new InteraktApi(getenv('POS_INTERAKT_API_KEY') ?: null);
            $api->sendOtpMessage($template, $phoneNumber, $bodyValues, $buttonValues);

            return ['success' => true, 'message' => 'OTP sent successfully'];
        } catch (Throwable $e) {
            Yii::error('OTP WhatsApp send failed: ' . $e->getMessage());
            return ['success' => false, 'message' => 'Failed to send OTP via WhatsApp'];
        }
    }
}
