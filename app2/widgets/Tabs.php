<?php
namespace app\widgets;

use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Stand-in for bootstrap.widgets.TbTabs, as GxController's panel helpers use
 * it: each tab is ['label' => ..., 'content' => ..., 'active' => bool] and the
 * content is already-rendered HTML.
 */
class Tabs extends Widget
{
    use IgnoresLegacyOptions;

    public $type = 'tabs';
    public $tabs = [];
    public $htmlOptions = [];

    public function run()
    {
        if (empty($this->tabs)) {
            return '';
        }

        $heads = [];
        $panes = [];
        foreach ($this->tabs as $i => $tab) {
            $id = $this->getId() . '-tab' . $i;
            $active = !empty($tab['active']);
            $heads[] = Html::tag('li',
                Html::a(Html::encode(ArrayHelper::getValue($tab, 'label', '')),
                        '#' . $id, ['data-toggle' => 'tab']),
                $active ? ['class' => 'active'] : []);
            $panes[] = Html::tag('div', ArrayHelper::getValue($tab, 'content', ''),
                ['id' => $id, 'class' => 'tab-pane' . ($active ? ' active' : '')]);
        }

        $options = $this->htmlOptions;
        $options['class'] = trim('tabbable ' . ArrayHelper::getValue($options, 'class', ''));

        return Html::tag('div',
            Html::tag('ul', implode('', $heads), ['class' => 'nav nav-' . $this->type])
            . Html::tag('div', implode('', $panes), ['class' => 'tab-content']),
            $options);
    }
}
