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
        // The web root for Yii 2's own purposes is /v2, where the entry script
        // lives; published assets land in /v2/assets and are served from there.
        '@webroot' => dirname(dirname(__DIR__)) . '/v2',
        '@web' => '/v2',
        // composer.json installs asset packages under vendor/bower-asset, not
        // the vendor/bower that Yii 2 assumes by default.
        '@bower' => dirname(dirname(__DIR__)) . '/vendor/bower-asset',
        '@npm' => dirname(dirname(__DIR__)) . '/vendor/npm-asset',
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
        'formatter' => [
            // Yii 1's grids and detail views render a null as an empty cell.
            // Yii 2's default is the literal "(not set)", which would show up
            // in every column that can be null.
            'nullDisplay' => '',
        ],
        'assetManager' => [
            'basePath' => '@webroot/assets',
            'baseUrl' => '@web/assets',
            // Into the head, which is where Yii 1's CClientScript puts its
            // core scripts. Yii 2 registers a bundle at the end of the body
            // by default, and the theme's own script tags are hard-coded
            // above that in the layout - so jQuery arrived *after* the twelve
            // plugins that need it and after AdminLTE's app.min.js. Every one
            // of them threw on `$`, app.min.js never ran, and the sidebar
            // menu did not open: the markup was right, the 80 links were all
            // there, and nothing was listening for the click.
            'bundles' => [
                // jQuery 1.12.4, the same file Yii 1 publishes from
                // framework/web/js/source, rather than the 3.7.1 composer
                // resolves for yii2.
                //
                // The theme's bootstrap.js - itself a copy of Yii 1's, for the
                // same reason, see the layout - reads an anchor's href as a
                // selector in getParent(). For `href="#"` that selector is
                // "#", which 1.12.4 answers with an empty set and 3.7.1
                // rejects: "Syntax error, unrecognized expression: #". It
                // throws inside the click handler, so the dropdown never
                // opens. 33 such anchors on mrsDetail/admin alone, and every
                // dropdown and toggle in the theme is built this way.
                //
                // Pinned rather than patched: bootstrap is not the only caller
                // of a Yii 1-era jQuery idiom in this tree, and the port's
                // whole premise is to behave as Yii 1 does. Yii 2 supports
                // jQuery >= 1.11, so its own yii.js, yii.activeForm.js and
                // yii.gridView.js are in spec on this version.
                //
                // sourcePath null because the file is served from the /v2
                // docroot, not published from vendor - framework/ is 403.
                \yii\web\JqueryAsset::class => [
                    'sourcePath' => null,
                    'js' => ['/v2/js/jquery.js'],
                    'jsOptions' => ['position' => \yii\web\View::POS_HEAD],
                ],
                \yii\web\YiiAsset::class => [
                    'jsOptions' => ['position' => \yii\web\View::POS_HEAD],
                ],
            ],
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
                //
                // This was six string rules, one per controller, passing
                // <action> through untouched - so the port answered only the
                // hyphenated spelling Yii 2 prefers, and every multi-word
                // action in the API was a 404 for anything built against
                // Yii 1: countryList, getLatestBill, getLastOrder,
                // preRedeemPoints and the rest. The .NET application and the
                // Android app could not call it at all. ApiUrlRule converts
                // the action id the way LegacyUrlRule already did for the web
                // UI, and accepts both spellings; it keeps loyalty and emp
                // POST-only, as the rules it replaces did.
                ['class' => \app\components\ApiUrlRule::class],

                // The ported web-UI controllers, at the same paths Yii 1 uses.
                // Listed after the API rules so those still win. The rule only
                // answers for controllers in Ui::PORTED, so an unported path
                // still 404s rather than resolving to something odd.
                ['class' => \app\components\LegacyUrlRule::class],
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
