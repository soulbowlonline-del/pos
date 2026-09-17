<?php

use PHPMailer\PHPMailer\PHPMailer;

/**
 * JPhpMailer - thin Yii 1 wrapper around PHPMailer.
 *
 * Previously this extended the PHPMailer 5.1 copy bundled under
 * protected/extensions/phpmailer/, which is not PHP 8 compatible: it uses
 * each(), preg_replace() with the /e modifier and get_magic_quotes_gpc().
 * It now extends PHPMailer 6 from Composer instead.
 *
 * Calling code needs no changes. PHP method names are case-insensitive, so the
 * existing IsSMTP() / SetFrom() / AddAddress() / AddAttachment() / MsgHTML() /
 * Send() calls resolve to PHPMailer 6's isSMTP() / setFrom() / addAddress() /
 * addAttachment() / msgHTML() / send(). The properties in use (Host, Port,
 * SMTPAuth, SMTPSecure, SMTPOptions, Username, Password, Subject, AltBody,
 * ErrorInfo) kept their names in 6.x.
 *
 * Usage:
 *   Yii::import('ext.phpmailer.JPhpMailer');
 *   $mail = new JPhpMailer;
 *   $mail->IsSMTP();
 *   ...
 *   $mail->Send();
 */
class JPhpMailer extends PHPMailer
{
	/**
	 * PHPMailer 6 defaults $exceptions to true, whereas the bundled 5.1 copy
	 * defaulted to false and callers here check Send()'s return value rather
	 * than catching. Default to false to preserve that behaviour; pass true
	 * explicitly if you want exceptions.
	 *
	 * @param bool|null $exceptions whether to throw on error
	 */
	public function __construct($exceptions = false)
	{
		parent::__construct($exceptions);
	}
}
