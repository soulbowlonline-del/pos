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

        return Html::tag($this->htmlTag, $list, ArrayHelper::getValue($this->htmlOptions, 'id')
            ? ['id' => $this->htmlOptions['id']] : []);
    }
}
