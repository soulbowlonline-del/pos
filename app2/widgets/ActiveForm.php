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
    use IgnoresLegacyOptions;

    /** @var string TbActiveForm's 'horizontal', 'vertical' or 'inline' */
    public $type = 'vertical';

    /** @var array Yii 1 spells the form's tag attributes htmlOptions. */
    public $htmlOptions = [];

    /**
     * CActiveForm's clientOptions.
     *
     * Yii 1 takes the client-side validation settings as one array;
     * yii\widgets\ActiveForm exposes them as properties and answers
     * clientOptions from a getter, so assigning it raises "Setting read-only
     * property". The keys that have a counterpart are copied onto it and the
     * rest are dropped with a note, rather than the whole form failing.
     */
    public $clientOptions = [];

    public function init()
    {
        foreach ($this->clientOptions as $name => $value) {
            // Yii 2 spells these the same way, as properties of the form.
            if (in_array($name, ['validateOnSubmit', 'validateOnChange',
                                 'validateOnBlur', 'validateOnType',
                                 'enableClientValidation', 'enableAjaxValidation',
                                 'errorCssClass', 'successCssClass',
                                 'validatingCssClass', 'errorSummaryCssClass'], true)) {
                $this->$name = $value;
            }
        }
        $this->clientOptions = [];

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
        // A multiple select is CHtml::activeDropDownList's other shape, and it
        // behaves like activeListBox: the name gains `[]` and there is no
        // hidden "nothing selected" input. Yii 2's listBox emits one unless it
        // is told not to, which gave discount's form a scalar
        // `Discount[item_detail_id]` beside the real
        // `Discount[item_detail_id][]` - thirteen fields where Yii 1 has
        // twelve.
        if (!empty($htmlOptions['multiple'])) {
            $htmlOptions = self::noUnselect($htmlOptions);
        }

        return (string) $this->field($model, $attribute)->dropDownList($data, $htmlOptions);
    }

    public function checkBoxRow($model, $attribute, $htmlOptions = [])
    {
        return (string) $this->field($model, $attribute)->checkbox($htmlOptions);
    }

    public function checkBoxListRow($model, $attribute, $data, $htmlOptions = [])
    {
        $htmlOptions = $this->scalarSelection($model, $attribute, $htmlOptions);

        return (string) $this->field($model, $attribute)->checkboxList($data, $htmlOptions);
    }

    /**
     * Drops anything from the checked-values list that is not a scalar.
     *
     * Some models use the same name for a column list and for a getter that
     * builds grid column definitions - ItemExpire::getColumns() answers
     * $model->columns with an array of arrays. Yii 1 compared each option
     * loosely against that and matched none of them, so it rendered the boxes
     * unchecked. Yii 2 runs array_map('strval', ...) over the selection first
     * and raises "Array to string conversion" instead.
     *
     * Reducing the selection to its scalars renders the same unchecked boxes.
     */
    /**
     * Stops Yii 2 emitting its hidden "nothing selected" input.
     *
     * Used for a multiple *select* only. Yii 1's checkBoxListRow and
     * radioButtonListRow do emit such a field - `ytEmp_shift_id` and the like
     * - so suppressing it there removed an input Yii 1 has and broke two
     * controllers that had been matching. CHtml::activeListBox does not emit
     * one, and that is the only case this is applied to.
     */
    public static function noUnselect($htmlOptions)
    {
        if (!array_key_exists('unselect', $htmlOptions)) {
            $htmlOptions['unselect'] = null;
        }

        return $htmlOptions;
    }

    private function scalarSelection($model, $attribute, $htmlOptions)
    {
        if (isset($htmlOptions['value'])) {
            return $htmlOptions;
        }

        $value = $model->$attribute;
        if (is_array($value)) {
            $scalars = array_filter($value, 'is_scalar');
            if (count($scalars) !== count($value)) {
                // Passed as an option rather than written back: several of
                // these attributes are read-only getters - ItemExpire::columns
                // is answered by getColumns() - and assigning to one throws.
                $htmlOptions['value'] = array_values($scalars);
            }
        }

        return $htmlOptions;
    }

    public function radioButtonListRow($model, $attribute, $data, $htmlOptions = [])
    {
        $htmlOptions = $this->scalarSelection($model, $attribute, $htmlOptions);

        return (string) $this->field($model, $attribute)->radioList($data, $htmlOptions);
    }

    public function fileFieldRow($model, $attribute, $htmlOptions = [])
    {
        // Yii 2 puts the attribute's current value on the file input; Yii 1
        // does not, and a browser ignores it either way. It still shows up as
        // a difference on every update form for a model that stores a
        // filename, so it is cleared here rather than excused there.
        // '' and not null: Yii 2 resolves the input's value with ??, so null
        // falls straight back to the attribute.
        $htmlOptions['value'] = '';

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
