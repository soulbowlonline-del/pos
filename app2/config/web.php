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
$params = [
    'adminEmail' => 'admin@daspos.com',
];

return [
    'id' => 'pos-v2',
    'name' => 'DASPOS',
    'basePath' => dirname(__DIR__),
    'bootstrap' => ['log'],
    'timeZone' => 'Asia/Kolkata',
    'aliases' => [
        '@app' => dirname(__DIR__),
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
                'GET health' => 'site/health',
                // Ported from the Yii 1 api module. Yii 1 still serves
                // /api/<controller>/* for everything not yet moved across.
                // Yii 1 action ids are camelCase and Yii 2 routes them
                // hyphenated, so /v2/api/loyalty/pre-redeem-points answers
                // what Yii 1 serves at /api/loyalty/preRedeemPoints.
                'POST api/loyalty/<action:[\w-]+>' => 'loyalty/<action>',
                'POST api/emp/<action:[\w-]+>' => 'emp/<action>',
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
