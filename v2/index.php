<?php
/**
 * Entry script for the Yii 2 application (served at /v2).
 *
 * The Yii 1 application keeps index.php at the web root; this is a separate
 * front controller so the two frameworks never load in the same process.
 */
$root = dirname(__DIR__);

// Same .env loader the Yii 1 side uses, so both read one set of credentials.
$envFile = $root . '/.env';
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
            $_ENV[$k] = $v;
        }
    }
}

if (getenv('POS_V2_COOKIE_KEY') === false || getenv('POS_V2_COOKIE_KEY') === '') {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'POS_V2_COOKIE_KEY is not set in .env']);
    exit(1);
}

defined('YII_DEBUG') or define('YII_DEBUG', getenv('POS_V2_DEBUG') === '1');
defined('YII_ENV') or define('YII_ENV', 'prod');

require $root . '/vendor/autoload.php';
require $root . '/vendor/yiisoft/yii2/Yii.php';

$config = require $root . '/app2/config/web.php';
(new yii\web\Application($config))->run();
