<?php
namespace app\widgets;

use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Port of protected/extensions/widgets/CJuiRadioButtonList.
 *
 * 119 views use it. The jQuery UI buttonset styling is not part of this port;
 * what it actually emits is an active radio button list inside a div, with the
 * labels displayed inline, and that is reproduced.
 */
class CJuiRadioButtonList extends Widget
{
    use IgnoresLegacyOptions;

    public $model;
    public $attribute;
    public $data = [];
    public $htmlTag = 'div';
    public $htmlOptions = [];

    public function run()
    {
        $options = $this->htmlOptions;

        // 'rows' stacks the options; anything else keeps them on one line.
        $style = ArrayHelper::remove($options, 'displayStyle') === 'rows'
            ? 'display:block'
            : 'display:inline-block';
        $options['separator'] = '';
        $options['itemOptions'] = [];
        $options['labelOptions'] = ['style' => $style];

        $list = $this->model !== null
            ? Html::activeRadioList($this->model, $this->attribute, $this->data, $options)
            : Html::radioList(ArrayHelper::getValue($options, 'name', ''), null, $this->data, $options);

        $id = ArrayHelper::getValue($this->htmlOptions, 'id');
        if ($id === null) {
            $id = $this->model !== null
                ? Html::getInputId($this->model, $this->attribute)
                : $this->getId();
        }

        // Yii 1's widget is a CJuiInputWidget: it loads jQuery UI and calls
        // buttonset() on the group, which is what turns the radios into the
        // joined button bar the search panels show. The port rendered plain
        // radios and bound nothing.
        //
        // Unlike the typeahead this is styling rather than function - a plain
        // radio still selects - which is why it was never reported as broken,
        // only as not looking right.
        $view = $this->getView();
        $view->registerCssFile('/v2/css/jquery-ui-bootstrap.css');
        $view->registerJsFile('/v2/js/jquery-ui.min.js',
                              ['depends' => \yii\web\JqueryAsset::class]);
        $view->registerJs(sprintf("jQuery('#%s').buttonset();", $id));

        return Html::tag($this->htmlTag, $list, ['id' => $id]);
    }
}
