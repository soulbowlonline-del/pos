<?php
/**
 * Shared mysqli connection for the standalone report scripts in the webroot.
 *
 * Those scripts each opened their own connection with a hardcoded host, user
 * and password:
 *
 *     $mysqli = new mysqli("localhost", "root", "<password>", "pos");
 *
 * That breaks under Docker - MySQL is the "db" service, not localhost, and the
 * database is "pos_live" - so every one of these reports failed to connect. It
 * also duplicated a plaintext root password across five files in the webroot.
 *
 * Credentials now come from the environment, matching config/prod-db.php.
 *
 * @return mysqli
 */
function pos_report_db()
{
    $envFile = __DIR__ . '/.env';
    if (is_file($envFile)) {
        foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                continue;
            }
            list($k, $v) = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v);
            if (strlen($v) >= 2 && ($v[0] === '"' || $v[0] === "'") && substr($v, -1) === $v[0]) {
                $v = substr($v, 1, -1);
            }
            if (getenv($k) === false) {
                putenv($k . '=' . $v);
            }
        }
    }

    $host = getenv('POS_DB_HOST') !== false ? getenv('POS_DB_HOST') : 'db';
    $user = getenv('POS_DB_USER') !== false ? getenv('POS_DB_USER') : 'root';
    $pass = getenv('POS_DB_PASSWORD') !== false ? getenv('POS_DB_PASSWORD') : '';
    $name = getenv('DB_NAME') !== false ? getenv('DB_NAME') : 'pos_live';

    // PHP 8.1 made mysqli throw by default. These scripts predate that and
    // check ->connect_errno instead, so keep the old reporting mode.
    if (function_exists('mysqli_report')) {
        mysqli_report(MYSQLI_REPORT_OFF);
    }

    return @new mysqli($host, $user, $pass, $name);
}
