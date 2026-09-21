<?php
namespace app\widgets;

use yii\base\Widget;
use yii\helpers\ArrayHelper;
use yii\helpers\Html;
use yii\helpers\Json;
use yii\web\JqueryAsset;

/**
 * TbTypeahead: the item autocomplete the purchasing screens are driven by.
 *
 * It used to render the text input and register nothing, so the box existed
 * and nothing ever appeared in it. That is the item entry on the goods
 * received note, the requisition and the purchase order - the field you type
 * an item name into - so those screens could not be used at all, whatever
 * vendor was chosen.
 *
 * Yii 1 emits
 *
 *     var <id> = $('#<id>').typeahead([{ ...dataset... }]);
 *     <id>.on('typeahead:selected', function (obj, datum, name) { ... });
 *
 * and publishes typeahead.js, hogan and the stylesheet from
 * protected/extensions/typeahead. The same files are served here from /v2 and
 * the same two statements are emitted, against the same id.
 *
 * One translation is needed. A typeahead dataset names itself with `name`,
 * and the generator rewrote that key to `attribute` when it ported the views -
 * a dataset called `attribute` is not one typeahead understands. The key is
 * mapped back here rather than in nineteen views.
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
            $field = Html::activeTextInput($this->model, $this->attribute, $options);
            $id = $options['id'] ?? Html::getInputId($this->model, $this->attribute);
        } else {
            $field = Html::textInput($this->name, $this->value, $options);
            $id = $options['id'] ?? Html::getInputIdByName($this->name);
        }

        $this->register($id);

        return $field;
    }

    private function register($id)
    {
        $view = $this->getView();
        $view->registerCssFile('/v2/css/typeahead.js-bootstrap.css');
        $view->registerJsFile('/v2/js/hogan-2.0.0.js', ['depends' => JqueryAsset::class]);
        $view->registerJsFile('/v2/js/typeahead.js', ['depends' => JqueryAsset::class]);

        $datasets = [];
        foreach ($this->options as $dataset) {
            if (!is_array($dataset)) {
                continue;
            }
            // See the note above: `attribute` is the generator's spelling of
            // typeahead's own `name`.
            if (!isset($dataset['name']) && isset($dataset['attribute'])) {
                $dataset['name'] = $dataset['attribute'];
            }
            unset($dataset['attribute']);
            $datasets[] = $dataset;
        }

        // Json::encode rather than json_encode: the datasets carry a
        // JsExpression for the template engine, and the events are
        // JsExpressions throughout.
        $var = preg_replace('/[^A-Za-z0-9_]/', '_', $id);
        $js = sprintf("var %s = jQuery('#%s').typeahead(%s);\n",
                      $var, $id, Json::encode($datasets));
        foreach ($this->events as $event => $handler) {
            $js .= sprintf("%s.on('typeahead:%s', %s);\n",
                           $var, $event, Json::encode($handler));
        }
        $view->registerJs($js);
    }
}
