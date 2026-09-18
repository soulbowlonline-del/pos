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

    public function init()
    {
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
