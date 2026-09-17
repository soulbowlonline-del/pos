<?php
// --- Load secrets from .env (gitignored) into the environment, so config files
//     can read them via getenv() instead of hardcoding them. Real environment
//     variables (e.g. from docker-compose) take precedence over the file. ---
$__envFile = dirname(__FILE__) . '/.env';
if (is_file($__envFile)) {
    foreach (file($__envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $__line) {
        $__line = trim($__line);
        if ($__line === '' || $__line[0] === '#' || strpos($__line, '=') === false) {
            continue;
        }
        list($__k, $__v) = explode('=', $__line, 2);
        $__k = trim($__k);
        $__v = trim($__v);
        if (strlen($__v) >= 2 && ($__v[0] === '"' || $__v[0] === "'") && substr($__v, -1) === $__v[0]) {
            $__v = substr($__v, 1, -1);
        }
        if (getenv($__k) === false) {
            putenv($__k . '=' . $__v);
            $_ENV[$__k] = $__v;
        }
    }
    unset($__line, $__k, $__v);
}
unset($__envFile);

// --- Composer autoloader ---------------------------------------------------
// Third-party libraries (PHPMailer 6, mPDF 8, PhpSpreadsheet) are managed by
// Composer. Nothing previously required this file, so vendor/ was unreachable;
// the old bundled copies under protected/extensions and ext-prod were used
// instead. Registering it here - before Yii boots - makes the modern packages
// available to the whole application.
$__autoload = dirname(__FILE__) . '/vendor/autoload.php';
if (is_file($__autoload)) {
    require_once $__autoload;
}
unset($__autoload);

date_default_timezone_set('Asia/Calcutta');
defined('YII_ENV') or define('YII_ENV','prod');

//db config path setting
defined('UPLOAD_PATH') or define('UPLOAD_PATH','/wdir/uploads/');
defined('IMAGE_UPLOAD_PATH') or define('IMAGE_UPLOAD_PATH','/wdir/uploads/images/');

defined('DB_CONFIG_FILE_PATH') or define('DB_CONFIG_FILE_PATH', dirname(__FILE__).'/config/'. YII_ENV .'-db.php');
define('CACHE_TIME', 1);
// create directories if required
if ( !file_exists(dirname(__FILE__). UPLOAD_PATH)) mkdir( dirname(__FILE__). UPLOAD_PATH, 0777, true);
if ( !file_exists(dirname(__FILE__). '/assets')) mkdir( dirname(__FILE__). '/assets', 0777, true);
if ( !file_exists(dirname(__FILE__). '/wdir/runtime')) mkdir( dirname(__FILE__). '/wdir/runtime', 0777, true);

// change the following paths if necessary
$yii = dirname(__FILE__).'/framework/yii.php';
$config = dirname(__FILE__).'/config/'. YII_ENV .'.php';


if ( YII_ENV == 'dev')
{
	error_reporting(E_ALL);
	ini_set("display_errors", 1); 

	// remove the following lines when in production mode
	defined('YII_DEBUG') or define('YII_DEBUG',true);
	// specify how many levels of call stack should be shown in each log message
	defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL',3);
}
else if ( YII_ENV == 'test')
{
	error_reporting(E_ALL);
	ini_set("display_errors", 1); 

	// remove the following line when in production mode
	defined('YII_DEBUG') or define('YII_DEBUG',true);	

}
else
{
error_reporting(E_ALL);
	ini_set("display_errors", 1); 

	// remove the following lines when in production mode
	defined('YII_DEBUG') or define('YII_DEBUG',true);
	// specify how many levels of call stack should be shown in each log message
	defined('YII_TRACE_LEVEL') or define('YII_TRACE_LEVEL',3);
	// change the following paths if necessary
$yii = dirname(__FILE__).'/framework/yiilite.php';

}
