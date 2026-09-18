<?php
namespace app\widgets;

use yii\helpers\ArrayHelper;
use yii\helpers\Html;

/**
 * Stand-in for bootstrap.widgets.TbActiveForm.
 *
 * 263 views build their forms with this. Two families of method are involved:
 *
 *   - the *Row() helpers, which render a label, the input and the error
 *     together as one horizontal-form row. Yii 2 builds that from a field
 *     object, so each one is a thin wrapper.
 *   - the plain CActiveForm methods - label(), textField(), dropDownList() -
 *     which Yii 2 dropped from ActiveForm and kept on Html as activeLabel(),
 *     activeTextInput() and so on.
 *
 * Where a Yii 1 helper drives a JavaScript widget that is not present here -
 * the date picker, the two rich-text editors - the field is rendered as the
 * plain input underneath it. The value posted is the same; the editing
 * experience is not, and that is noted in docs/web-ui-port.md rather than
 * papered over.
 */
class ActiveForm extends \yii\widgets\ActiveForm
{
    /** @var string TbActiveForm's 'horizontal', 'vertical' or 'inline' */
    public $type = 'vertical';

    /** @var array Yii 1 spells the form's tag attributes htmlOptions. */
    public $htmlOptions = [];

    public function init()
    {
        if (!empty($this->htmlOptions)) {
            $this->options = ArrayHelper::merge($this->htmlOptions, $this->options);
        }
        if ($this->type === 'horizontal') {
            $this->options = ArrayHelper::merge(['class' => 'form-horizontal'], $this->options);
        } elseif ($this->type === 'inline') {
            $this->options = ArrayHelper::merge(['class' => 'form-inline'], $this->options);
        }
        parent::init();
    }

    // ---------------------------------------------------------------- rows

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

    /**
     * The views spell several of these two ways - dropDownListRow and
     * dropdownListRow, checkBoxRow and checkboxRow. PHP method names are
     * case-insensitive, so one definition answers both; declaring the second
     * spelling as well is a redeclaration error.
     */
    public function dropDownListRow($model, $attribute, $data, $htmlOptions = [])
    {
        return (string) $this->field($model, $attribute)->dropDownList($data, $htmlOptions);
    }

    public function checkBoxRow($model, $attribute, $htmlOptions = [])
    {
        return (string) $this->field($model, $attribute)->checkbox($htmlOptions);
    }

    public function checkBoxListRow($model, $attribute, $data, $htmlOptions = [])
    {
        return (string) $this->field($model, $attribute)->checkboxList($data, $htmlOptions);
    }

    public function radioButtonListRow($model, $attribute, $data, $htmlOptions = [])
    {
        return (string) $this->field($model, $attribute)->radioList($data, $htmlOptions);
    }

    public function fileFieldRow($model, $attribute, $htmlOptions = [])
    {
        return (string) $this->field($model, $attribute)->fileInput($htmlOptions);
    }

    /**
     * TbActiveForm::datepickerRow() renders a text input wired to
     * bootstrap-datepicker. The picker is not part of this port, so the field
     * is the text input on its own, carrying the same class the theme's own
     * scripts look for.
     */
    public function datepickerRow($model, $attribute, $htmlOptions = [], $options = [])
    {
        $htmlOptions['class'] = trim('form-control datepicker '
            . ArrayHelper::getValue($htmlOptions, 'class', ''));

        return (string) $this->field($model, $attribute)->textInput($htmlOptions);
    }

    /** As datepickerRow: the picker is not ported, the text input is. */
    public function timepickerRow($model, $attribute, $htmlOptions = [], $options = [])
    {
        $htmlOptions['class'] = trim('form-control timepicker '
            . ArrayHelper::getValue($htmlOptions, 'class', ''));

        return (string) $this->field($model, $attribute)->textInput($htmlOptions);
    }

    /** CKEditor is not part of this port; the underlying textarea is. */
    public function ckEditorRow($model, $attribute, $htmlOptions = [], $options = [])
    {
        return (string) $this->field($model, $attribute)->textarea($htmlOptions);
    }

    /** Redactor is not part of this port; the underlying textarea is. */
    public function redactorRow($model, $attribute, $htmlOptions = [], $options = [])
    {
        return (string) $this->field($model, $attribute)->textarea($htmlOptions);
    }

    // ------------------------------------------------- plain CActiveForm

    public function label($model, $attribute, $htmlOptions = [])
    {
        return Html::activeLabel($model, $attribute, $htmlOptions);
    }

    /** labelEx() marked required attributes; Yii 2's activeLabel does that. */
    public function labelEx($model, $attribute, $htmlOptions = [])
    {
        return Html::activeLabel($model, $attribute, $htmlOptions);
    }

    public function textField($model, $attribute, $htmlOptions = [])
    {
        return Html::activeTextInput($model, $attribute, $htmlOptions);
    }

    public function passwordField($model, $attribute, $htmlOptions = [])
    {
        return Html::activePasswordInput($model, $attribute, $htmlOptions);
    }

    public function textArea($model, $attribute, $htmlOptions = [])
    {
        return Html::activeTextarea($model, $attribute, $htmlOptions);
    }

    public function dropDownList($model, $attribute, $data, $htmlOptions = [])
    {
        return Html::activeDropDownList($model, $attribute, $data, $htmlOptions);
    }

    public function checkBox($model, $attribute, $htmlOptions = [])
    {
        return Html::activeCheckbox($model, $attribute, $htmlOptions);
    }

    public function hiddenField($model, $attribute, $htmlOptions = [])
    {
        return Html::activeHiddenInput($model, $attribute, $htmlOptions);
    }

    public function error($model, $attribute, $htmlOptions = [])
    {
        return Html::error($model, $attribute, $htmlOptions);
    }

    public function errorSummary($models, $header = null, $footer = null, $htmlOptions = [])
    {
        return Html::errorSummary($models, ArrayHelper::merge(
            ['header' => $header, 'footer' => $footer], $htmlOptions));
    }

    /**
     * CActiveForm::widget($class, $config) renders a widget already bound to
     * the form's model. The views use it for pickers and editors.
     *
     * The name is renderWidget() and not widget(): Yii 2's Widget already has
     * a static widget(), and PHP will not let a subclass redeclare it as an
     * instance method. The transformer rewrites the call sites to match.
     */
    public function renderWidget($className, $properties = [], $captureOutput = false)
    {
        $class = strpos($className, '\\') === false
            ? 'app\\widgets\\' . preg_replace('/^.*\\./', '', $className)
            : $className;
        if (!class_exists($class)) {
            return '';
        }
        $out = $class::widget($properties);

        if ($captureOutput) {
            return $out;
        }
        echo $out;
        return '';
    }
}
