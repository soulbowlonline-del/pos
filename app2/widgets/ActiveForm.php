<?php
namespace app\widgets;

use yii\helpers\ArrayHelper;

/**
 * Stand-in for bootstrap.widgets.TbActiveForm.
 *
 * 218 views call the *Row() helpers, which render a label, the input and the
 * error together in one horizontal-form row. Yii 2's ActiveForm already builds
 * that from a field object; these methods wrap it so the call sites do not
 * change shape.
 */
class ActiveForm extends \yii\widgets\ActiveForm
{
    /** @var string TbActiveForm's 'horizontal', 'vertical' or 'inline' */
    public $type = 'vertical';

    public function init()
    {
        if ($this->type === 'horizontal') {
            $this->options = ArrayHelper::merge(['class' => 'form-horizontal'], $this->options);
        } elseif ($this->type === 'inline') {
            $this->options = ArrayHelper::merge(['class' => 'form-inline'], $this->options);
        }
        parent::init();
    }

    public function textFieldRow($model, $attribute, $htmlOptions = [])
    {
        return (string) $this->field($model, $attribute)->textInput($htmlOptions);
    }

    public function passwordFieldRow($model, $attribute, $htmlOptions = [])
    {
        return (string) $this->field($model, $attribute)->passwordInput($htmlOptions);
    }

    public function textAreaRow($model, $attribute, $htmlOptions = [])
    {
        return (string) $this->field($model, $attribute)->textarea($htmlOptions);
    }

    public function dropDownListRow($model, $attribute, $data, $htmlOptions = [])
    {
        return (string) $this->field($model, $attribute)->dropDownList($data, $htmlOptions);
    }

    public function checkBoxRow($model, $attribute, $htmlOptions = [])
    {
        return (string) $this->field($model, $attribute)->checkbox($htmlOptions);
    }

    public function fileFieldRow($model, $attribute, $htmlOptions = [])
    {
        return (string) $this->field($model, $attribute)->fileInput($htmlOptions);
    }

    public function hiddenField($model, $attribute, $htmlOptions = [])
    {
        return (string) $this->field($model, $attribute)->hiddenInput($htmlOptions)->label(false);
    }
}
