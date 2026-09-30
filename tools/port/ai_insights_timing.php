<?php
/**
 * Runs every Insights check once against the database and prints how long
 * each took and what it found. Read-only: the checks are SELECTs.
 *
 * For the first deploy of /v2/ai, to see how the checks behave on the real
 * data before anyone opens the page:
 *
 *   docker exec -u www-data -w /var/www/html pos-php-83 php tools/port/ai_insights_timing.php
 *
 * As www-data, so the cache directory it may create under app2/runtime stays
 * writable by the web server.
 *
 * A check slower than about 20 s is stopped by MySQL (MAX_EXECUTION_TIME) and
 * shows as FAILED here; the page shows it as "could not be worked out".
 * It leaves nothing in the cache, and it calls no AI.
 *
 * With -v, each check is followed by its queries that took more than 0.3 s.
 */
$root = dirname(__DIR__, 2);
// The same .env reading as v2/index.php: quotes around a value are removed.
foreach (is_file("$root/.env") ? file("$root/.env", FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) : [] as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
        continue;
    }
    list($k, $v) = array_map('trim', explode('=', $line, 2));
    if (strlen($v) >= 2 && ($v[0] === '"' || $v[0] === "'") && substr($v, -1) === $v[0]) {
        $v = substr($v, 1, -1);
    }
    if (getenv($k) === false) {
        putenv($k . '=' . $v);
        $_ENV[$k] = $v;
    }
}
require "$root/lib/PosOutbound.php";
require "$root/vendor/autoload.php";
require "$root/vendor/yiisoft/yii2/Yii.php";
Yii::$classMap['yii\helpers\Html'] = "$root/app2/helpers/Html.php";
$_SERVER += ['SCRIPT_FILENAME' => "$root/v2/index.php", 'SCRIPT_NAME' => '/v2/index.php', 'REQUEST_URI' => '/v2/',
             'SERVER_NAME' => 'localhost', 'HTTP_HOST' => 'localhost'];
$config = require "$root/app2/config/web.php";
new yii\web\Application($config);

$verbose = in_array('-v', $argv, true);
$total = microtime(true);
foreach (app\components\ai\Insights::checks() as $key => list($group, $title)) {
    app\components\ai\Insights::forget($key);
    app\components\ai\AiData::$trace = [];
    $t = microtime(true);
    try {
        $r = app\components\ai\Insights::run($key);
        $out = sprintf('%6d found', $r['total']);
    } catch (Throwable $e) {
        $out = 'FAILED ' . substr(preg_replace('/\s+/', ' ', $e->getMessage()), 0, 160);
    }
    app\components\ai\Insights::forget($key);
    printf("%-22s %6.2fs  %s\n", $key, microtime(true) - $t, $out);
    foreach ($verbose ? app\components\ai\AiData::$trace : [] as list($secs, $rows, $sql)) {
        if ($secs > 0.3) {
            printf("    %6.2fs %7d rows  %s\n", $secs, $rows, substr(preg_replace('/\s+/', ' ', $sql), 0, 150));
        }
    }
}
printf("%-22s %6.2fs\n", 'all', microtime(true) - $total);
