<?php
namespace app\widgets;

use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/** Stand-in for bootstrap.widgets.TbButton. */
class Button extends Widget
{
    use IgnoresLegacyOptions;

    public $buttonType = 'button';
    public $type = '';
    public $label = '';
    public $icon = '';
    public $url;
    public $htmlOptions = [];

    public function run()
    {
        $options = $this->htmlOptions;
        $options['class'] = trim('btn btn-' . ($this->type !== '' ? $this->type : 'default')
            . ' ' . ArrayHelper::getValue($options, 'class', ''));

        $label = Html::encode($this->label);
        if ($this->icon !== '') {
            $label = Html::tag('i', '', ['class' => $this->icon]) . ' ' . $label;
        }

        if ($this->buttonType === 'submit') {
            return Html::submitButton($label, $options);
        }
        if ($this->buttonType === 'link' || $this->url !== null) {
            return Html::a($label, $this->url === null ? '#' : $this->url, $options);
        }
        return Html::button($label, $options);
    }
}
