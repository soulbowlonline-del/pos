<?php

/**
 * Class TypeAhead
 *
 * ## Usage without model
 *
 * ```
 * $this->widget('ext.typeahead.TypeAhead',array(
 *      'name' => 'hello',
 *      'options' => array(
 *          array(
 *              'name' => 'accounts',
 *              'local' => array(
 *                  'jquery',
 *                  'ajax',
 *                  'bootstrap'
 *              ),
 *          )
 *      ),
 *      'events' => array(
 *          'selected' => new CJavascriptExpression('function(evt,data) {
 *              console.log('data==>' + data); //selected datum object
 *          }'),
 *      ),
 * ));
 * ```
 *
 * ## Usage with model
 *
 * ```
 * $this->widget('ext.typeahead.TypeAhead',array(
 *      'model' => $model,
 *      'attribute' => 'keyword',
 *      'options' => array(
 *          array(
 *              'name' => 'accounts',
 *              'local' => array(
 *                  'jquery',
 *                  'ajax',
 *                  'bootstrap'
 *              ),
 *          )
 *      ),
 * ));
 * ```
 *
 * ## Complicated Usage
 *
 * ```
 * $this->widget('ext.typeahead.TypeAhead',array(
 *      'model' => $model,
 *      'attribute' => 'keyword',
 *      'enableHogan' => true,
 *      'options' => array(
 *          array(
 *              'name' => 'countries',
 *              'valueKey' => 'name',
 *              'remote' => array(
 *                  'url' => Yii::app()->createUrl('/ajax/countryLists') . '?term=%QUERY',
 *              ),
 *              'template' => '<p>{{name}}<strong>{{code}}</strong> - {{id}}</p>',
 *              'engine' => new CJavaScriptExpression('Hogan'),
 *          )
 *      ),
 *      'events' => array(
 *          'selected' => new CJavascriptExpression("function(obj, datum, name) {
 *              console.log(obj);
 *              console.log(datum);
 *              console.log(name);
 *          }")
 *      ),
 * ));
 * ```
 *
 * in your Ajax Controller
 * ```
 * class AjaxController extends Controller
 * {
 *     public function actionCountryLists()
 *     {
 *         $term = Yii::app()->request->getQuery('term');
 *         $countries = Country::model()->findAllByAttributes(array('name' => "%{$term}%"));
 *
 *         $lists = array();
 *         foreach($countries as $country) {
 *             $lists[] = array(
 *                 'id' => $country->id,
 *                 'name' => $country->name,
 *                 'code' => $country->code,
 *             );
 *         }
 *
 *         echo json_encode($lists);
 *     }
 *
 * }
 *
 *
 * @author Bryan Jayson Tan <bryantan16@gmail.com>
 * @link admin@bryantan.info
 * @date 10/30/2013
 * @time 08:15pm
 * @see https://github.com/twitter/typeahead.js
 */
class TbTypeAhead extends CInputWidget
{
    /**
     * @var array
     * @see http://twitter.github.io/typeahead.js/examples/
     */
    public $options;

    /**
     * custom css file. if null we will use default css files
     * @var null
     */
    public $cssFile = null;

    /**
     * @var bool
     */
    public $enableHogan = false;

    /**
     * custom events
     * available parameter as per bootstraps are
     *
     * ```
     * array(
     *      'initialized' => new CJavascriptExpression('function(evt,data) { }'), //
     *      'opened' => new CJavascriptExpression('function(evt,data) { }'),
     *      'closed' => new CJavascriptExpression('function(evt,data) { }'),
     *      'selected' => new CJavascriptExpression('function(evt,data) { }'),
     *      'autocompleted' => new CJavascriptExpression('function(evt,data) { }'),
     * )
     * ```
     *
     * initialized -  Triggered after initialization. If data needs to be prefetched, this event will not be triggered until after the prefetched data is processed.
     * opened - Triggered when the dropdown menu of a typeahead is opened.
     * closed - Triggered when the dropdown menu of a typeahead is closed.
     * selected - Triggered when a suggestion from the dropdown menu is explicitly selected. The datum for the selected suggestion is passed to the event handler as an argument in addition to the name of the dataset it originated from.
     * autocompleted - Triggered when the query is autocompleted. The datum used for autocompletion is passed to the event handler as an argument in addition to the name of the dataset it originated from.
     *
     * All custom events are triggered on the element initialized as a typeahead.
     * @var array
     */
    public $events = array();

    private $_assetsUrl = null;

    public function init()
    {
        $this->publishAssets();
    }

    public function run()
    {
        $this->renderField();
        $this->registerClientScript();
        $this->registerCss();
    }

    public function renderField()
    {
        list($name, $id) = $this->resolveNameID();

        if ($this->hasModel()) {
            echo CHtml::activeTextField($this->model, $this->attribute, $this->htmlOptions);
        }else {
            echo CHtml::textField($name, $this->value, $this->htmlOptions);
        }
    }

    public function publishAssets()
    {
        if ($this->_assetsUrl === null) {
            $assetsUrl = Yii::app()->assetManager->publish(dirname(__FILE__).'/assets');

            $this->_assetsUrl = $assetsUrl;
        }

        return $this->_assetsUrl;
    }

    public function registerCss()
    {
        $cs = Yii::app()->getClientScript();
        if ($this->cssFile !== null) {
            $cs->registerCssFile($this->cssFile);
        }else {
            $cs->registerCssFile($this->_assetsUrl . '/css/typeahead.js-bootstrap.css');
        }
    }

    public function registerClientScript()
    {
        list($name, $id) = $this->resolveNameID();

        $options = $this->options;
        $options = CJavaScript::encode($options);

        $eventsScript = array();
        foreach($this->events as $event => $expression) {
            $eventsScript[] = "{$id}.on('typeahead:{$event}',{$expression})";
        }
        $eventsScript = implode("\n",$eventsScript);

        $cs = Yii::app()->getClientScript();
        if ($this->enableHogan === true) {
            $cs->registerScriptFile($this->_assetsUrl . '/js/hogan-2.0.0.js',CClientScript::POS_END);
        }
        $typeahead = YII_DEBUG === true ? 'typeahead.js' : 'typeahead.min.js';
        $cs->registerScriptFile($this->_assetsUrl . '/js/' . $typeahead,CClientScript::POS_END);
        Yii::app()->clientScript->registerScript(__CLASS__ . $this->getId(),"var {$id} = $('#" . $id . "').typeahead({$options})\n$eventsScript",CClientScript::POS_READY);
    }
}