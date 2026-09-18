<?php
namespace app\widgets;

use app\components\Gx;
use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/** Stand-in for bootstrap.widgets.TbMenu. */
class Menu extends Widget
{
    /** 'tabs' or 'pills' */
    public $type = 'pills';
    public $stacked = false;
    public $items = [];
    public $htmlOptions = [];

    public function run()
    {
        $lis = [];
        foreach ($this->items as $item) {
            if (isset($item['visible']) && !$item['visible']) {
                continue;
            }
            $url = Gx::url(ArrayHelper::getValue($item, 'url', '#'));
            $lis[] = Html::tag('li',
                Html::a(Html::encode(ArrayHelper::getValue($item, 'label', '')), $url),
                ArrayHelper::getValue($item, 'itemOptions', []));
        }

        $options = $this->htmlOptions;
        $classes = ['nav', 'nav-' . $this->type];
        if ($this->stacked) {
            $classes[] = 'nav-stacked';
        }
        $options['class'] = trim(implode(' ', $classes) . ' ' . ArrayHelper::getValue($options, 'class', ''));

        return Html::tag('ul', implode('', $lis), $options);
    }
}
