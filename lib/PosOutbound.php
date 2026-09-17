<?php
/**
 * Outbound stub transport.
 *
 * The application talks to several outside services: the Interakt WhatsApp API,
 * an HTTP file endpoint, an FTP server and an SMTP relay. None of those can be
 * exercised by a differential test - a comparison run would fire each call
 * twice, for real, to real phone numbers and real servers.
 *
 * With POS_STUB_OUTBOUND=1 in the environment, every outbound call is recorded
 * and answered with a canned response instead of being made. The recording is
 * then part of what a test compares, so the port is checked on *what it would
 * have sent*, not merely on the response it returned.
 *
 * Deliberately plain PHP with no framework dependency: the Yii 1 application
 * and the Yii 2 one both require this same file, so the two cannot drift.
 * A drifting stub would produce a matching comparison for the wrong reason.
 *
 * Never enable this in production. When it is off, this file adds one env
 * lookup per outbound call and changes nothing else.
 */
class PosOutbound
{
    const CHANNEL_HTTP = 'http';
    const CHANNEL_UPLOAD = 'upload';
    const CHANNEL_FTP = 'ftp';
    const CHANNEL_MAIL = 'mail';

    private static $pdo = null;

    /** @return bool whether outbound calls should be stubbed rather than made */
    public static function isStubbed()
    {
        return getenv('POS_STUB_OUTBOUND') === '1';
    }

    /**
     * Records an outbound call that was not made.
     *
     * Failures here are swallowed on purpose: a stub that breaks the request it
     * is standing in for would be worse than one that silently misses a log
     * line, and the tests read the log directly so a gap is visible.
     */
    public static function record($channel, $target, $payload)
    {
        try {
            $pdo = self::pdo();
            if ($pdo === null) {
                return;
            }
            $stmt = $pdo->prepare(
                'INSERT INTO tbl_outbound_stub_log (channel, target, payload, created_at)
                 VALUES (:channel, :target, :payload, NOW())'
            );
            $stmt->execute([
                ':channel' => $channel,
                ':target' => (string)$target,
                ':payload' => self::encode($payload),
            ]);
        } catch (\Throwable $e) {
            // deliberately ignored - see above
        }
    }

    /**
     * The canned answer for a stubbed call.
     *
     * Ids are derived from the call itself rather than a counter, so two runs
     * of the same case produce the same id and a comparison is not defeated by
     * ordering.
     */
    public static function response($channel, $target, $payload)
    {
        $fingerprint = substr(md5($channel . '|' . $target . '|' . self::encode($payload)), 0, 12);

        switch ($channel) {
            case self::CHANNEL_HTTP:
                // Shape matches what the Interakt API returns: callers read
                // result, id and message.
                return [
                    'result' => true,
                    'id' => 'stub-' . $fingerprint,
                    'message' => 'stubbed',
                ];
            case self::CHANNEL_UPLOAD:
                return json_encode(['status' => 'ok', 'stub' => true, 'id' => 'stub-' . $fingerprint]);
            case self::CHANNEL_FTP:
            case self::CHANNEL_MAIL:
                return true;
            default:
                return null;
        }
    }

    /** Records and answers in one step. */
    public static function intercept($channel, $target, $payload)
    {
        self::record($channel, $target, $payload);
        return self::response($channel, $target, $payload);
    }

    private static function encode($payload)
    {
        if (is_string($payload)) {
            return $payload;
        }
        $json = json_encode($payload);
        return $json === false ? '' : $json;
    }

    /**
     * Its own connection, from the same environment the application uses, so
     * this file stays independent of either framework's DB layer.
     */
    private static function pdo()
    {
        if (self::$pdo !== null) {
            return self::$pdo;
        }
        $host = getenv('POS_DB_HOST') !== false ? getenv('POS_DB_HOST') : 'db';
        $name = getenv('DB_NAME') !== false ? getenv('DB_NAME') : 'pos_live';
        $user = getenv('POS_DB_USER') !== false ? getenv('POS_DB_USER') : 'root';
        $pass = getenv('POS_DB_PASSWORD') !== false ? getenv('POS_DB_PASSWORD') : '';
        try {
            self::$pdo = new \PDO(
                sprintf('mysql:host=%s;dbname=%s', $host, $name),
                $user,
                $pass,
                [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
            );
        } catch (\Throwable $e) {
            self::$pdo = null;
        }
        return self::$pdo;
    }
}
