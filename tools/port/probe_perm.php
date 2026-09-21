<?php
// Compares the two permission implementations for the same routes and user.
$root = '/var/www/html';
require $root . '/vendor/autoload.php';
require $root . '/vendor/yiisoft/yii2/Yii.php';
foreach (file($root . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
    list($k, $v) = explode('=', $line, 2);
    putenv(trim($k) . '=' . trim($v));
}
$config = require $root . '/app2/config/web.php';
$config['components']['request']['scriptUrl'] = '/v2/index.php';
$config['components']['request']['hostInfo'] = 'http://127.0.0.1';
new yii\web\Application($config);

$db = Yii::$app->db;
$roleId = $db->createCommand("SELECT role_id FROM tbl_user WHERE id = 9990003")->queryScalar();
echo "test admin role_id: " . var_export($roleId, true) . "\n";
foreach (['userRole/view', 'userRole/update', 'paymentMode/view', 'paymentMode/update'] as $url) {
    $pid = $db->createCommand("SELECT id FROM tbl_permission WHERE url = :u", [':u' => $url])->queryScalar();
    $has = $pid ? $db->createCommand(
        "SELECT COUNT(*) FROM tbl_role_permission WHERE role_id = :r AND permission_id = :p",
        [':r' => $roleId, ':p' => $pid])->queryScalar() : 0;
    printf("  %-22s permission_id=%-6s role_link=%s\n", $url, var_export($pid, true), $has);
}
