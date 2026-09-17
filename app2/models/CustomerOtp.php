<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * Ported from protected/models/CustomerOtp.php (Yii 1).
 *
 * The generate and verify paths are both ported. generateOTP()'s caller,
 * customer/sendOTP, despatches a WhatsApp message - that goes through the
 * shared outbound stub, so with POS_STUB_OUTBOUND=1 it can be compared without
 * anything leaving the server.
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

    /**
     * Deletes a customer's expired or already-used codes.
     *
     * Reproduced including its flaw: expires_at is written from PHP's clock but
     * compared here against MySQL's NOW(), and the two run 5h30m apart in this
     * stack, so expired codes survive far longer than intended. Fixing it means
     * aligning the clocks, which is a configuration change beyond this port.
     */
    public static function cleanupExpiredOTPs($customerId)
    {
        static::deleteAll([
            'and',
            ['customer_id' => $customerId],
            ['or', ['<', 'expires_at', new \yii\db\Expression('NOW()')], ['is_verified' => 1]],
        ]);
    }

    /** Issues a fresh six-digit code, valid for five minutes. */
    public static function generateOTP($customerId, $phoneNumber)
    {
        self::cleanupExpiredOTPs($customerId);

        $otpCode = str_pad((string)mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));

        $otp = new static();
        $otp->customer_id = $customerId;
        $otp->phone_number = $phoneNumber;
        $otp->otp_code = $otpCode;
        $otp->attempts = 0;
        $otp->is_verified = 0;
        $otp->expires_at = $expiresAt;
        $otp->created_at = date('Y-m-d H:i:s');

        if ($otp->save(false)) {
            return [
                'success' => true,
                'otp_id' => $otp->id,
                'otp_code' => $otpCode,
                'expires_at' => $expiresAt,
            ];
        }
        return ['success' => false, 'message' => 'Failed to generate OTP'];
    }
}
