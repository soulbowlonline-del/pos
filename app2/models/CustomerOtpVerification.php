<?php
namespace app\models;

use yii\db\ActiveRecord;

/**
 * Ported from protected/models/CustomerOtpVerification.php (Yii 1).
 *
 * A separate table and flow from CustomerOtp: this one backs the WhatsApp OTP
 * pair (sentwhatappotp / verifywhatappotp) rather than the newer sendOTP path.
 */
class CustomerOtpVerification extends ActiveRecord
{
    public static function tableName()
    {
        return 'tbl_customer_otp_verification';
    }

    /**
     * Marks the code verified and returns true, or false if there is no
     * matching unverified code or it has expired.
     *
     * Ordered by id: the Yii 1 findByAttributes() has no order, so which row
     * was taken when a customer had several matching codes was the database's
     * choice.
     */
    public static function verifyOtp($customerId, $otpCode)
    {
        $entry = static::find()
            ->where(['customer_id' => $customerId, 'otp_code' => $otpCode, 'is_verified' => 0])
            ->orderBy(['id' => SORT_ASC])
            ->one();

        if ($entry && strtotime((string)$entry->expires_at) > time()) {
            $entry->is_verified = 1;
            $entry->save(false);
            return true;
        }
        return false;
    }

    /**
     * Issues a six-digit code valid for five minutes and returns it.
     *
     * Unlike CustomerOtp::generateOTP this does not clean up prior codes, so a
     * customer accumulates rows here. Reproduced as-is.
     */
    public static function generateOtp($customerId, $expiryMinutes = 5)
    {
        $otp = rand(100000, 999999);

        $entry = new static();
        $entry->customer_id = $customerId;
        $entry->otp_code = $otp;
        $entry->expires_at = date('Y-m-d H:i:s', strtotime("+{$expiryMinutes} minutes"));
        $entry->is_verified = 0;
        $entry->save(false);

        return $otp;
    }
}
