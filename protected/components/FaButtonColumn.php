<?php

Yii::import('zii.widgets.grid.CButtonColumn');

/**
 * CButtonColumn rendering Font Awesome icons instead of image buttons.
 *
 * This reproduces a patch that was previously applied directly to
 * framework/zii/widgets/grid/CButtonColumn.php. Editing the framework in place
 * blocked every Yii upgrade, so the behaviour now lives here as a subclass and
 * the framework is stock.
 *
 * Behaviour is deliberately identical to the old core patch:
 *   - view/update/delete buttons default to fa-eye / fa-pencil-square-o / fa-trash
 *   - a button carrying an 'imageUrl' renders as <i class="{icon}"></i> rather
 *     than CHtml::image(), which is what the original patch did
 */
class FaButtonColumn extends CButtonColumn
{
	/** @var string|false icon for the view button; false disables the override. */
	public $viewButtonIcon = 'fa fa-eye';

	/** @var string|false icon for the update button; false disables the override. */
	public $updateButtonIcon = 'fa fa-pencil-square-o';

	/** @var string|false icon for the delete button; false disables the override. */
	public $deleteButtonIcon = 'fa fa-trash';

	/**
	 * Seeds the icon for each default button, then defers to the stock
	 * implementation for labels, URLs and imageUrl defaults.
	 */
	protected function initDefaultButtons()
	{
		if ($this->viewButtonIcon !== false && !isset($this->buttons['view']['icon']))
			$this->buttons['view']['icon'] = $this->viewButtonIcon;
		if ($this->updateButtonIcon !== false && !isset($this->buttons['update']['icon']))
			$this->buttons['update']['icon'] = $this->updateButtonIcon;
		if ($this->deleteButtonIcon !== false && !isset($this->buttons['delete']['icon']))
			$this->buttons['delete']['icon'] = $this->deleteButtonIcon;

		parent::initDefaultButtons();
	}

	/**
	 * Renders an icon in place of the button image.
	 *
	 * The original core patch read $button['icon'] unconditionally inside the
	 * imageUrl branch, which emits an undefined-index warning under PHP 8 for
	 * any custom button that sets imageUrl without an icon. The isset() guard
	 * below keeps the rendered output identical while silencing that warning.
	 */
	protected function renderButton($id, $button, $row, $data)
	{
		if (isset($button['visible']) && !$this->evaluateExpression($button['visible'], array('row' => $row, 'data' => $data)))
			return;

		$label = isset($button['label']) ? $button['label'] : $id;
		$url = isset($button['url']) ? $this->evaluateExpression($button['url'], array('data' => $data, 'row' => $row)) : '#';
		$options = isset($button['options']) ? $button['options'] : array();
		if (!isset($options['title']))
			$options['title'] = $label;

		if (isset($button['imageUrl']) && is_string($button['imageUrl']) && isset($button['icon']))
			echo CHtml::link('<i class="' . $button['icon'] . '"></i> ', $url, $options);
		elseif (isset($button['imageUrl']) && is_string($button['imageUrl']))
			echo CHtml::link(CHtml::image($button['imageUrl'], $label), $url, $options);
		else
			echo CHtml::link($label, $url, $options);
	}
}
