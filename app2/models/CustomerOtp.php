<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * Ported from protected/models/CustomerOtp.php (Yii 1).
 *
 * Only the verification path is ported. generateOTP() is not, because the one
 * caller that uses it - customer/sendOTP - also despatches a real WhatsApp
 * message, so it cannot be exercised by a comparison test without messaging a
 * real phone number. That action stays with Yii 1 until there is a stubbed
 * transport to send through.
 */
class CustomerOtp extends ActiveRecord
{
    public static function tableName()
    {
        return 'tbl_customer_otp';
    }

    /** Whether an unverified, unexpired OTP already exists for a customer. */
    public static function hasPendingOTP($customerId)
    {
        return static::find()
            ->where(['customer_id' => $customerId, 'is_verified' => 0])
            ->andWhere(['>', 'expires_at', new \yii\db\Expression('NOW()')])
            ->exists();
    }

    /**
     * Checks a code against the most recent unverified OTP for a customer.
     *
     * Behaviour reproduced exactly, including two quirks worth knowing about:
     *
     *  - The attempt counter is incremented in memory but only persisted on the
     *    path that rejects for too many attempts. A wrong code therefore does
     *    not consume an attempt, because no matching unverified row is found at
     *    all - the code is part of the lookup. The "maximum attempts exceeded"
     *    branch is only reachable by submitting the *correct* code four times.
     *  - Expiry is checked after the lookup, so an expired OTP reports
     *    "OTP has expired" rather than "Invalid OTP".
     */
    public static function verifyOTP($customerId, $otpCode)
    {
        $otp = static::find()
            ->where(['customer_id' => $customerId, 'otp_code' => $otpCode, 'is_verified' => 0])
            ->orderBy(['created_at' => SORT_DESC, 'id' => SORT_DESC]) // id breaks the 1-second tie
            ->one();

        if (!$otp) {
            return ['success' => false, 'message' => 'Invalid OTP'];
        }

        if (strtotime((string)$otp->expires_at) < time()) {
            return ['success' => false, 'message' => 'OTP has expired'];
        }

        $otp->attempts += 1;

        if ($otp->attempts > 3) {
            $otp->save(false);
            return ['success' => false, 'message' => 'Maximum attempts exceeded'];
        }

        $otp->is_verified = 1;
        $otp->verified_at = date('Y-m-d H:i:s');

        if ($otp->save(false)) {
            return [
                'success' => true,
                'message' => 'OTP verified successfully',
                'verified_at' => $otp->verified_at,
            ];
        }

        return ['success' => false, 'message' => 'Failed to verify OTP'];
    }
}
