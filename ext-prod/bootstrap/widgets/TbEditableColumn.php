<?php
/*## EditableColumn class file.
 * @see <https://github.com/vitalets/x-editable-yii>
 *
 * @author Vitaliy Potapov <noginsk@rambler.ru>
 * @link https://github.com/vitalets/x-editable-yii
 * @copyright Copyright &copy; Vitaliy Potapov 2012
 * @package bootstrap.widgets
 * @version 1.0.0
*/

Yii::import('bootstrap.widgets.TbEditableField');
Yii::import('bootstrap.widgets.TbDataColumn');

/**
* EditableColumn widget makes editable one column in CGridView.
*
* @package widgets
*/
class TbEditableColumn extends TbDataColumn
{
    /**
    * @var array editable config options.
    * @see EditableField config
    */
    public $editable = array();

    //flag to render client script only once for all column cells
    private $_isScriptRendered = false;

  /**
   *### .init()
   *
   * Widget initialization
   */
    public function init()
    {
        if (!$this->grid->dataProvider instanceOf CActiveDataProvider) {
            throw new CException('EditableColumn can be applied only to grid based on CActiveDataProvider');
        }
        if (!$this->name) {
            throw new CException('You should provide name for EditableColumn');
        }

        parent::init();

        //need to attach ajaxUpdate handler to refresh editables on pagination and sort
        //should be here, before render of grid js
        $this->attachAjaxUpdateEvent();
    }

  /**
   *### .renderDataCellContent()
   */
    protected function renderDataCellContent($row, $data)
    {
        $options = CMap::mergeArray($this->editable, array(
            'model'     => $data,
            'attribute' => $this->name,
            'parentid'  => $this->grid->id,
        ));

        //if value defined for column --> use it as element text
        // (string) for PHP 8.1: `text` and `value` both default to null,
        // and strlen(null) is deprecated - which this application turns
        // into a 500, because Yii 1's error handler reports a deprecation
        // like any other error. purchaseBill/view renders an editable
        // column and died here on PHP 8.3 while working on 5.6. The cast
        // keeps the test exactly as it was: strlen(null) was 0, and
        // strlen((string) null) is 0.
        if (strlen((string) $this->value)) {
            ob_start();
            parent::renderDataCellContent($row, $data);
            $text = ob_get_clean();
            $options['text'] = $text;
            $options['encode'] = false;
        }

        /** @var $widget TbEditableField */
        $widget = $this->grid->controller->createWidget('TbEditableField', $options);

        //if editable not applied --> render original text
        if (!$widget->apply) {

           if (isset($text)) {
               echo $text;
           } else {
               parent::renderDataCellContent($row, $data);
           }
           return;
        }

        //manually make selector non unique to match all cells in column
        $selector = get_class($widget->model) . '_' . $widget->attribute;
        $widget->htmlOptions['rel'] = $selector;

        //can't call run() as it registers clientScript
        $widget->renderLink();

        //manually render client script (one for all cells in column)
        if (!$this->_isScriptRendered) {
            $script = $widget->registerClientScript();
            //use parent() as grid is totally replaced by new content
            Yii::app()->getClientScript()->registerScript(__CLASS__ . '#' . $this->grid->id . $selector.'-event', '
                $("#'.$this->grid->id.'").parent().on("ajaxUpdate.yiiGridView", "#'.$this->grid->id.'", function() {'.$script.'});
            ');
            $this->_isScriptRendered = true;
        }
    }

   /**
   *### .attachAjaxUpdateEvent()
   *
   * Yii yet does not support custom js events in widgets.
   * So we need to invoke it manually to ensure update of editables on grid ajax update.
   *
   * issue in Yii github: <https://github.com/yiisoft/yii/issues/1313>
   *
   */
    protected function attachAjaxUpdateEvent()
    {
        $trigger = '$("#"+id).trigger("ajaxUpdate.yiiGridView");';

        //check if trigger already inserted by another column
        // (string): afterAjaxUpdate is null until some column sets it, and
        // passing null to strpos/strlen is deprecated on PHP 8.1.
        if (strpos((string)$this->grid->afterAjaxUpdate, $trigger) !== false) return;

        //inserting trigger
        if (strlen((string)$this->grid->afterAjaxUpdate)) {
            $orig = $this->grid->afterAjaxUpdate;
            if (strpos($orig, 'js:')===0) $orig = substr($orig,3);
            $orig = "\n($orig).apply(this, arguments);";
        } else {
            $orig = '';
        }
        $this->grid->afterAjaxUpdate = "js: function(id, data) {
            $trigger $orig
        }";
    }
}
