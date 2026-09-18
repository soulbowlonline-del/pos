<?php
namespace app\widgets;

use yii\helpers\ArrayHelper;

/**
 * Stand-in for bootstrap.widgets.TbGridView.
 *
 * Yii 2's own grid does the work; this only translates TbGridView's `type`
 * string - 'striped bordered condensed' - into the table classes the theme's
 * CSS expects, and turns off the summary line and the "no results" caption
 * that Yii 2 adds and Yii 1 does not.
 */
class GridView extends \yii\grid\GridView
{
    /** @var string space-separated TbGridView table flavours */
    public $type = '';

    /** @var array TbGridView's name for the container's tag attributes. */
    public $htmlOptions = [];

    /** @var \yii\base\Model TbGridView calls the filter model `filter`. */
    public $filter;

    public function init()
    {
        if ($this->filter !== null && $this->filterModel === null) {
            $this->filterModel = $this->filter;
        }
        if (!empty($this->htmlOptions)) {
            $this->options = ArrayHelper::merge($this->htmlOptions, $this->options);
        }
        $classes = ['table'];
        foreach (preg_split('/\s+/', trim($this->type)) as $t) {
            if ($t !== '') {
                $classes[] = 'table-' . $t;
            }
        }
        $this->tableOptions = ArrayHelper::merge(
            ['class' => implode(' ', $classes)],
            $this->tableOptions
        );

        if ($this->summary === null) {
            $this->summary = '';
        }
        // Yii 1 renders an empty table body; Yii 2 would print a message row.
        if ($this->emptyText === null) {
            $this->emptyText = '';
        }

        parent::init();
    }
}
