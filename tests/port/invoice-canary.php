<?php
/**
 * Invoice canary: renders protected/views/order/_billpdf.php for a list of
 * order ids and writes the HTML to /tmp/billhtml/<id>.html.
 *
 * Run under both stacks and compare the bytes. The invoice is the most
 * arithmetic-heavy page in the application - every tax, discount and rounding
 * path runs through it - so byte-identical output across PHP 5.6 and 8.3 is the
 * single strongest signal that the upgrade changed no money.
 *
 * It renders the view rather than producing a PDF, because mPDF embeds a
 * creation timestamp that would differ on every run. Bootstraps through
 * common.php, exactly as index.php does, so it sees the same config, the same
 * DB attributes and the same autoloader as a real request.
 */
if (PHP_SAPI !== 'cli') {
    exit("CLI only\n");
}

$ids = isset($argv[1]) ? array_filter(array_map('trim', explode(',', $argv[1]))) : array();
if (!$ids) {
    exit("usage: php __dumphtml.php <id,id,...>\n");
}

// Sets $yii, $config, YII_ENV and DB_CONFIG_FILE_PATH - same as index.php.
require dirname(__FILE__) . '/common.php';
require_once $yii;
Yii::createWebApplication($config);

$outDir = '/tmp/billhtml';
if (!is_dir($outDir)) {
    mkdir($outDir, 0777, true);
}
foreach (glob($outDir . '/*.html') as $stale) {
    unlink($stale);
}

// renderPartial needs a controller for the view path and the widget stack.
$controller = new CController('order');
Yii::app()->setController($controller);

$written = 0;
foreach ($ids as $id) {
    $order = Order::model()->findByPk($id);
    if (!$order) {
        fwrite(STDERR, "order $id not found\n");
        continue;
    }
    $html = $controller->renderPartial(
        'application.views.order._billpdf',
        array('order' => $order),
        true
    );
    file_put_contents($outDir . '/' . $id . '.html', $html);
    $written++;
}
echo "wrote $written of " . count($ids) . " to $outDir\n";
