<?php
namespace app\widgets;

use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Stand-in for bootstrap.widgets.TbButtonGroup.
 *
 * YiiBooster is a Yii 1 extension and does not run here, but 387 of the views
 * to be ported call its widgets. Rather than rewrite each view's markup, these
 * shims take the same configuration arrays and emit the same Bootstrap
 * classes, so a view ports by changing how the widget is invoked and not what
 * it renders.
 *
 * The button list is the controller's $menu, whose items are
 * ['label' => ..., 'url' => ..., 'icon' => ...] exactly as in Yii 1.
 */
class ButtonGroup extends Widget
{
    /** @var array menu items: label, url, optional icon and linkOptions */
    public $buttons = [];

    /** Bootstrap button flavour - 'success', 'primary', and so on. */
    public $type = '';

    public $htmlOptions = [];

    public function run()
    {
        if (empty($this->buttons)) {
            return '';
        }

        $class = 'btn btn-' . ($this->type !== '' ? $this->type : 'default');
        $out = [];
        foreach ($this->buttons as $b) {
            $label = Html::encode(ArrayHelper::getValue($b, 'label', ''));
            if (!empty($b['icon'])) {
                // Bootstrap 2 icon classes, as the original theme uses them.
                $label = Html::tag('i', '', ['class' => $b['icon']]) . ' ' . $label;
            }
            $opts = ArrayHelper::getValue($b, 'linkOptions', []);
            $opts['class'] = trim($class . ' ' . ArrayHelper::getValue($opts, 'class', ''));
            $out[] = Html::a($label, ArrayHelper::getValue($b, 'url', '#'), $opts);
        }

        $options = $this->htmlOptions;
        $options['class'] = trim('btn-group ' . ArrayHelper::getValue($options, 'class', ''));

        return Html::tag('div', implode('', $out), $options);
    }
}
