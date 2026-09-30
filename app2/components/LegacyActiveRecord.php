<?php
namespace app\components;

use yii\db\ActiveRecord;

/**
 * ActiveRecord that inserts the way Yii 1's does.
 *
 * Yii 1 leaves a NOT NULL column out of the INSERT when its value is null, so
 * the database fills in the column's own default. CDbCommandBuilder:
 *
 *     if(($column=$table->getColumn($name))!==null
 *         && ($value!==null || $column->allowNull))
 *
 * Yii 2 has no such rule: every dirty attribute goes into the INSERT, nulls
 * and all, and MySQL answers "Column 'x' cannot be null".
 *
 * That matters here because 67 of the 78 models carry the rule Gii generates
 *
 *     [[...], 'default', 'value' => null]
 *
 * which sets an attribute to null precisely when the form left it blank. Add
 * Item on itemReturnItem/admin posts an empty Disc1 %, the rule turns it into
 * null, tbl_item_return_item.discount1 is NOT NULL DEFAULT 0.00, and the
 * insert that Yii 1 completes - the column omitted, the default applied - the
 * port refused. The same pairing exists in most of the other 66.
 *
 * Validation still runs, and still runs first; only the column list narrows,
 * and only for columns the database can fill itself.
 */
class LegacyActiveRecord extends ActiveRecord
{
    public function insert($runValidation = true, $attributeNames = null)
    {
        if ($runValidation && !$this->validate($attributeNames)) {
            return false;
        }

        $names = $attributeNames === null
            ? array_keys($this->getDirtyAttributes())
            : $attributeNames;

        $schema = static::getTableSchema();
        $kept = [];
        foreach ($names as $name) {
            $column = $schema->getColumn($name);
            if ($column !== null && !$column->allowNull && self::wouldInsertNull($column, $this->getAttribute($name))) {
                continue;
            }
            $kept[] = $name;
        }

        // Validation has already run; parent::insert() must not repeat it,
        // because a second pass would re-apply the default rules it is the
        // point of this class to work around.
        return parent::insert(false, $kept);
    }

    /**
     * GxActiveRecord::saveUploadedFile(), which Yii 1 gives every model:
     * stores the posted file for $attribute under wdir/uploads as
     * "<Class>-<time>-<attribute>.<ext>" and puts that name on the model.
     *
     * user/update and customer/sendEmail call it and answered 500 without
     * it. Bill carries its own copy of the same code.
     */
    public function saveUploadedFile($model, $attribute)
    {
        $uploadedFile = \yii\web\UploadedFile::getInstance($model, $attribute);
        if (isset($uploadedFile)) {
            $path = \Yii::getAlias('@legacyroot') . '/wdir/uploads/';
            // Yii 1's get_class() is the bare class name, and its
            // getExtensionName() keeps the case Yii 2's getExtension() drops.
            $filename = $path . (new \ReflectionClass($model))->getShortName() . '-' . time() . '-' . $attribute
                . '.' . pathinfo($uploadedFile->name, PATHINFO_EXTENSION);
            if (file_exists($filename)) unlink($filename);
            $uploadedFile->saveAs($filename);
            $model->$attribute = basename($filename);
            return true;
        }
    }

    /**
     * GxActiveRecord::customermailsend(): one HTML mail through the marketing
     * SMTP account, with an optional attachment from wdir/uploads. 1 when
     * sent, 2 when not - and on failure Yii 1 prints the mailer's error into
     * the page, which this keeps.
     *
     * Recorded rather than sent under POS_STUB_OUTBOUND, as the port's other
     * outbound calls are: customer/sendEmail mails up to 1,900 real customers.
     */
    public function customermailsend($to, $from, $subject, $message, $attachment, $type, $name)
    {
        if (\PosOutbound::isStubbed()) {
            \PosOutbound::intercept(\PosOutbound::CHANNEL_MAIL, $to,
                ['from' => $from, 'subject' => $subject, 'body' => $message, 'attachment' => $attachment]);
            return 1;
        }

        set_time_limit(240);
        $mail = new \PHPMailer\PHPMailer\PHPMailer(false);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = 'true';
        $mail->Port = '587';
        $mail->Username = 'marketing@soulbowl.in';
        $mail->Password = getenv('POS_SMTP_PASSWORD');
        if ($attachment != '') {
            $mail->addAttachment(\Yii::getAlias('@legacyroot') . '/wdir/uploads/' . $attachment, $name, 'base64', $type);
        }
        $mail->setFrom($from, 'marketing@soulbowl.in');
        $mail->Subject = $subject;
        $mail->AltBody = 'In&out';
        $mail->msgHTML($message);
        $mail->addAddress($to, '');
        $mail->SMTPSecure = 'tls';
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];

