<?php
class CustomerOtp extends CActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'tbl_customer_otp';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return array(
            array('customer_id, phone_number, otp_code', 'required'),
            array('customer_id, attempts, is_verified', 'numerical', 'integerOnly' => true),
            array('phone_number', 'length', 'max' => 15),
            array('otp_code', 'length', 'max' => 6),
            array('expires_at, verified_at, created_at', 'safe'),
            // The following rule is used by search().
            array('id, customer_id, phone_number, otp_code, attempts, is_verified, expires_at, verified_at, created_at', 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        return array(
            'customer' => array(self::BELONGS_TO, 'Customer', 'customer_id'),
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'customer_id' => 'Customer ID',
            'phone_number' => 'Phone Number',
            'otp_code' => 'OTP Code',
            'attempts' => 'Attempts',
            'is_verified' => 'Is Verified',
            'expires_at' => 'Expires At',
            'verified_at' => 'Verified At',
            'created_at' => 'Created At',
        );
    }

    /**
     * Returns the static model of the specified AR class.
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * Generate OTP for customer
     */
    public static function generateOTP($customerId, $phoneNumber)
    {
        // Clean up expired OTPs for this customer
        self::cleanupExpiredOTPs($customerId);
        
        // Generate 6-digit OTP
        $otpCode = str_pad(mt_rand(100000, 999999), 6, '0', STR_PAD_LEFT);
        
        // Set expiration time (5 minutes from now)
        $expiresAt = date('Y-m-d H:i:s', strtotime('+5 minutes'));
        
        // Create OTP record
        $otp = new self();
        $otp->customer_id = $customerId;
        $otp->phone_number = $phoneNumber;
        $otp->otp_code = $otpCode;
        $otp->attempts = 0;
        $otp->is_verified = 0;
        $otp->expires_at = $expiresAt;
        $otp->created_at = date('Y-m-d H:i:s');
        
        if ($otp->save()) {
            return array(
                'success' => true,
                'otp_id' => $otp->id,
                'otp_code' => $otpCode,
                'expires_at' => $expiresAt
            );
        }
        
        return array('success' => false, 'message' => 'Failed to generate OTP');
    }

    /**
     * Verify OTP for customer
     */
    public static function verifyOTP($customerId, $otpCode)
    {
        $criteria = new CDbCriteria();
        $criteria->condition = 'customer_id = :customer_id AND otp_code = :otp_code AND is_verified = 0';
        $criteria->params = array(
            ':customer_id' => $customerId,
            ':otp_code' => $otpCode
        );
        $criteria->order = 'created_at DESC, id DESC'; // id breaks the 1-second tie
        
        $otp = self::model()->find($criteria);
        
        if (!$otp) {
            return array('success' => false, 'message' => 'Invalid OTP');
        }
        
        // Check if OTP has expired
        if (strtotime($otp->expires_at) < time()) {
            return array('success' => false, 'message' => 'OTP has expired');
        }
        
        // Increment attempts
        $otp->attempts += 1;
        
        // Check max attempts (allow 3 attempts)
        if ($otp->attempts > 3) {
            $otp->save();
            return array('success' => false, 'message' => 'Maximum attempts exceeded');
        }
        
        // Mark as verified
        $otp->is_verified = 1;
        $otp->verified_at = date('Y-m-d H:i:s');
        
        if ($otp->save()) {
            return array(
                'success' => true,
                'message' => 'OTP verified successfully',
                'verified_at' => $otp->verified_at
            );
        }
        
        return array('success' => false, 'message' => 'Failed to verify OTP');
    }

    /**
     * Clean up expired OTPs for customer
     */
    public static function cleanupExpiredOTPs($customerId)
    {
        $criteria = new CDbCriteria();
        $criteria->condition = 'customer_id = :customer_id AND (expires_at < NOW() OR is_verified = 1)';
        $criteria->params = array(':customer_id' => $customerId);
        
        self::model()->deleteAll($criteria);
    }

    /**
     * Check if customer has pending unverified OTP
     */
    public static function hasPendingOTP($customerId)
    {
        $criteria = new CDbCriteria();
        $criteria->condition = 'customer_id = :customer_id AND is_verified = 0 AND expires_at > NOW()';
        $criteria->params = array(':customer_id' => $customerId);
        
        return self::model()->exists($criteria);
    }

    /**
     * Get remaining time for OTP expiration
     */
    public function getRemainingTime()
    {
        $now = time();
        $expires = strtotime($this->expires_at);
        
        if ($expires > $now) {
            return $expires - $now; // seconds remaining
        }
        
        return 0; // expired
    }
}