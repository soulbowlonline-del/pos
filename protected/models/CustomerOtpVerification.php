<?php

class CustomerOtpVerification extends GxActiveRecord
{
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    public function tableName()
    {
        return 'tbl_customer_otp_verification';
    }

    public function rules()
    {
        return [
            ['customer_id, otp_code, expires_at', 'required'],
            ['customer_id', 'numerical', 'integerOnly' => true],
            ['otp_code', 'length', 'max' => 10],
            ['is_verified', 'boolean'],
            ['created_at, is_verified', 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'id' => 'ID',
            'customer_id' => 'Customer ID',
            'otp_code' => 'OTP Code',
            'expires_at' => 'Expires At',
            'is_verified' => 'Is Verified',
            'created_at' => 'Created At',
        ];
    }

    // Method to generate and store OTP
    public static function generateOtp($customerId, $expiryMinutes = 5)
    {
        $otp = rand(100000, 999999);  // Generate a 6-digit numeric OTP
        $expiresAt = date('Y-m-d H:i:s', strtotime("+$expiryMinutes minutes"));

        $otpEntry = new self();
        $otpEntry->customer_id = $customerId;
        $otpEntry->otp_code = $otp;
        $otpEntry->expires_at = $expiresAt;
        $otpEntry->is_verified = 0;
        $otpEntry->save(false);

        return $otp;
    }

    // Method to verify OTP
    public static function verifyOtp($customerId, $otpCode)
    {
        $otpEntry = self::model()->findByAttributes([
            'customer_id' => $customerId,
            'otp_code' => $otpCode,
            'is_verified' => 0,
        ]);

        if ($otpEntry && strtotime($otpEntry->expires_at) > time()) {
            $otpEntry->is_verified = 1;
            $otpEntry->save(false);
            return true;
        }

        return false;
    }
}