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
