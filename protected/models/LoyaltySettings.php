<?php

class LoyaltySettings extends CActiveRecord
{
    /**
     * @return string the associated database table name
     */
    public function tableName()
    {
        return 'tbl_loyalty_settings';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules()
    {
        return array(
            array('setting_key, setting_value', 'required'),
            array('setting_key', 'length', 'max' => 100),
            array('setting_key', 'unique'),
            array('setting_value', 'safe'),
            array('description', 'length', 'max' => 255),
            array('description', 'safe'),
            // The following rule is used by search().
            array('id, setting_key, setting_value, description, updated_at', 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations()
    {
        return array(
            // Add relations here if needed
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels()
    {
        return array(
            'id' => 'ID',
            'setting_key' => 'Setting Key',
            'setting_value' => 'Setting Value',
            'description' => 'Description',
            'updated_at' => 'Updated At',
        );
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     *
     * Typical usecase:
     * - Initialize the model fields with values from filter form.
     * - Execute this method to get CActiveDataProvider instance which will filter
     * models according to data in model fields.
     * - Pass data provider to CGridView, CListView or any similar widget.
     *
     * @return CActiveDataProvider the data provider that can return the models
     * based on the search/filter conditions.
     */
    public function search()
    {
        $criteria = new CDbCriteria;

        $criteria->compare('id', $this->id);
        $criteria->compare('setting_key', $this->setting_key, true);
        $criteria->compare('setting_value', $this->setting_value, true);
        $criteria->compare('description', $this->description, true);
        $criteria->compare('updated_at', $this->updated_at, true);

        return new CActiveDataProvider($this, array(
            'criteria' => $criteria,
        ));
    }

    /**
     * Returns the static model of the specified AR class.
     * Please note that you should have this exact method in all your CActiveRecord descendants!
     * @param string $className active record class name.
     * @return LoyaltySettings the static model class
     */
    public static function model($className = __CLASS__)
    {
        return parent::model($className);
    }

    /**
     * Get a setting value by key
     * @param string $key
     * @param mixed $defaultValue
     * @return mixed
     */
    public static function getValue($key, $defaultValue = null)
    {
        $setting = self::model()->findByAttributes(array('setting_key' => $key));
        return $setting ? $setting->setting_value : $defaultValue;
    }

    /**
     * Set a setting value by key
     * @param string $key
     * @param mixed $value
     * @param string $description
     * @return bool
     */
    public static function setValue($key, $value, $description = null)
    {
        $setting = self::model()->findByAttributes(array('setting_key' => $key));
        
        if (!$setting) {
            $setting = new self();
            $setting->setting_key = $key;
        }
        
        $setting->setting_value = $value;
        if ($description !== null) {
            $setting->description = $description;
        }
        
        return $setting->save();
    }

    /**
     * Get all settings as key-value array
     * @return array
     */
    public static function getAllSettings()
    {
        $settings = self::model()->findAll();
        $result = array();
        
        foreach ($settings as $setting) {
            $result[$setting->setting_key] = $setting->setting_value;
        }
        
        return $result;
    }

    /**
     * Get loyalty program settings with defaults
     * @return array
     */
    public static function getLoyaltySettings()
    {
        $defaults = array(
            'earn_rate' => 1,
            'min_redeem_points' => 50,
            'point_value' => 1,
            'expiry_months' => 12,
            'is_active' => 1,
        );
        
        $settings = self::getAllSettings();
        
        return array_merge($defaults, $settings);
    }

    /**
     * Initialize default loyalty settings
     * @return bool
     */
    public static function initializeDefaults()
    {
        $defaults = array(
            'earn_rate' => array('value' => '1', 'description' => 'Points earned per 100 spent'),
            'min_redeem_points' => array('value' => '50', 'description' => 'Minimum points required for redemption'),
            'point_value' => array('value' => '1', 'description' => 'Value of 1 point in currency'),
            'expiry_months' => array('value' => '12', 'description' => 'Points expiry in months (0 = no expiry)'),
            'is_active' => array('value' => '1', 'description' => 'Loyalty program active status'),
        );
        
        $transaction = Yii::app()->db->beginTransaction();
        
        try {
            foreach ($defaults as $key => $data) {
                $existing = self::model()->findByAttributes(array('setting_key' => $key));
                if (!$existing) {
                    $setting = new self();
                    $setting->setting_key = $key;
                    $setting->setting_value = $data['value'];
                    $setting->description = $data['description'];
                    $setting->save();
                }
            }
            
            $transaction->commit();
            return true;
        } catch (Exception $e) {
            $transaction->rollback();
            return false;
        }
    }

    /**
     * Check if loyalty program is active
     * @return bool
     */
    public static function isLoyaltyActive()
    {
        return (bool) self::getValue('is_active', 0);
    }

    /**
     * Get earn rate (points per 100 spent)
     * @return float
     */
    public static function getEarnRate()
    {
        return (float) self::getValue('earn_rate', 1);
    }

    /**
     * Get minimum redeem points
     * @return int
     */
    public static function getMinRedeemPoints()
    {
        return (int) self::getValue('min_redeem_points', 50);
    }

    /**
     * Get point value in currency
     * @return float
     */
    public static function getPointValue()
    {
        return (float) self::getValue('point_value', 1);
    }

    /**
     * Get points expiry in months
     * @return int
     */
    public static function getExpiryMonths()
    {
        return (int) self::getValue('expiry_months', 12);
    }
}