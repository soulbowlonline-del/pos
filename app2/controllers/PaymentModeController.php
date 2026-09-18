<?php
namespace app\controllers;

use app\components\Ui;
use app\models\PaymentMode;
use Yii;
use yii\data\ActiveDataProvider;
use yii\web\BadRequestHttpException;

/**
 * Yii 2 port of protected/controllers/PaymentModeController.php.
 *
 * The first web-UI controller ported, and the template the rest follow. It is
 * the plain Gii CRUD shape that 40-odd of the 59 controllers share: index,
 * admin (the filterable grid), view, create, update, delete, search.
 *
 * Yii 1 still serves /paymentMode/*. This serves /v2/paymentMode/*, and
 * Ui::PORTED is what decides which one the rest of the UI links to.
 *
 * Access matches Yii 1's accessRules(): signed in is the only requirement, for
 * every action. checkPermission() decides what the sidebar and the row buttons
 * offer, and it is not a gate on the URL in Yii 1 either - see
 * docs/live-bugs-found.md, which records that as found rather than fixed.
 */
class PaymentModeController extends BaseUiController
{
    public function actionIndex()
    {
        $this->updateMenuItems();

        return $this->render('index', [
            'dataProvider' => new ActiveDataProvider([
                'query' => PaymentMode::find(),
                // GxActiveRecord::defaultScope() orders every model by
                // id DESC, so Yii 1's list is newest first even though this
                // provider names no sort.
                'sort' => ['defaultOrder' => ['id' => SORT_DESC]],
                'pagination' => ['pageSize' => Ui::PAGE_SIZE],
            ]),
        ]);
    }

    /** The filterable grid. The view calls $model->search(), as in Yii 1. */
    public function actionAdmin()
    {
        $model = new PaymentMode(['scenario' => 'search']);
        $this->updateMenuItems($model);
        $model->load(Yii::$app->request->queryParams);

        return $this->render('admin', ['model' => $model]);
    }

    /** The ajax half of the grid: the filter form, and the rows when filtered. */
    public function actionSearch()
    {
        $model = new PaymentMode(['scenario' => 'search']);
        $this->updateMenuItems($model);

        $out = '';
        if (Yii::$app->request->get($model->formName()) !== null) {
            $model->load(Yii::$app->request->queryParams);
            $out .= $this->renderPartial('_list', [
                'dataProvider' => $model->search(),
                'model' => $model,
            ]);
        }

        // Yii 1 renders both partials on a filtered request - renderPartial
        // there does not stop the action.
        return $out . $this->renderPartial('_search', ['model' => $model]);
    }

    public function actionView($id)
    {
        $model = $this->loadModel($id);
        $this->updateMenuItems($model);

        return $this->render('view', ['model' => $model]);
    }

    public function actionCreate()
    {
        $model = new PaymentMode();

        if ($model->load(Yii::$app->request->post())) {
            // Yii 1 takes create_user_id from the form, where it is a hidden
            // field. Set here instead: the signed-in user is the one creating
            // the row, and a posted value should not be able to say otherwise.
            $model->create_user_id = Yii::$app->user->id;
            if ($model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        $this->updateMenuItems($model);
        return $this->render('create', ['model' => $model]);
    }

    public function actionUpdate($id)
    {
        $model = $this->loadModel($id);

        if ($model->load(Yii::$app->request->post())) {
            $model->updated_by = Yii::$app->user->id;
            if ($model->save()) {
                return $this->redirect(['view', 'id' => $model->id]);
            }
        }

        $this->updateMenuItems($model);
        return $this->render('update', ['model' => $model]);
    }

    /** POST only, as in Yii 1 - a GET is a bad request, not a deletion. */
    public function actionDelete($id)
    {
        if (!Yii::$app->request->isPost) {
            throw new BadRequestHttpException('Your request is invalid.');
        }

        $this->loadModel($id)->delete();

        if (!Yii::$app->request->isAjax) {
            return $this->redirect(['admin']);
        }
        return '';
    }

    /**
     * Port of Yii 1's updateMenuItems(): the buttons in the page header.
     *
     * The case labels and their contents are the original's, including the
     * missing break after 'update' - an update page offers View, Manage and
     * List, in that order - and including 'view' sharing the default branch.
     * No permission filtering: Yii 1 offers every button to every signed-in
     * user here.
     */
    protected function updateMenuItems($model = null)
    {
        if ($model === null) {
            $model = new PaymentMode();
        }

        switch ($this->action->id) {
            case 'update':
                $this->menu[] = ['label' => 'View', 'url' => $this->uiUrl('paymentMode/view', ['id' => $model->id]), 'icon' => 'icon-plus icon-white'];
                // falls through, as in Yii 1
            case 'create':
                $this->menu[] = ['label' => 'Manage', 'url' => $this->uiUrl('paymentMode/admin'), 'icon' => 'icon-wrench icon-white'];
                $this->menu[] = ['label' => 'List', 'url' => $this->uiUrl('paymentMode/index'), 'icon' => 'icon-th-list icon-white'];
                break;
            case 'index':
                $this->menu[] = ['label' => 'Manage', 'url' => $this->uiUrl('paymentMode/admin'), 'icon' => 'icon-wrench icon-white'];
                $this->menu[] = ['label' => 'Create', 'url' => $this->uiUrl('paymentMode/create'), 'icon' => 'icon-plus icon-white'];
                break;
            case 'admin':
                $this->menu[] = ['label' => 'Create', 'url' => $this->uiUrl('paymentMode/create'), 'icon' => 'icon-plus icon-white'];
                break;
            case 'view':
            default:
                $this->menu[] = ['label' => 'Manage', 'url' => $this->uiUrl('paymentMode/admin'), 'icon' => 'icon-wrench icon-white'];
                $this->menu[] = ['label' => 'Create', 'url' => $this->uiUrl('paymentMode/create'), 'icon' => 'icon-plus icon-white'];
                $this->menu[] = ['label' => 'Update', 'url' => $this->uiUrl('paymentMode/update', ['id' => $model->id]), 'icon' => 'icon-edit icon-white'];
                break;
        }

        $this->processSEO($model);
        $this->actions = array_merge($this->actions, $this->menu);
    }
}
