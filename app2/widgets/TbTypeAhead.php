<?php
namespace app\widgets;

use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Stand-in for ext.typeahead.TbTypeAhead.
 *
 * The Bootstrap typeahead plugin is not part of this port, so what is left is
 * the text input it decorates - the same field, posting the same value, with
 * the same name and id. The suggestion list, its remote lookup and its
 * selection callback are not reproduced, which is a real difference in how the
 * form is used and is listed in docs/web-ui-port.md with the pickers and the
 * rich text editors.
 */
class TbTypeAhead extends Widget
{
    use IgnoresLegacyOptions;

    public $model;
    public $attribute;
    public $name;
    public $value;
    public $options = [];
    public $htmlOptions = [];
    public $events = [];

    public function run()
    {
        $options = $this->htmlOptions;
        $options['class'] = trim('form-control ' . ArrayHelper::getValue($options, 'class', ''));
        $options['autocomplete'] = 'off';

        if ($this->model !== null && $this->attribute !== null) {
            return Html::activeTextInput($this->model, $this->attribute, $options);
        }

        return Html::textInput($this->name, $this->value, $options);
    }
}
