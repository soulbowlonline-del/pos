<?php
namespace app\widgets;

use Yii;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\JsExpression;
use yii\web\View;

/**
 * bootstrap.widgets.TbEditableColumn (ext-prod/bootstrap/widgets/TbEditableColumn.php
 * and TbEditableField.php): each cell is an x-editable link, edited in place.
 *
 * This was a stub that printed the value as plain text, so nothing in these
 * columns could be edited - and since the cell had no element id either, the
 * page javascript that reads it found nothing. purchaseBill/view,
 * b2bPurchaseBill/view and item/barcode take the expiry and packing dates for
 * the barcode labels from `#PurchaseBillDetail_expiry_date_<id>`'s text
 * after the user edits it; item/admin saves tax, category, status, vendor and
 * the stock levels through it.
 *
 * The markup and the options are TbEditableField's:
 *   <a href="#" rel="Model_attr" data-pk="<pk>" id="Model_attr_<pk>">text</a>
 * with one `.editable({...})` call per column. No `url` (the barcode screens)
 * means the edit stays on the page; with one, x-editable posts name, value and
 * pk to it. The plugin is the same X-editable 1.3.0 Yii 1 publishes.
 *
 * Yii 1 re-applies the editables when the grid is reloaded by ajax, on the
 * grid's ajaxUpdate.yiiGridView event; v2/js/legacy-grid.js fires the same
 * event after it swaps a grid in.
 */
class EditableColumn extends DataColumn
{
    /** @var array TbEditableField's configuration (type, url, source, ...). */
    public $editable = [];

    /** @var bool one script per column, as TbEditableColumn's flag */
    private $_isScriptRendered = false;

    public function init()
    {
        // Yii 1 tests strlen($this->value): '' means "the attribute itself".
        if ($this->value === '') {
            $this->value = null;
        }
        parent::init();
    }

    protected function renderDataCellContent($model, $key, $index)
    {
        $config = $this->editable;
        $attribute = isset($config['attribute']) ? $config['attribute'] : $this->attribute;
        $type = isset($config['type']) ? $config['type'] : 'text';

        // the column's value, when it has one, is the link's text, unencoded
        $text = null;
        if ($this->value !== null) {
            $text = $this->grid->formatter->format($this->getDataCellValue($model, $key, $index), $this->format);
        }

        if (isset($config['apply']) && $config['apply'] === false) {
            return $text !== null ? $text : Html::encode($model->$attribute);
        }

        $pk = $model->getPrimaryKey();
        $pk = is_array($pk) ? implode('-', array_values($pk)) : $pk;
        $selector = $model->formName() . '_' . $attribute;

        $htmlOptions = isset($config['htmlOptions']) ? $config['htmlOptions'] : [];
        $htmlOptions['href'] = '#';
        $htmlOptions['rel'] = $selector;
        $htmlOptions['data-pk'] = $pk;
        $htmlOptions['id'] = $selector . ($pk ? '_' . $pk : '_new');

        // A list or a date shows its text from data-value (x-editable's
        // autotext), so the select's label rather than the stored id.
        $options = isset($config['options']) ? $config['options'] : [];
        $autotext = (!isset($options['autotext']) || $options['autotext'] !== 'never')
            && in_array($type, ['select', 'checklist', 'date', 'dateui'], true);
        if ($autotext) {
            $htmlOptions['data-value'] = $model->$attribute;
        }
        if ($text === null) {
            $text = $autotext ? '' : Html::encode($model->$attribute);
        }
        if ($type === 'textarea') {
            $text = preg_replace('/\r?\n/', '<br>', $text);
        }

        if (!$this->_isScriptRendered) {
            $this->registerClientScript($model, $attribute, $type, $selector, $config);
            $this->_isScriptRendered = true;
        }

        return Html::tag('a', $text, $htmlOptions);
    }

    /** TbEditableField::buildJsOptions() and registerClientScript(). */
    private function registerClientScript($model, $attribute, $type, $selector, array $config)
    {
        if (isset($config['title'])) {
            $title = strtr($config['title'], ['{label}' => $model->getAttributeLabel($attribute)]);
        } else {
            $verb = 'Enter';
            if (in_array($type, ['select', 'date'], true)) {
                $verb = 'Select';
            } elseif ($type === 'checklist') {
                $verb = 'Check';
            }
            $title = $verb . ' ' . $model->getAttributeLabel($attribute);
        }

        $url = isset($config['url']) ? $config['url'] : '';
        if (is_array($url)) {
            $url = \yii\helpers\Url::to($url);
        }

        $options = [
            'type' => $type,
            'url' => (string) $url,
            'name' => $attribute,
            'title' => Html::encode($title),
        ];
        foreach (['placement', 'emptytext', 'params', 'inputclass', 'format', 'viewformat'] as $option) {
            if (!empty($config[$option])) {
                $options[$option] = $config[$option];
            }
        }

        if (isset($config['source'])) {
            $source = $config['source'];
            if (is_array($source) && count($source) > 1) {
                if (isset($source[0]) && is_array($source[0])) {
                    $options['source'] = $source;
                } else {
                    $options['source'] = [];
                    foreach ($source as $value => $label) {
                        $options['source'][] = ['value' => $value, 'text' => $label];
                    }
                }
            } else {
                // a one-element array or a string is a route to load the list from
                $options['source'] = \yii\helpers\Url::to($source);
            }
        }

        foreach (['validate', 'success', 'display'] as $method) {
            if (isset($config[$method])) {
                $options[$method] = self::js($config[$method]);
            }
        }

        $options = array_merge(isset($config['options']) ? $config['options'] : [], $options);

        $script = '$(' . Json::encode('#' . $this->grid->options['id'] . ' a[rel=' . $selector . ']') . ')';
        foreach (['init', 'shown', 'save', 'hidden'] as $event) {
            $name = 'on' . ucfirst($event);
            if (isset($config[$name])) {
                $script .= '.on(' . Json::encode($event) . ', ' . self::js($config[$name]) . ')';
            }
        }
        $script .= '.editable(' . Json::encode($options) . ');';

        $view = $this->grid->getView();
        $view->registerCssFile('/v2/css/bootstrap-editable.min.css');
        $view->registerJsFile('/v2/js/bootstrap-editable.min.js',
                              ['depends' => \yii\web\JqueryAsset::class, 'position' => View::POS_END]);
        $gridId = Json::encode('#' . $this->grid->options['id']);
        $view->registerJs($script
            . "\n$(document).on('ajaxUpdate.yiiGridView', " . $gridId . ', function () {' . $script . '});');
    }

    /** A Yii 1 'js:...' callback (or plain code) as a javascript expression. */
    private static function js($code)
    {
        return new JsExpression(strpos($code, 'js:') === 0 ? substr($code, 3) : $code);
    }
}
