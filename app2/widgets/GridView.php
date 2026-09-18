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
    use IgnoresLegacyOptions;

    /** @var string space-separated TbGridView table flavours */
    public $type = '';

    /** @var array TbGridView's name for the container's tag attributes. */
    public $htmlOptions = [];

    /** @var \yii\base\Model TbGridView calls the filter model `filter`. */
    public $filter;

    public function init()
    {
        // Plain columns go through the shim too, so a column written with
        // Yii 1's htmlOptions does not fail to construct.
        if ($this->dataColumnClass === null) {
            $this->dataColumnClass = DataColumn::class;
        }

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

        // CGridView takes `'pager' => true` to mean "the default pager";
        // Yii 2 expects a configuration array and fails on a scalar.
        if (!is_array($this->pager)) {
            $this->pager = $this->pager ? [] : ['class' => \yii\widgets\LinkPager::class, 'options' => ['style' => 'display:none']];
        }

        // A column carrying a 'footer' means the grid has a totals row. Yii 1
        // renders it whenever any column defines one; Yii 2 needs to be told,
        // and without it the table is one row shorter than the original.
        foreach ($this->columns as $column) {
            if (is_array($column) && isset($column['footer'])) {
                $this->showFooter = true;
                break;
            }
        }

        if ($this->summary === null) {
            $this->summary = '';
        }
        // CGridView's own default, which the views do not override. An earlier
        // guess that Yii 1 rendered nothing here was wrong: it prints this.
        if ($this->emptyText === null) {
            $this->emptyText = 'No results found.';
        }

        parent::init();
    }
}