        try {
            if (!$mail->send()) {
                echo 'Message could not be sent.';
                echo 'Mailer Error: ' . $mail->ErrorInfo;
                return 2;
            }
            return 1;
        } catch (\Exception $e) {
            \Yii::warning($e->getMessage(), '$mail_error');
            return 2;
        }
    }

    /**
     * GxActiveRecord::mailsend(): the same through the In&out account, which
     * always sends from that address and always attaches
     * wdir/uploads/pdf/filename.pdf as po.pdf. user/recover calls it.
     */
    public function mailsend($to, $from, $subject, $message)
    {
        $from = 'inandoutsec4@gmail.com';

        if (\PosOutbound::isStubbed()) {
            \PosOutbound::intercept(\PosOutbound::CHANNEL_MAIL, $to,
                ['from' => $from, 'subject' => $subject, 'body' => $message]);
            return 1;
        }

        set_time_limit(240);
        $mail = new \PHPMailer\PHPMailer\PHPMailer(false);
        $mail->isSMTP();
        $mail->Host = 'smtp.gmail.com';
        $mail->SMTPAuth = true;
        $mail->Port = '587';
        $mail->Username = 'inandoutsec4@gmail.com';
        $mail->Password = getenv('POS_SMTP_INOUT_PASSWORD');
        $mail->addAttachment(\Yii::getAlias('@legacyroot') . '/wdir/uploads/pdf/filename.pdf', 'po.pdf', 'base64', 'application/pdf');

        $mail->setFrom($from, 'In&out');
        $mail->Subject = $subject;
        $mail->AltBody = 'In&out';
        $mail->msgHTML($message);
        $mail->addAddress($to, '');
        $mail->SMTPSecure = 'tls';
        $mail->SMTPOptions = [
            'ssl' => [
                'verify_peer' => false,
                'verify_peer_name' => false,
                'allow_self_signed' => true,
            ],
        ];

        try {
            if (!$mail->send()) {
                echo 'Message could not be sent.';
                echo 'Mailer Error: ' . $mail->ErrorInfo;
                return 2;
            }
            return 1;
        } catch (\Exception $e) {
            \Yii::warning($e->getMessage(), '$mail_error');
            return 2;
        }
    }

    /**
     * findOne() and findAll() in the order Yii 1's finders use.
     *
     * GxActiveRecord::defaultScope() puts `id DESC` on every finder of a model
     * that does not override it, so findByAttributes() returns the newest
     * matching row. Yii 2's findOne() has no order and MySQL hands back the
     * oldest - which batch a stock adjustment, expiry or return lands on,
     * which bill a payment import marks paid, which pending MRS is reused.
     * Each model's defaultOrder() carries its own Yii 1 scope, null where the
     * model overrides it to none, so only the models that had an order get one.
     *
     * Only findOne()/findAll() come through here; a find() chain that names
     * its own orderBy() is untouched.
     */
    protected static function findByCondition($condition)
    {
        $query = parent::findByCondition($condition);
        if ($query->orderBy === null && method_exists(static::class, 'defaultOrder')) {
            $order = static::defaultOrder();
            if ($order) {
                $query->orderBy($order);
            }
        }

        return $query;
    }

    /**
     * Would this value reach the database as null?
     *
     * Two ways it can, and Yii 1 survives both. The attribute is already null,
     * which Yii 1 omits outright. Or it is the empty string on a column that is
     * not textual, which yii\db\ColumnSchema::typecast() turns into null on the
     * way out - Yii 1 sends the empty string instead and MySQL, in the
     * sql_mode="" this database runs under, stores 0.00.
     *
     * Either way the column is left out and the database fills it, which lands
     * on the same value Yii 1 records: a blank Disc % is stored as 0.00 by
     * both stacks, one by coercion and one by default.
     */
    private static function wouldInsertNull($column, $value)
    {
        if ($value === null) {
            return true;
        }

        return $value === '' && !in_array($column->type, ['string', 'text', 'char', 'binary'], true);
    }
}
