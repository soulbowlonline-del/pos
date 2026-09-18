<?php
namespace app\controllers;

use Yii;
use yii\web\Controller;
use yii\web\Response;

/**
 * Health and diagnostics for the Yii 2 side. Proves the framework boots, the
 * database is reachable and the Yii 1 session bridge is working, without
 * depending on any ported business logic.
 */
class SiteController extends Controller
{
    public $enableCsrfValidation = false;

    /**
     * GET /v2/
     *
     * A directory of what this application serves. There is no user interface
     * here - the Yii 2 side is the API port and nothing else - and landing on a
     * bare 404 gives no way to find out what does work, which is what this
     * answers.
     *
     * The action list is read from the controllers themselves rather than
     * written out here, so it cannot drift from what is actually routed.
     */
    public function actionIndex()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $controllers = ['customer', 'emp', 'item', 'loyalty', 'order', 'tally'];
        $endpoints = [];

        foreach ($controllers as $id) {
            $class = 'app\\controllers\\' . ucfirst($id) . 'Controller';
            if (!class_exists($class)) {
                continue;
            }
            $actions = [];
            foreach (get_class_methods($class) as $method) {
                if (strpos($method, 'action') !== 0 || $method === 'actions') {
                    continue;
                }
                $name = substr($method, 6);
                // Yii 2 routes actions hyphenated: actionGetOnlineOrder is
                // reached at get-online-order, where Yii 1 uses getOnlineOrder.
                $route = strtolower(preg_replace('/([a-z0-9])([A-Z])/', '$1-$2', $name));
                $actions[] = $route;
            }
            sort($actions);
            $endpoints['/v2/api/' . $id . '/'] = $actions;
        }

        return [
            'ok' => true,
            'what' => 'The Yii 2 port of the DASPOS API. No user interface lives here.',
            'the_web_ui_is_at' => '/ on this same host and port, served by Yii 1 on PHP ' . PHP_VERSION,
            'yii1_api_is_at' => '/api/<controller>/<action>, still serving every route',
            'note' => 'Yii 1 action ids are camelCase; these are hyphenated. '
                    . '/api/order/getOnlineOrder is /v2/api/order/get-online-order.',
            'health' => '/v2/health',
            'endpoints' => $endpoints,
        ];
    }

    public function actionHealth()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;

        $db = null;
        try {
            $db = Yii::$app->db->createCommand('SELECT VERSION()')->queryScalar();
        } catch (\Throwable $e) {
            $db = 'ERROR: ' . $e->getMessage();
        }

        $bridge = Yii::$app->user->readBridge();
        $identity = Yii::$app->user->getIdentity();

        return [
            'ok' => true,
            'framework' => 'Yii ' . Yii::getVersion(),
            'php' => PHP_VERSION,
            'mysql' => $db,
            'session' => [
                'name' => session_name(),
                'id' => session_id() ?: null,
            ],
            'auth' => [
                'bridged' => $bridge !== null,
                'yii1_user_id' => $bridge['id'] ?? null,
                'yii1_user_name' => $bridge['name'] ?? null,
                'yii2_identity' => $identity ? $identity->getId() : null,
                'is_guest' => Yii::$app->user->getIsGuest(),
            ],
        ];
    }

    public function actionError()
    {
        Yii::$app->response->format = Response::FORMAT_JSON;
        $ex = Yii::$app->errorHandler->exception;
        Yii::$app->response->statusCode = ($ex instanceof \yii\web\HttpException) ? $ex->statusCode : 500;
        return [
            'ok' => false,
            'error' => $ex ? $ex->getMessage() : 'Unknown error',
            'type' => $ex ? get_class($ex) : null,
        ];
    }
}
