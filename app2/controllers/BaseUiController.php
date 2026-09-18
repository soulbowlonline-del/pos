<?php
namespace app\controllers;

use app\components\ExportsGrid;
use app\components\Ui;
use app\widgets\Tabs;
use Yii;
use yii\web\Controller;
use yii\helpers\Html;
use yii\web\NotFoundHttpException;
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
    // GxController got these from ExportableGridBehavior; the admin actions
    // call isExportRequest() and exportCSV() directly.
    use ExportsGrid;

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

    /**
     * The model class this controller edits.
     *
     * Yii 1 passed the class name to loadModel() at each call site -
     * CityController::loadModel($id, 'City'). Deriving it from the controller
     * name holds for every CRUD controller here; one that edits something else
     * overrides this.
     */
    public function modelClass()
    {
        $short = (new \ReflectionClass($this))->getShortName();

        return 'app\\models\\' . substr($short, 0, -strlen('Controller'));
    }

    /**
     * Port of GxController::loadModel(): the row, or a 404.
     *
     * The message is Yii 1's, because it is what the error page shows.
     */
    public function loadModel($id)
    {
        $class = $this->modelClass();
        $model = $class::findOne($id);
        if ($model === null) {
            throw new NotFoundHttpException('The requested page does not exist.');
        }

        return $model;
    }

    /** @var string GxController's tab-panel state. */
    public $tabs_name = 'tabpanel1';

    /** @var array the tabs built up between StartPanel() and EndPanel(). */
    public $tabs_data = [];

    /** Port of GxController::StartPanel(). */
    public function StartPanel($name = 'tabpanel1')
    {
        $this->tabs_name = $name;
        $this->tabs_data = [];
    }

    /**
     * Port of GxController::AddPanel(): one tab per relation, rendered from
     * that model's _list partial, and a matching "Add <thing>" menu item.
     *
     * A relation with no rows contributes no tab, as in Yii 1 - which is why
     * a view page's tab strip changes shape with the data.
     */
    public function AddPanel($title, $objects, $relations, $view,
                             $partialview = '_list', $model = null, $addMenu = true)
    {
        if ($addMenu) {
            $this->menu[] = [
                'label' => 'Add ' . $title,
                'url' => Ui::to($view . '/create', $model ? ['id' => $model->id] : []),
                'icon' => 'icon-plus icon-white',
            ];
        }

        if (!$objects) {
            return;
        }

        $dataProvider = $objects instanceof \yii\data\DataProviderInterface
            ? $objects
            : new \yii\data\ArrayDataProvider(['allModels' => $objects]);

        if ($dataProvider->getCount()) {
            $content = $this->renderPartial('/' . $view . '/' . $partialview,
                ['dataProvider' => $dataProvider]);
            $this->tabs_data[] = [
                'label' => $title,
                'content' => $content,
                'active' => count($this->tabs_data) === 0,
            ];
        }
    }

    /**
     * Port of GxController::EndPanel(). Yii 1's version echoes the widget from
     * inside the controller, and the views call it as a statement, so this
     * echoes too rather than returning.
     */
    public function EndPanel()
    {
        echo Tabs::widget([
            'type' => 'tabs',
            'tabs' => $this->tabs_data,
            'htmlOptions' => ['class' => 'tabbable tabs-left well'],
        ]);
    }

    /**
     * Port of GxController::performAjaxValidation().
     *
     * Yii 1 answered an ajax validation POST with the error JSON and ended the
     * request before the action could save anything. Yii 2's ActiveForm posts
     * the same way, so the behaviour is kept.
     */
    protected function performAjaxValidation($model, $form = null)
    {
        $request = Yii::$app->request;
        if ($request->isAjax && ($form === null || $request->post('ajax') === $form)) {
            Yii::$app->response->format = Response::FORMAT_JSON;
            Yii::$app->response->data = \yii\widgets\ActiveForm::validate($model);
            Yii::$app->response->send();
            Yii::$app->end();
        }
    }

    /**
     * Port of GxController::richTextEditor(): which editor the forms pick.
     *
     * The value is a constant 3 in Yii 1, which selects CKEditor. This port
     * renders the plain textarea underneath either editor, so the number only
     * decides which branch of the view runs; it is kept the same so the same
     * branch runs.
     */
    public function richTextEditor()
    {
        return 3;
    }

    /** Link helper, so views do not have to know which stack serves a route. */
    public function uiUrl($route, $params = [])
    {
        return Ui::to($route, $params);
    }
}
