<?php
namespace app\controllers;

use app\components\Ui;
use Yii;
use yii\web\Controller;
use yii\helpers\Html;
use yii\web\Response;

/**
 * Base for the ported web-UI controllers.
 *
 * The API controllers return arrays and let Yii 2 encode them as JSON. These
 * render HTML instead, so they need a layout and a logged-in user.
 *
 * Login stays with Yii 1: this reads the session Yii 1 wrote, through
 * BridgedUser. There is deliberately no login form here - two applications
 * issuing sessions for the same cookie would be a way to get subtly different
 * answers about who is signed in.
 */
abstract class BaseUiController extends Controller
{
    public $layout = 'main';

    /** Breadcrumb trail, as Yii 1's $this->breadcrumbs. */
    public $breadcrumbs = [];

    /** Action buttons for the page header, as Yii 1's $this->menu. */
    public $menu = [];

    /**
     * Yii 1's $this->actions - the same items as $menu plus anything a
     * controller's processActions() added. The views render one or the other,
     * so both have to exist.
     */
    public $actions = [];

    /** Yii 1's page-level SEO fields, set by processSEO(). */
    public $pageCaption;
    public $pageDescription;
    public $pageKeywords;

    public $enableCsrfValidation = true;

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        Yii::$app->response->format = Response::FORMAT_HTML;

        if (Yii::$app->user->getIsGuest()) {
            // Yii 1 owns the login form, so send them there rather than
            // rendering one here. loginUrl in config/main.php is user/login.
            Yii::$app->response->redirect('/user/login')->send();
            return false;
        }

        return true;
    }

    /**
     * Yii 1 keeps its views under the controller's own camelCase name -
     * protected/views/paymentMode - while Yii 2 derives the directory from the
     * hyphenated controller id and would look in views/payment-mode. Point it
     * back at the Yii 1 spelling so the ported views sit at the same relative
     * paths as the originals and stay diffable against them.
     */
    public function getViewPath()
    {
        return $this->module->getViewPath() . DIRECTORY_SEPARATOR . Ui::toYii1Id($this->id);
    }

    /**
     * Port of Controller::processSEO(). Sets the page caption and title from
     * the model: its own label for a saved row, the label plus the action name
     * for a new one.
     */
    protected function processSEO($model)
    {
        if ($model && !$model->isNewRecord) {
            if ($model->hasAttribute('id')) {
                $this->pageCaption = Html::encode($model->label()) . '' . Html::encode((string) $model);
            }
            $this->view->title = $this->pageCaption;
            if ($model->hasAttribute('content')) {
                $this->pageDescription = substr(strip_tags($model->content), 0, 150);
            }
        } else {
            $this->pageCaption = Html::encode($model->label() . '' . $this->action->id);
            $this->view->title = $this->pageCaption;
        }
    }

    /** Link helper, so views do not have to know which stack serves a route. */
    public function uiUrl($route, $params = [])
    {
        return Ui::to($route, $params);
    }
}
