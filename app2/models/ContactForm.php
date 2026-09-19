<?php
namespace app\models;

use yii\base\Model;

/**
 * Ported from protected/models/ContactForm.php (Yii 1).
 *
 * A form model, not an ActiveRecord: it has no table and the generator - which
 * reads a model's columns from the database - has no way to produce it. Ported
 * by hand for that reason, and kept to the shape of the original.
 *
 * The captcha rule is dropped. Yii 1 made it conditional on
 * CCaptcha::checkRequirements(), which asks whether GD is present; Yii 2's
 * captcha is a separate action and route that this controller does not have,
 * so requiring a code no page can show would refuse every submission. Yii 1
 * with GD absent behaves the same way, and that is the behaviour reproduced.
 */
class ContactForm extends Model
{
    public $name;

    public $email;

    public $subject;

    public $body;

    public $verifyCode;

    public function rules()
    {
        return [
            [['name', 'email', 'subject', 'body'], 'required'],
            ['email', 'email'],
            ['verifyCode', 'safe'],
        ];
    }

    public function attributeLabels()
    {
        return [
            'verifyCode' => 'Verification Code',
        ];
    }
}
