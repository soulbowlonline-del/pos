<?php
namespace app\widgets;

use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Stand-in for zii.widgets.jui.CJuiDatePicker.
 *
 * The jQuery UI calendar is not part of this port, so this is the text input
 * underneath it, carrying the class the theme's own scripts look for. The
 * value posted is the same; the picker is not. See docs/web-ui-port.md.
 */
class CJuiDatePicker extends Widget
{
    use IgnoresLegacyOptions;

    public $model;
    public $attribute;
    public $name;
    public $value;
    public $options = [];
    public $htmlOptions = [];

    public function run()
    {
        $options = $this->htmlOptions;
        $options['class'] = trim('form-control datepicker '
            . ArrayHelper::getValue($options, 'class', ''));

        if ($this->model !== null && $this->attribute !== null) {
            return Html::activeTextInput($this->model, $this->attribute, $options);
        }

        return Html::textInput($this->name, $this->value, $options);
    }
}
