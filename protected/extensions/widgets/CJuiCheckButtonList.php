<?php
/**
 * CJuiCheckButtonList class file.
 *
 * @author Shiv Charan Panjeta
 */

Yii::import('zii.widgets.jui.CJuiInputWidget');

/**
 * CJuiCheckButtonList displays a radio button list widget.
 *
 * It encapsulates the {@link http://jqueryui.com/demos/button/ JUI Button}
 * plugin.
 *
 * To use this widget as a submit button, you may insert the following code in a view:
 * <pre>
 * $this->widget('ext.CJuiCheckButtonList', array(
 'model'=>$model,
 'attribute'=>'status',
 'data'=>Lookup::items('PostStatus'),
 ));
 * </pre>
 *
*/
class CJuiCheckButtonList extends CJuiInputWidget
{
	/**
	 * @var string The button type (possible types: submit, button, link, radio, checkbox, buttonset).
	 * "submit" is used as default.
	 */
	public $buttonType = 'buttonset';

	/**
	 * @var string The default html tag for the buttonset
	 */
	public $htmlTag = 'div';
	/**
	 * @var string The url used when a buttonType "link" is selected.
	 */
	public $url = null;

	/**
	 * @var mixed The value of the current item. Used only for "radio" and "checkbox"
	 */
	public $value;

	public $data = array();

	/**
	 * @var string The button text
	*/
	public $caption="";
	/**
	 * @var string The javascript function to be raised when this item is clicked (client event).
	 */
	public $onclick;

	/**
	 * (non-PHPdoc)
	 * @see framework/zii/widgets/jui/CJuiWidget::init()
	 */
	public function init(){
		parent::init();
		if ($this->buttonType=='buttonset')
		{
			list($name,$id)=$this->resolveNameID();

			if(isset($this->htmlOptions['id']))
				$id = $this->htmlOptions['id'];
			else
				$this->htmlOptions['id']=$id;
			if(isset($this->htmlOptions['name']))
				$name = $this->htmlOptions['name'];
			else
				$this->htmlOptions['name'] = $name;

			$options = $this->htmlOptions;
			//unset($options['separator']);
			unset($options['displayStyle']);

			echo CHtml::openTag($this->htmlTag, $options);
		}
	}

	/**
	 * (non-PHPdoc)
	 * @see framework/CWidget::run()
	 */
	public function run()
	{
		$cs = Yii::app()->getClientScript();
		list($name,$id)=$this->resolveNameID();

		if(isset($this->htmlOptions['id']))
			$id=$this->htmlOptions['id'];
		else
			$this->htmlOptions['id']=$id;
		if(isset($this->htmlOptions['name']))
			$name=$this->htmlOptions['name'];
		else
			$this->htmlOptions['name']=$name;

		$displayStyle = 'display:inline-block';
		$this->htmlOptions['separator']='';
		if(isset($this->htmlOptions['displayStyle']))
		{
			switch ( $this->htmlOptions['displayStyle'] )
			{
				case 'rows': $displayStyle = 'display:block';	break;
				default:$this->htmlOptions['separator']='';
			}
			unset($this->htmlOptions['displayStyle']);
		}
		$this->htmlOptions['labelOptions'] = array('style'=>$displayStyle);

		if ($this->hasModel())
		{
			echo CHtml::activeCheckBoxList($this->model, $this->attribute, $this->data ,$this->htmlOptions);
		}
		else
		{
			echo CHtml::checkBoxList($name, $this->value,$this->data,$this->htmlOptions );
		}
		if ($this->buttonType=='buttonset')
		{
			echo CHtml::closeTag($this->htmlTag);
			$cs->registerScript(__CLASS__.'#'.$id,"jQuery('#{$id}').buttonset();");
		}
	}
}
