<?php
/**
 * Yii 2 application config.
 *
 * This application is served from /v2 and runs alongside the Yii 1 application,
 * which still owns every other route. They share a container, a session and a
 * database; they do not share a process - each request is handled by exactly
 * one framework, which avoids the class collision between Yii 1's Yii and
 * Yii 2's yii\BaseYii entirely.
 */
// The Yii 1 parameters, read from the file Yii 1 reads, so the two cannot
// drift. item/adjust branches on saleStatus, and a copy of that value here
// would be a second source of truth for something an operator changes.
$params = require dirname(dirname(__DIR__)) . '/config/params.php';
$params['adminEmail'] = 'admin@daspos.com';

return [
    'id' => 'pos-v2',
    'name' => 'DASPOS',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'timeZone' => 'Asia/Kolkata',
    'aliases' => [
        '@app' => dirname(__DIR__),
        // Yii 2's own @webroot is the directory of this front controller, /v2.
        // The Yii 1 application reads and writes uploads under the web root
        // one level up, and ported actions have to use that same directory or
        // a file written by one stack would be invisible to the other.
        '@legacyroot' => dirname(dirname(__DIR__)),
    ],
    'components' => [
        'request' => [
            // Only used for Yii 2's own CSRF/cookie signing. It is read from the
            // environment so it is not committed; the entry script fails loudly
            // if it is missing.
            'cookieValidationKey' => getenv('POS_V2_COOKIE_KEY'),
            'baseUrl' => '/v2',
        ],
        'user' => [
            'class' => 'app\components\BridgedUser',
        ],
        'session' => [
            // Same PHP session as Yii 1: same container, same save path, same
            // cookie name. Yii 2 must not regenerate or rename it.
            'class' => \yii\web\Session::class,
        ],
        'db' => require __DIR__ . '/db.php',
        'urlManager' => [
            'enablePrettyUrl' => true,
            'showScriptName' => false,
            'baseUrl' => '/v2',
            'rules' => [
                // the bare /v2 root: a directory of what is served here, so a
                // visitor does not land on an unexplained 404
                '' => 'site/index',
                'GET health' => 'site/health',
                // Ported from the Yii 1 api module. Yii 1 still serves
                // /api/<controller>/* for everything not yet moved across.
                // Yii 1 action ids are camelCase and Yii 2 routes them
                // hyphenated, so /v2/api/loyalty/pre-redeem-points answers
                // what Yii 1 serves at /api/loyalty/preRedeemPoints.
                'POST api/loyalty/<action:[\w-]+>' => 'loyalty/<action>',
                'POST api/emp/<action:[\w-]+>' => 'emp/<action>',
                // No method prefix: the Yii 1 routes answer GET and POST alike,
                // and several of these take an id from the query string.
                'api/customer/<action:[\w-]+>' => 'customer/<action>',
                'api/order/<action:[\w-]+>' => 'order/<action>',
                'api/tally/<action:[\w-]+>' => 'tally/<action>',
                'api/item/<action:[\w-]+>' => 'item/<action>',
            ],
        ],
        'response' => [
            'format' => \yii\web\Response::FORMAT_JSON,
            'formatters' => [
                \yii\web\Response::FORMAT_JSON => [
                    'class' => \yii\web\JsonResponseFormatter::class,
                    // Yii 1 encoded with a bare json_encode(), which escapes
                    // slashes and unicode. Yii 2 defaults to
                    // JSON_UNESCAPED_SLASHES|JSON_UNESCAPED_UNICODE, so the same
                    // payload would serialise to different bytes. 0 restores the
                    // Yii 1 encoding exactly.
                    'encodeOptions' => 0,
                    'prettyPrint' => false,
                ],
            ],
        ],
        'errorHandler' => [
            'errorAction' => 'site/error',
        ],
        'log' => [
            'traceLevel' => 0,
            'targets' => [
                [
                    'class' => \yii\log\FileTarget::class,
                    'levels' => ['error', 'warning'],
                ],
            ],
        ],
    ],
    'params' => $params,
];
