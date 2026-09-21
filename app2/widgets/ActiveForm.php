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
        $htmlOptions = self::promptFromEmpty($htmlOptions);

        return (string) $this->field($model, $attribute)->dropDownList($data, $htmlOptions);
    }

    /**
     * CHtml's `empty` is Yii 2's `prompt`.
     *
     * Both prepend an option with an empty value and the given label. Passed
     * through untouched, Yii 2 does not recognise the key and renders it as
     * an attribute on the <select> - so the option was simply missing, on 25
     * dropdowns across the forms: "Select State", "Select Country", "No
     * Parent".
     *
     * The UI suite did not see it. It compares a form's fields and their
     * current values, not the options inside a select.
     *
     * CHtml also accepts an array here, as a set of options to prepend rather
     * than one. Nothing in this application does, so that form is left alone
     * and reaches Yii 2 unchanged, where it still fails loudly.
     */
    private static function promptFromEmpty(array $htmlOptions)
    {
        if (array_key_exists('empty', $htmlOptions) && !is_array($htmlOptions['empty'])) {
            $htmlOptions['prompt'] = (string) $htmlOptions['empty'];
            unset($htmlOptions['empty']);
        }

        return $htmlOptions;
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
     * TbActiveForm::datepickerRow(): the field and the picker on it.
     *
     * The picker used not to be ported - the text input was rendered and
     * nothing bound to it - so every date field in the application was a box
     * you had to type into, and the screens that search by a date range could
     * not be driven at all.
     *
     * Yii 1 publishes bootstrap-datepicker from its bootstrap extension and
     * registers `jQuery('#Model_attribute').datepicker({...})` per field. The
     * same script and stylesheet are served here from /v2/js and /v2/css, and
     * the same call is registered against the same id, which is Yii 1's id now
     * (see app2/helpers/Html.php).
     */
    public function datepickerRow($model, $attribute, $htmlOptions = [], $options = [])
    {
        // TbActiveForm carries the picker's own options inside $htmlOptions
        // under 'options', and its hint and prepend beside them. Left in
        // place they became attributes on the input - the port was rendering
        // hint="Click inside! to select a date." into the tag - and the
        // picker options never reached the picker, so a field asking for a
        // different format silently got the default one.
        $options = array_merge(ArrayHelper::remove($htmlOptions, 'options', []),
                               $options);
        $hint = ArrayHelper::remove($htmlOptions, 'hint');
        ArrayHelper::remove($htmlOptions, 'prepend');
        ArrayHelper::remove($htmlOptions, 'append');

        $htmlOptions['class'] = trim('form-control datepicker '
            . ArrayHelper::getValue($htmlOptions, 'class', ''));

        $field = (string) $this->field($model, $attribute)->textInput($htmlOptions);
        if ($hint !== null && $hint !== '') {
            $field .= \yii\helpers\Html::tag('span', $hint, ['class' => 'help-block']);
        }

        $view = $this->getView();
        $view->registerCssFile('/v2/css/bootstrap-datepicker.css');
        $view->registerJsFile('/v2/js/bootstrap.datepicker.js',
                              ['depends' => \yii\web\JqueryAsset::class]);

        // Yii 1's defaults, which every call in the application relies on:
        // the format the models store and parse, and a week starting Sunday.
        $options = array_merge(['format' => 'yyyy-mm-dd', 'language' => 'en',
                                'weekStart' => 0], $options);
        $id = $htmlOptions['id'] ?? \yii\helpers\Html::getInputId($model, $attribute);
        $view->registerJs(sprintf("jQuery('#%s').datepicker(%s);",
                                  $id, json_encode($options, JSON_UNESCAPED_SLASHES)));

        return $field;
    }

    /**
     * TbActiveForm::timepickerRow(): the field and the picker on it.
     *
     * As datepickerRow. TbTimePicker publishes bootstrap-timepicker and emits
     * jQuery('#id').timepicker({...}) with any events chained onto it; the
     * same files are served from /v2 and the same call is made. Two views use
     * it - the discount form and the shift form - and in both the field was a
     * box you had to type a time into.
     */
    public function timepickerRow($model, $attribute, $htmlOptions = [], $options = [])
    {
        $options = array_merge(ArrayHelper::remove($htmlOptions, 'options', []),
                               $options);
        $hint = ArrayHelper::remove($htmlOptions, 'hint');
        $events = ArrayHelper::remove($htmlOptions, 'events', []);
        ArrayHelper::remove($htmlOptions, 'prepend');
        ArrayHelper::remove($htmlOptions, 'append');

        $htmlOptions['class'] = trim('form-control timepicker '
            . ArrayHelper::getValue($htmlOptions, 'class', ''));

        $field = (string) $this->field($model, $attribute)->textInput($htmlOptions);
        if ($hint !== null && $hint !== '') {
            $field .= \yii\helpers\Html::tag('span', $hint, ['class' => 'help-block']);
        }

        $id = $htmlOptions['id'] ?? \yii\helpers\Html::getInputId($model, $attribute);
        $view = $this->getView();
        $view->registerCssFile('/v2/css/bootstrap-timepicker.css');
        $view->registerJsFile('/v2/js/bootstrap.timepicker.js',
                              ['depends' => \yii\web\JqueryAsset::class]);

        $js = sprintf("jQuery('#%s').timepicker(%s)", $id,
                      $options ? self::encodeOptions($options) : '');
        foreach ($events as $event => $handler) {
            $js .= sprintf(".on('%s', %s)", $event,
                           \yii\helpers\Json::encode($handler));
        }
        $view->registerJs($js . ';');

        return $field;
    }

    /**
     * Yii 1's CJavaScript::encode(): a value written as 'js:...' is raw
     * javascript rather than a string.
     *
     * The views rely on it - ckEditorRow is called with
     * ['fullpage' => 'js:true'], which has to reach CKEditor as the boolean
     * true and not as the string "js:true".
     */
    private static function encodeOptions(array $options)
    {
        $json = json_encode($options, JSON_UNESCAPED_SLASHES);

        return preg_replace('/"js:(.*?)"/', '$1', $json);
    }

    /**
     * TbActiveForm::ckEditorRow(): a textarea with CKEditor on it.
     *
     * Yii 1 publishes CKEditor from its bootstrap extension and replaces the
     * textarea with `CKEDITOR.replace('Model_attribute', {...})`. The same
     * library is served here from /v2/js/ckeditor and the same call is made
     * against the same id.
     *
     * Used by twelve views. Until now the textarea was rendered bare, so the
     * remarks and payment-terms fields on a purchase bill were a plain box.
     */
    public function ckEditorRow($model, $attribute, $htmlOptions = [], $options = [])
    {
        $options = array_merge(ArrayHelper::remove($htmlOptions, 'options', []), $options);
        $hint = ArrayHelper::remove($htmlOptions, 'hint');

        $field = (string) $this->field($model, $attribute)->textarea($htmlOptions);
        if ($hint !== null && $hint !== '') {
            $field .= \yii\helpers\Html::tag('span', $hint, ['class' => 'help-block']);
        }

        $id = $htmlOptions['id'] ?? \yii\helpers\Html::getInputId($model, $attribute);
        $view = $this->getView();
        $view->registerJsFile('/v2/js/ckeditor/ckeditor.js',
                              ['depends' => \yii\web\JqueryAsset::class]);
        $view->registerJs(sprintf("CKEDITOR.replace( '%s', %s);",
                                  $id, self::encodeOptions($options)));

        return $field;
    }

    /**
     * TbActiveForm::redactorRow(): a textarea with Redactor on it.
     *
     * TbRedactorJs sizes the textarea itself - width 100%, height 400px,
     * unless the caller gives its own style - and then calls
     * `$('#Model_attribute').redactor({...})`. Reproduced, including the
     * language, which Yii 1 takes from the application's own.
     *
     * Used by eleven views, on the same fields ckEditorRow serves: the form
     * picks between them on a setting.
     */
    public function redactorRow($model, $attribute, $htmlOptions = [], $options = [])
    {
        $options = array_merge(ArrayHelper::remove($htmlOptions, 'options', []), $options);
        $hint = ArrayHelper::remove($htmlOptions, 'hint');
        $width = ArrayHelper::remove($htmlOptions, 'width', '100%');
        $height = ArrayHelper::remove($htmlOptions, 'height', '400px');
        if (!isset($htmlOptions['style'])) {
            $htmlOptions['style'] = "width:$width;height:$height;";
        }
        if (!isset($options['lang'])) {
            $options['lang'] = substr(Yii::$app->language, 0, 2);
        }

        $field = (string) $this->field($model, $attribute)->textarea($htmlOptions);
        if ($hint !== null && $hint !== '') {
            $field .= \yii\helpers\Html::tag('span', $hint, ['class' => 'help-block']);
        }

        $id = $htmlOptions['id'] ?? \yii\helpers\Html::getInputId($model, $attribute);
        $view = $this->getView();
        $view->registerCssFile('/v2/css/redactor.css');
        $view->registerJsFile('/v2/js/redactor.min.js',
                              ['depends' => \yii\web\JqueryAsset::class]);
        $view->registerJs(sprintf("jQuery('#%s').redactor(%s);",
                                  $id, self::encodeOptions($options)));

        return $field;
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
        // CActiveForm's wording, which differs from Yii 2's by one word:
        // "Please fix the following input errors:" against "Please fix the
        // following errors:". Every form in the application shows this the
        // moment a field fails, and the UI suite does not see it - it compares
        // the fields, not the summary above them.
        if ($header === null) {
            $header = '<p>Please fix the following input errors:</p>';
        }

        $html = Html::errorSummary($models, ArrayHelper::merge(
            ['header' => $header, 'footer' => $footer], $htmlOptions));

        // CActiveForm emits a hidden placeholder when there is nothing to
        // report and validation runs in the browser: the container has to
        // exist for the client script to fill it, and its list carries a
        // single `dummy` item. Yii 2 renders an empty summary instead, so a
        // form that failed validation in the browser had nowhere to show it.
        if (($this->enableClientValidation || $this->enableAjaxValidation)
                && strpos($html, '<li>') === false) {
            $options = $htmlOptions;
            $options['class'] = $options['class'] ?? 'errorSummary';
            $options['style'] = isset($options['style'])
                ? rtrim($options['style'], ';') . ';display:none'
                : 'display:none';

            return Html::tag('div', $header . "\n<ul><li>dummy</li></ul>" . $footer,
                             $options);
        }

        return $html;
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
