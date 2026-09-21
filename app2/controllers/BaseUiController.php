<?php
namespace app\controllers;

use app\components\ExportsGrid;
use app\components\Ui;
use app\widgets\Tabs;
use Yii;
use yii\web\Controller;
use yii\helpers\Html;
use yii\web\ForbiddenHttpException;
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

    /**
     * Off, because Yii 1 does not validate it either.
     *
     * CWebApplication leaves enableCsrfValidation off unless the config turns
     * it on, this application's config does not, and no view in the Yii 1 tree
     * emits a token. The application's javascript therefore posts without one
     * - and it is the same javascript here, served from the same theme.
     *
     * With validation on, every ajax POST in the ported UI answered 400
     * "Unable to verify your data submission": the bill-number lookup, the
     * item lookup, the PO lookup, the inline grid edits. The page rendered and
     * nothing on it worked, on screen after screen.
     *
     * This is a real reduction in security against the Yii 2 default, and it
     * is deliberate: the port's job is to behave as Yii 1 behaves, and Yii 1
     * has no CSRF protection anywhere. Adding it is worth doing, but it is a
     * change to the application rather than to the port - every form and every
     * ajax call needs a token before validation can be turned on, on both
     * stacks at once, or the two stop agreeing.
     */
    public $enableCsrfValidation = false;

    /**
     * Actions Yii 1's accessRules() refuses to a signed-in user.
     *
     * Empty here; a generated controller overrides it where its rules refuse
     * something. Kept as a method rather than a property so that a hand-edited
     * controller can compute it.
     */
    public function deniedActions()
    {
        return [];
    }

    /**
     * Actions Yii 1's accessRules() allows a guest, by lower-case action id.
     *
     * Empty here, because every ported controller but one requires a signed-in
     * user. UserController overrides it: its first rule lists the actions
     * granted to `'users' => array('*')`, and the login form is among them.
     * Without this hook the guard below sent a guest asking for the port's own
     * login page to Yii 1's, so /v2/user/login could never render - it
     * answered 302 to /user/login, whoever asked.
     */
    public function guestActions()
    {
        return [];
    }

    public function beforeAction($action)
    {
        if (!parent::beforeAction($action)) {
            return false;
        }

        Yii::$app->response->format = Response::FORMAT_HTML;

        if (Yii::$app->user->getIsGuest()
                && !in_array(strtolower($action->id), array_map('strtolower',
                                                                $this->guestActions()), true)) {
            // Yii 1's loginUrl is user/login, and the port has one of its own
            // now; a guest asking for a page that needs a session is sent to
            // whichever application they were already using.
            $login = strpos(Yii::$app->request->getUrl(), '/v2/') === 0
                ? '/v2/user/login' : '/user/login';
            Yii::$app->response->redirect($login)->send();
            return false;
        }

        // CController::getPageTitle()'s default, for the actions that never
        // call processSEO(). Yii 1 computes it lazily from the controller and
        // action ids - "DASPOS - About Site" for site/about - and the theme
        // layout prints it. Without this the port's <title> was empty on every
        // such page, which the UI suite never looks at: it compares grid rows,
        // detail pairs and form fields, not the head.
        //
        // Set before the action runs, so processSEO() still overrides it where
        // a controller calls it, exactly as Yii 1's setter overrides the
        // lazily computed default.
        $name = ucfirst(Ui::toYii1Id($this->id));
        $act = Ui::toYii1Id($action->id);
        $this->view->title = strcasecmp($act, $this->defaultAction) !== 0
            ? Yii::$app->name . ' - ' . ucfirst($act) . ' ' . $name
            : Yii::$app->name . ' - ' . $name;

        // Yii 1's accessRules(), for the actions it refuses outright.
        //
        // The port checks only that someone is signed in, on the reading that
        // Yii 1's rules amount to the same thing - and for 43 of the 48 ported
        // controllers they do, because their first rule carries no action list
        // and CAccessRule matches every action when the list is empty. The
        // exceptions are real: item/getDiffStocks is refused to everybody
        // there, and was reachable here.
        //
        // The list is generated per controller from accessRules() and holds
        // only actions refused outright; anything a role or an expression
        // decides is left out rather than guessed at, so this can refuse less
        // than Yii 1 but never more.
        if (in_array(Ui::toYii1Id($action->id), $this->deniedActions(), true)) {
            throw new ForbiddenHttpException('You are not allowed to access this page.');
        }

        // Everything the action prints is captured rather than sent. See
        // afterAction().
        ob_start();
        $this->buffering = true;

        return true;
    }

    /** Whether beforeAction opened an output buffer for this request. */
    private $buffering = false;

    /**
     * Returns whatever the action printed, instead of letting it escape.
     *
     * Seventy-two ported actions write their answer with `echo` and return
     * nothing - `echo $option;` for a dropdown, `echo json_encode($data);` for
     * a tax lookup, `echo $bar_code;`. That is how Yii 1 is written and it is
     * correct there: CController sends nothing of its own afterwards.
     *
     * Yii 2 does. The action returns null, the framework sends its own empty
     * response, and sending it means sending headers - after the echo has
     * already started the body. The result was a HeadersAlreadySentException
     * and the string "An internal server error occurred." appended to the
     * answer. The purchase bill's PO dropdown arrived correct and with that
     * sentence glued to the end of it, which the page then put on screen.
     *
     * Capturing the output here turns an echoing action into an ordinary Yii 2
     * one without touching any of the seventy-two. An action that both prints
     * and returns keeps both, in the order they happened.
     */
    public function afterAction($action, $result)
    {
        if ($this->buffering) {
            $this->buffering = false;
            $printed = ob_get_clean();
            if ($printed !== '' && $printed !== false) {
                $result = ($result === null || $result === '')
                    ? $printed
                    : $printed . $result;
            }
        }

        return parent::afterAction($action, $result);
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
        $short = substr($short, 0, -strlen('Controller'));

        // ItemUiController edits Item: the suffix distinguishes the controller
        // from the API one of the same name, not the model.
        if (substr($short, -2) === 'Ui') {
            $short = substr($short, 0, -2);
        }

        return 'app\\models\\' . $short;
    }

    /**
     * Port of GxController::loadModel(): the row, or a 404.
     *
     * The message is Yii 1's, because it is what the error page shows.
     */
    /**
     * Yii 1's GxController::loadModel($id, $modelClass).
     *
     * The class argument is not decoration. ItemDetail's update action loads
     * an Item by the row's item_id with it, and dropping it looked up an
     * ItemDetail of that id instead: the page answered 404 where Yii 1
     * answers 403.
     */
    public function loadModel($id, $class = null)
    {
        $class = $class ?: $this->modelClass();
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
